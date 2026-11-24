<?php

namespace App\Services;

use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\PergerakanStok;
use App\Models\StokBatch;
use App\Models\StokKasir;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Semua perubahan stok (menu & bumbu) lewat sini. Stok sekarang dimiliki PER KASIR:
 *  - tabel stok_kasirs menyimpan sisa stok tiap kasir; menus.stok / bumbus.stok = total semua kasir
 *  - tambah()  : stok masuk untuk satu kasir -> bikin batch baru (milik kasir itu) dengan tanggal exp
 *  - keluar()  : stok kasir terpakai -> batch kasir itu yang paling dekat exp dipakai duluan (FEFO)
 *  - kembali() : stok dikembalikan ke kasir yang sama (transaksi/PO dibatalkan)
 *  - koreksi() : admin mengoreksi angka stok seorang kasir (hitung ulang)
 *  - buangKadaluarsa() : sisa batch yang sudah lewat exp otomatis dibuang dari stok kasir pemiliknya
 */
class StokService
{
    /** Stok standar saat admin memakai "Isi Semua Stok" (sama dengan stok di Master Menu / Bumbu). */
    public const STOK_STANDAR = 50;

    /** Masa simpan standar: menu 2 minggu, bumbu 1 bulan (lebih awet) */
    public const MASA_SIMPAN_MENU = 14;
    public const MASA_SIMPAN_BUMBU = 30;

    public static function tipe(Model $item): string
    {
        return $item instanceof Menu ? 'menu' : 'bumbu';
    }

    public static function expDefault(string $tipe = 'menu'): string
    {
        $hari = $tipe === 'bumbu' ? self::MASA_SIMPAN_BUMBU : self::MASA_SIMPAN_MENU;

        return today()->addDays($hari)->toDateString();
    }

