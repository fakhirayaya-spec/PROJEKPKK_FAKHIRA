<?php

namespace App\Services;

use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\StokBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Semua perubahan stok (menu & bumbu) lewat sini supaya angka stok dan batch exp-nya selalu sinkron.
 *  - tambah()  : stok masuk -> bikin batch baru dengan tanggal exp
 *  - keluar()  : stok terpakai -> batch yang paling dekat exp dipakai duluan (FEFO)
 *  - kembali() : stok dikembalikan (transaksi/PO dibatalkan)
 *  - buangKadaluarsa() : sisa batch yang sudah lewat exp otomatis dibuang dari stok
 */
class StokService
{
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

    public static function tambah(Model $item, int $jumlah, ?string $exp = null, ?string $keterangan = null): ?StokBatch
    {
        $item->increment('stok', $jumlah);

        if (! self::pakaiExp($item)) {
            return null; // minuman: stok bertambah, tanpa batch/exp
        }

        return StokBatch::create([
            'tipe' => self::tipe($item),
            'item_id' => $item->id,
            'jumlah_awal' => $jumlah,
            'sisa' => $jumlah,
            'dibuang' => 0,
            'tanggal_masuk' => today()->toDateString(),
            'tanggal_exp' => $exp ?: self::expDefault(self::tipe($item)),
            'keterangan' => $keterangan,
        ]);
    }

    public static function keluar(Model $item, int $jumlah): void
    {
        $item->decrement('stok', $jumlah);

        if (self::pakaiExp($item)) {
            self::ambilFefo($item, $jumlah);
        }
    }

    public static function kembali(Model $item, int $jumlah): void
    {
        $item->increment('stok', $jumlah);

        if (! self::pakaiExp($item)) {
            return;
        }

        $batch = StokBatch::where('tipe', self::tipe($item))
            ->where('item_id', $item->id)
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
            'jumlah_awal' => $jumlah,
            'sisa' => $jumlah,
            'dibuang' => 0,
            'tanggal_masuk' => today()->toDateString(),
            'tanggal_exp' => self::expDefault(self::tipe($item)),
            'keterangan' => 'Stok dikembalikan (pembatalan)',
        ]);
    }

    /** Samakan jumlah batch dengan angka stok (kalau stok diubah/di-seed langsung di luar alur biasa). */
    public static function sinkron(Model $item, string $keterangan = 'Penyesuaian stok'): void
    {
        if (! self::pakaiExp($item)) {
            return;
        }

        $tipe = self::tipe($item);
        $total = (int) StokBatch::where('tipe', $tipe)->where('item_id', $item->id)->sum('sisa');
        $stok = (int) $item->stok;

        if ($stok > $total) {
            StokBatch::create([
                'tipe' => $tipe, 'item_id' => $item->id,
                'jumlah_awal' => $stok - $total, 'sisa' => $stok - $total, 'dibuang' => 0,
                'tanggal_masuk' => today()->toDateString(), 'tanggal_exp' => self::expDefault($tipe),
                'keterangan' => $keterangan,
            ]);
        } elseif ($stok < $total) {
            self::ambilFefo($item, $total - $stok);
        }
    }

    private static function ambilFefo(Model $item, int $jumlah): void
    {
        $perlu = $jumlah;
        $batches = StokBatch::where('tipe', self::tipe($item))
            ->where('item_id', $item->id)
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

    /** Tanggal exp terdekat (yang masih ada sisanya) per item: [item_id => 'Y-m-d'] */
    public static function expTerdekat(string $tipe): Collection
    {
        if (! Schema::hasTable('stok_batches')) {
            return collect();
        }

        return StokBatch::where('tipe', $tipe)->where('sisa', '>', 0)
            ->selectRaw('item_id, MIN(tanggal_exp) as exp')
            ->groupBy('item_id')
            ->pluck('exp', 'item_id');
    }

    /** Samakan semua item yang jumlah batch-nya tidak cocok dengan angka stoknya. */
    public static function sinkronSemua(): void
    {
        $daftar = ['menu' => Menu::class];
        if (Schema::hasTable('bumbus')) {
            $daftar['bumbu'] = Bumbu::class;
        }

        foreach ($daftar as $tipe => $kelas) {
            $jumlah = StokBatch::where('tipe', $tipe)->groupBy('item_id')
                ->selectRaw('item_id, SUM(sisa) as total')->pluck('total', 'item_id');

            $query = $kelas::query();
            if ($tipe === 'menu') {
                $query->with('kategori');
            }

            foreach ($query->get() as $baris) {
                if (! self::pakaiExp($baris) || (int) ($jumlah[$baris->id] ?? 0) === (int) $baris->stok) {
                    continue;
                }
                DB::transaction(function () use ($kelas, $baris) {
                    $item = $kelas::lockForUpdate()->find($baris->id);
                    if ($item) {
                        self::sinkron($item);
                    }
                });
            }
        }
    }

    /**
     * Buang sisa stok dari batch yang tanggal exp-nya sudah lewat (exp kemarin atau lebih lama).
     * Stok dikurangi, dan untuk menu dicatat di riwayat stok sebagai "Kadaluarsa".
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
                    $kurangi = min($buang, (int) $item->stok);
                    if ($kurangi > 0) {
                        $item->decrement('stok', $kurangi);

                        if ($batch->tipe === 'menu') {
                            PergerakanStok::create([
                                'menu_id' => $item->id,
                                'user_id' => null,
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