    /**
     * Menu kategori Minuman dibuat dadakan, jadi tidak punya tanggal exp.
     * Menu lain (kentang) dan semua bumbu punya exp.
     */
    public static function pakaiExp(Model $item): bool
    {
        if ($item instanceof Menu) {
            $kategori = mb_strtolower((string) optional($item->kategori)->nama_kategori);

            return ! str_contains($kategori, 'minum');
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Membaca stok per kasir
    // ------------------------------------------------------------------

    /** Sisa stok satu menu/bumbu milik satu kasir. */
    public static function stokDi(Model $item, int $kasirId): int
    {
        return (int) StokKasir::where('user_id', $kasirId)
            ->where('tipe', self::tipe($item))
            ->where('item_id', $item->id)
            ->value('stok');
    }

    /** Peta [item_id => stok] milik satu kasir untuk satu tipe ('menu' | 'bumbu'). */
    public static function petaStok(string $tipe, int $kasirId): array
    {
        return StokKasir::where('user_id', $kasirId)->where('tipe', $tipe)
            ->pluck('stok', 'item_id')
            ->map(fn ($s) => (int) $s)
            ->all();
    }

    /**
     * Ganti angka `stok` pada daftar menu/bumbu dengan stok milik satu kasir, supaya tampilan
     * (dan accessor status_stok) memakai stok kasir itu. HANYA untuk ditampilkan: jangan di-save().
     */
    public static function terapkanStokKasir(iterable $items, string $tipe, int $kasirId): void
    {
        $peta = self::petaStok($tipe, $kasirId);

        foreach ($items as $item) {
            $item->setAttribute('stok', $peta[$item->id] ?? 0);
        }
    }

    // ------------------------------------------------------------------
    // Mengubah stok
    // ------------------------------------------------------------------

    /** Baris stok kasir untuk item ini (dibuat 0 kalau belum ada), terkunci untuk transaksi DB. */
    private static function baris(Model $item, int $kasirId): StokKasir
    {
        $kunci = ['user_id' => $kasirId, 'tipe' => self::tipe($item), 'item_id' => $item->id];

        StokKasir::firstOrCreate($kunci, ['stok' => 0]);

        return StokKasir::where($kunci)->lockForUpdate()->first();
    }

    public static function tambah(Model $item, int $jumlah, int $kasirId, ?string $exp = null, ?string $keterangan = null): ?StokBatch
    {
        $baris = self::baris($item, $kasirId);
        $baris->increment('stok', $jumlah);
        self::hitungUlangTotal($item);

        if (! self::pakaiExp($item)) {
            return null; // minuman: stok bertambah, tanpa batch/exp
        }

        return StokBatch::create([
            'tipe' => self::tipe($item),
            'item_id' => $item->id,
            'user_id' => $kasirId,
            'jumlah_awal' => $jumlah,
            'sisa' => $jumlah,
            'dibuang' => 0,
            'tanggal_masuk' => today()->toDateString(),
            'tanggal_exp' => $exp ?: self::expDefault(self::tipe($item)),
            'keterangan' => $keterangan,
        ]);
    }

    /**
     * Catat biaya stok menu yang diisi admin ke Pengeluaran (Bahan Baku) = jumlah x harga modal menu.
     * Bumbu tidak dicatat sendiri karena modalnya sudah ikut di harga modal menu.
     */
    public static function catatBiayaMenu(Menu $menu, int $jumlah, int $kasirId, ?int $adminId = null): void
    {
        $modal = (int) $menu->harga_modal;
        if ($jumlah <= 0 || $modal <= 0) {
            return;
        }

        $namaKasir = User::whereKey($kasirId)->value('name') ?? 'kasir';

        Pengeluaran::create([
            'tanggal' => today(),
            'kategori' => 'bahan_baku',
            'deskripsi' => Str::limit("Isi stok {$menu->nama_menu}: {$jumlah} porsi x Rp " . number_format($modal, 0, ',', '.') . " untuk kasir {$namaKasir}", 150, ''),
            'jumlah' => $jumlah * $modal,
            'user_id' => $adminId,
        ]);
    }

    private static function statusDari(int $stok, int $minimum): string
    {
        if ($stok <= 0) {
            return 'habis';
        }

        return $stok <= $minimum ? 'menipis' : 'aman';
    }

    /**
     * Stok SEMUA menu & bumbu aktif untuk SETIAP kasir aktif (kasir x item), lengkap dengan statusnya.
     * Dipakai Dashboard & lonceng admin supaya yang dilihat adalah stok asli tiap kasir, bukan angka Master.
     * Item yang belum pernah diisi untuk seorang kasir dianggap 0 (habis).
     */
    public static function barisStokSemuaKasir(): Collection
    {
        $kasirs = User::kasir()->aktif()->orderBy('name')->get(['id', 'name']);
        $menus = Menu::aktif()->with('kategori')->orderBy('nama_menu')->get();
        $bumbus = Schema::hasTable('bumbus') ? Bumbu::aktif()->orderBy('nama')->get() : collect();
        $baris = collect();

        foreach ($kasirs as $kasir) {
            $petaMenu = self::petaStok('menu', $kasir->id);
            $petaBumbu = self::petaStok('bumbu', $kasir->id);

            foreach ($menus as $m) {
                $stok = (int) ($petaMenu[$m->id] ?? 0);
                $baris->push((object) [
                    'kunci' => 'menu:' . $m->id, 'nama' => $m->nama_menu, 'kasir' => $kasir->name,
                    'jenis' => $m->kategori->nama_kategori ?? 'Menu', 'stok' => $stok, 'satuan' => 'pcs',
                    'status' => self::statusDari($stok, (int) $m->stok_minimum),
                ]);
            }

            foreach ($bumbus as $b) {
                $stok = (int) ($petaBumbu[$b->id] ?? 0);
                $baris->push((object) [
                    'kunci' => 'bumbu:' . $b->id, 'nama' => $b->nama, 'kasir' => $kasir->name,
                    'jenis' => $b->jenis === 'saus' ? 'Saus' : 'Bumbu tabur', 'stok' => $stok, 'satuan' => $b->satuan,
                    'status' => self::statusDari($stok, (int) $b->stok_minimum),
                ]);
            }
        }

        return $baris;
    }

    /** Stok di Master Menu / Bumbu = jumlah stok semua kasir. */
    public static function hitungUlangTotal(Model $item): void
    {
        $total = (int) StokKasir::where('tipe', self::tipe($item))->where('item_id', $item->id)->sum('stok');
        $item->forceFill(['stok' => $total])->save();
    }

    public static function keluar(Model $item, int $jumlah, int $kasirId): void
    {
        $baris = self::baris($item, $kasirId);
        $ambil = min($jumlah, (int) $baris->stok);

        if ($ambil > 0) {
            $baris->decrement('stok', $ambil);
            $item->decrement('stok', min($ambil, (int) $item->stok));
        }

        if (self::pakaiExp($item)) {
            self::ambilFefo($item, $jumlah, $kasirId);
        }
    }

    public static function kembali(Model $item, int $jumlah, int $kasirId): void
    {
        $baris = self::baris($item, $kasirId);
        $baris->increment('stok', $jumlah);
        $item->increment('stok', $jumlah);

        if (! self::pakaiExp($item)) {
            return;
        }

        $batch = StokBatch::where('tipe', self::tipe($item))
            ->where('item_id', $item->id)
            ->where('user_id', $kasirId)
            ->where('tanggal_exp', '>=', today()->toDateString())
            ->orderBy('tanggal_exp')->orderBy('id')
            ->first();

        if ($batch) {
            $batch->increment('sisa', $jumlah);

            return;
        }

        StokBatch::create([
            'tipe' => self::tipe($item),
            'item_id' => $item->id,
            'user_id' => $kasirId,
            'jumlah_awal' => $jumlah,
            'sisa' => $jumlah,
            'dibuang' => 0,
            'tanggal_masuk' => today()->toDateString(),
            'tanggal_exp' => self::expDefault(self::tipe($item)),
            'keterangan' => 'Stok dikembalikan (pembatalan)',
        ]);
    }

    /**
     * Koreksi angka stok seorang kasir menjadi $stokBaru (misalnya setelah hitung ulang).
     * Total di menus/bumbus dan batch exp kasir itu ikut disesuaikan.
     */
    public static function koreksi(Model $item, int $kasirId, int $stokBaru, string $keterangan = 'Koreksi stok'): void
    {
        $baris = self::baris($item, $kasirId);
        $selisih = $stokBaru - (int) $baris->stok;

        if ($selisih === 0) {
            return;
        }

        $baris->update(['stok' => $stokBaru]);

        if ($selisih > 0) {
            $item->increment('stok', $selisih);
        } else {
            $item->decrement('stok', min(-$selisih, (int) $item->stok));
        }

        self::sinkron($item, $kasirId, $keterangan);
    }

    /** Samakan jumlah batch seorang kasir dengan angka stok kasir itu. */
    public static function sinkron(Model $item, int $kasirId, string $keterangan = 'Penyesuaian stok'): void
    {
        if (! self::pakaiExp($item)) {
            return;
        }

        $tipe = self::tipe($item);
        $total = (int) StokBatch::where('tipe', $tipe)->where('item_id', $item->id)->where('user_id', $kasirId)->sum('sisa');
        $stok = self::stokDi($item, $kasirId);

        if ($stok > $total) {
            StokBatch::create([
                'tipe' => $tipe, 'item_id' => $item->id, 'user_id' => $kasirId,
                'jumlah_awal' => $stok - $total, 'sisa' => $stok - $total, 'dibuang' => 0,
                'tanggal_masuk' => today()->toDateString(), 'tanggal_exp' => self::expDefault($tipe),
                'keterangan' => $keterangan,
            ]);
        } elseif ($stok < $total) {
            self::ambilFefo($item, $total - $stok, $kasirId);
        }
    }

    private static function ambilFefo(Model $item, int $jumlah, int $kasirId): void
    {
        $perlu = $jumlah;
        $batches = StokBatch::where('tipe', self::tipe($item))
            ->where('item_id', $item->id)
            ->where('user_id', $kasirId)
            ->where('sisa', '>', 0)
            ->orderBy('tanggal_exp')->orderBy('id')
            ->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if ($perlu <= 0) {
                break;
            }
            $ambil = min((int) $batch->sisa, $perlu);
            $batch->decrement('sisa', $ambil);
            $perlu -= $ambil;
        }
    }

    // ------------------------------------------------------------------
    // Exp & kadaluarsa
    // ------------------------------------------------------------------

    /**
     * Tanggal exp terdekat (yang masih ada sisanya) per item: [item_id => 'Y-m-d'].
     * Kalau $kasirId diisi, hanya batch milik kasir itu.
     */
    public static function expTerdekat(string $tipe, ?int $kasirId = null): Collection
    {
        if (! Schema::hasTable('stok_batches')) {
            return collect();
        }

        return StokBatch::where('tipe', $tipe)->where('sisa', '>', 0)
            ->when($kasirId, fn ($q) => $q->where('user_id', $kasirId))
            ->selectRaw('item_id, MIN(tanggal_exp) as exp')
            ->groupBy('item_id')
            ->pluck('exp', 'item_id');
    }

    /** Samakan batch semua kasir yang jumlahnya tidak cocok dengan angka stoknya. */
    public static function sinkronSemua(): void
    {
        if (! Schema::hasTable('stok_kasirs')) {
            return;
        }

        $batch = StokBatch::whereNotNull('user_id')
            ->groupBy('user_id', 'tipe', 'item_id')
            ->selectRaw('user_id, tipe, item_id, SUM(sisa) as total')
            ->get()
            ->keyBy(fn ($r) => "{$r->user_id}|{$r->tipe}|{$r->item_id}");

        foreach (StokKasir::all() as $baris) {
            $total = (int) ($batch["{$baris->user_id}|{$baris->tipe}|{$baris->item_id}"]->total ?? 0);
            if ($total === (int) $baris->stok) {
                continue;
            }

            $item = $baris->tipe === 'menu'
                ? Menu::with('kategori')->find($baris->item_id)
                : (Schema::hasTable('bumbus') ? Bumbu::find($baris->item_id) : null);

            if (! $item || ! self::pakaiExp($item)) {
                continue;
            }

            DB::transaction(fn () => self::sinkron($item, (int) $baris->user_id));
        }
    }

    /**
     * Buang sisa stok dari batch yang tanggal exp-nya sudah lewat (exp kemarin atau lebih lama).
     * Stok kasir pemilik batch dikurangi, dan untuk menu dicatat di riwayat stok sebagai "Kadaluarsa".
     * Return: jumlah batch yang dibuang.
     */
    public static function buangKadaluarsa(): int
    {
        self::sinkronSemua();

        $ids = StokBatch::where('sisa', '>', 0)
            ->where('tanggal_exp', '<', today()->toDateString())
            ->orderBy('id')->pluck('id');

        $jumlahBatch = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$jumlahBatch) {
                $batch = StokBatch::lockForUpdate()->find($id);
                if (! $batch || $batch->sisa <= 0) {
                    return;
                }

                // Batch lama tanpa pemilik (sebelum stok dibagi per kasir): cukup dinolkan
                if (! $batch->user_id) {
                    $batch->update(['dibuang' => (int) $batch->dibuang + (int) $batch->sisa, 'sisa' => 0, 'dibuang_pada' => now()]);

                    return;
                }

                $item = $batch->tipe === 'menu'
                    ? Menu::with('kategori')->lockForUpdate()->find($batch->item_id)
                    : Bumbu::lockForUpdate()->find($batch->item_id);

                // Minuman tidak punya exp: sisa batch lama (kalau ada) dihapus, stok tidak disentuh
                if ($item && ! self::pakaiExp($item)) {
                    $batch->delete();

                    return;
                }

                $buang = (int) $batch->sisa;

                if ($item) {
                    $baris = self::baris($item, (int) $batch->user_id);
                    $kurangi = min($buang, (int) $baris->stok);

                    if ($kurangi > 0) {
                        $baris->decrement('stok', $kurangi);
                        $item->decrement('stok', min($kurangi, (int) $item->stok));

                        if ($batch->tipe === 'menu') {
                            PergerakanStok::create([
                                'menu_id' => $item->id,
                                'user_id' => null,
                                'kasir_id' => $batch->user_id,
                                'jenis' => 'keluar',
                                'jumlah' => $kurangi,
                                'keterangan' => 'Kadaluarsa otomatis (exp ' . Carbon::parse($batch->tanggal_exp)->format('d/m/Y') . ')',
                                'tanggal' => today(),
                            ]);
                        }
                    }
                }

                $batch->update([
                    'dibuang' => (int) $batch->dibuang + $buang,
                    'sisa' => 0,
                    'dibuang_pada' => now(),
                ]);

                $jumlahBatch++;
            });
        }

        return $jumlahBatch;
    }
}
