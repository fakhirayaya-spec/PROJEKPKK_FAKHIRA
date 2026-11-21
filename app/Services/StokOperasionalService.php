<?php

namespace App\Services;

use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\RiwayatStokOperasional;
use App\Models\StokOperasional;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stok operasional PER KASIR (gas, minyak, dus, cup, es batu, dan bahan tambahan dari admin).
 * Otomatis berkurang setiap transaksi berhasil, dan dikembalikan kalau transaksi dibatalkan.
 *
 * Aturan tiap bahan disimpan di tabel bahan_operasionals (diatur admin di Kelola Bahan):
 *  - dasar_pakai : kentang | minuman | semua_item | transaksi
 *  - per_kelompok: berkurang 1 untuk tiap sekian satuan dasar, dibulatkan ke atas, per transaksi
 *
 * Contoh bawaan: gas & minyak = kentang per 3 porsi (1-3 porsi = 1, 4-6 = 2, 7-9 = 3, dst),
 * dus = kentang per 1, cup & es batu = minuman per 1.
 */
class StokOperasionalService
{
    /** Bahan yang aktif, urut sesuai tampilan */
    public static function bahanAktif(): Collection
    {
        return BahanOperasional::aktif()->orderBy('urutan')->orderBy('id')->get();
    }

    /** Pastikan kasir punya baris stok untuk semua bahan aktif (dibuat dengan stok awal bahan). */
    public static function pastikan(int $userId, ?Collection $aktif = null): void
    {
        $aktif ??= self::bahanAktif();
        $ada = StokOperasional::where('user_id', $userId)->pluck('bahan')->all();

        foreach ($aktif as $bahan) {
            if (! in_array($bahan->kode, $ada, true)) {
                $baris = StokOperasional::firstOrCreate(
                    ['user_id' => $userId, 'bahan' => $bahan->kode],
                    ['stok' => $bahan->stok_awal]
                );

                // Stok awal ini dibeli pemilik usaha, jadi biayanya ikut dicatat di Pengeluaran
                // (hanya saat baris stok baru dibuat, jadi tidak pernah tercatat dua kali).
                if ($baris->wasRecentlyCreated) {
                    self::catatBiayaAwal($userId, $bahan);
                }
            }
        }
    }

    /**
     * Catat biaya stok awal ke Pengeluaran (kategori mengikuti pengaturan bahan, sama seperti biaya isi ulang).
     * Biaya = (stok awal / isi per unit) x harga per unit. Kalau harga per unit belum diisi, tidak ada yang dicatat.
     */
    private static function catatBiayaAwal(int $userId, BahanOperasional $bahan): void
    {
        $isi = max((int) $bahan->isi_per_unit, 1);
        $harga = (int) $bahan->harga_per_unit;
        $unit = ((int) $bahan->stok_awal) / $isi;
        $biaya = (int) round($unit * $harga);

        if ($harga <= 0 || $biaya <= 0) {
            return;
        }

        $namaKasir = User::whereKey($userId)->value('name') ?? 'kasir';
        $unitTeks = floor($unit) == $unit ? (string) (int) $unit : rtrim(rtrim(number_format($unit, 2, '.', ''), '0'), '.');

        Pengeluaran::create([
            'tanggal' => today(),
            'kategori' => $bahan->kategori_pengeluaran ?: 'operasional',
            'deskripsi' => Str::limit("Stok awal {$bahan->nama}: {$unitTeks} {$bahan->unit_isi} untuk kasir {$namaKasir}", 150, ''),
            'jumlah' => $biaya,
            'user_id' => null,
        ]);
    }

    /** Stok bahan aktif milik satu kasir, berurutan, dengan kunci kode bahan: ['gas' => StokOperasional, ...] */
    public static function stokKasir(int $userId, ?Collection $aktif = null): Collection
    {
        $aktif ??= self::bahanAktif();
        $aktif = $aktif->keyBy('kode');
        self::pastikan($userId, $aktif);

        $urutan = $aktif->keys()->all();

        return StokOperasional::where('user_id', $userId)
            ->whereIn('bahan', $urutan)
            ->get()
            ->each(fn ($baris) => $baris->setRelation('master', $aktif[$baris->bahan]))
            ->sortBy(fn ($baris) => array_search($baris->bahan, $urutan, true))
            ->keyBy('bahan');
    }

    private static function menuMinuman(Menu $menu): bool
    {
        $kategori = mb_strtolower((string) optional($menu->kategori)->nama_kategori);

        return str_contains($kategori, 'minum');
    }

    /**
     * Hitung kebutuhan tiap bahan aktif dari item transaksi.
     * $items: array berisi ['menu' => Menu, 'qty' => int]
     * Hasil: ['gas' => n, 'dus' => n, ...] (kunci = kode bahan)
     */
    public static function hitungKebutuhan(iterable $items, ?Collection $aktif = null): array
    {
        $aktif ??= self::bahanAktif();

        $kentang = 0;
        $minuman = 0;
        $adaItem = false;

        foreach ($items as $item) {
            $adaItem = true;
            $qty = (int) $item['qty'];

            if (self::menuMinuman($item['menu'])) {
                $minuman += $qty;
            } else {
                $kentang += $qty;
            }
        }

        $hasil = [];
        foreach ($aktif as $bahan) {
            $dasar = match ($bahan->dasar_pakai) {
                'kentang' => $kentang,
                'minuman' => $minuman,
                'semua_item' => $kentang + $minuman,
                'transaksi' => $adaItem ? 1 : 0,
                default => 0,
            };

            $kelompok = $bahan->dasar_pakai === 'transaksi' ? 1 : max((int) $bahan->per_kelompok, 1);
            $hasil[$bahan->kode] = $dasar > 0 ? (int) ceil($dasar / $kelompok) : 0;
        }

        return $hasil;
    }

    /**
     * Tolak transaksi kalau ada bahan yang tidak cukup.
     * Panggil di dalam DB::transaction supaya baris stok terkunci sampai transaksi selesai.
     */
    public static function pastikanCukup(int $userId, iterable $items): void
    {
        $aktif = self::bahanAktif()->keyBy('kode');
        self::pastikan($userId, $aktif);

        $kebutuhan = self::hitungKebutuhan($items, $aktif);
        $stok = StokOperasional::where('user_id', $userId)->lockForUpdate()->get()->keyBy('bahan');

        $kurang = [];
        foreach ($kebutuhan as $kode => $butuh) {
            if ($butuh <= 0) {
                continue;
            }

            $sisa = (int) ($stok[$kode]->stok ?? 0);
            if ($sisa < $butuh) {
                $bahan = $aktif[$kode];
                $kurang[] = "{$bahan->nama} (sisa {$sisa} {$bahan->satuan}, dibutuhkan {$butuh})";
            }
        }

        if ($kurang) {
            throw ValidationException::withMessages([
                'stok_operasional' => 'Stok operasional tidak cukup: ' . implode('; ', $kurang) . '. Hubungi Admin untuk isi ulang.',
            ]);
        }
    }

    /**
     * Kurangi stok operasional kasir pemilik transaksi dan catat riwayatnya.
     * Panggil setelah Transaksi dibuat, di dalam DB::transaction yang sama.
     */
    public static function pakaiUntukTransaksi(Transaksi $transaksi, iterable $items): void
    {
        $userId = (int) $transaksi->user_id;

        self::pastikanCukup($userId, $items); // cek ulang + kunci baris stok

        $kebutuhan = self::hitungKebutuhan($items);
        $stok = StokOperasional::where('user_id', $userId)->lockForUpdate()->get()->keyBy('bahan');

        foreach ($kebutuhan as $kode => $butuh) {
            if ($butuh <= 0 || ! isset($stok[$kode])) {
                continue;
            }

            $baris = $stok[$kode];
            $baris->stok = $baris->stok - $butuh;
            $baris->save();

            RiwayatStokOperasional::create([
                'user_id' => $userId,
                'bahan' => $kode,
                'jenis' => 'pakai',
                'jumlah' => $butuh,
                'saldo' => $baris->stok,
                'transaksi_id' => $transaksi->id,
                'dilakukan_oleh' => $userId,
                'keterangan' => 'Transaksi ' . $transaksi->kode_transaksi,
            ]);
        }
    }

    /**
     * Kembalikan stok operasional yang dipakai transaksi (saat transaksi dibatalkan).
     * Yang dikembalikan persis sebesar yang tercatat waktu transaksi dibuat.
     * Transaksi lama (sebelum fitur ini ada) tidak punya catatan, jadi tidak ada yang dikembalikan.
     */
    public static function kembalikanTransaksi(Transaksi $transaksi, ?User $oleh = null): void
    {
        $sudahKembali = RiwayatStokOperasional::where('transaksi_id', $transaksi->id)->where('jenis', 'kembali')->exists();
        if ($sudahKembali) {
            return;
        }

        $dipakai = RiwayatStokOperasional::where('transaksi_id', $transaksi->id)
            ->where('jenis', 'pakai')
            ->get()
            ->groupBy('bahan')
            ->map(fn ($baris) => (int) $baris->sum('jumlah'));

        if ($dipakai->isEmpty()) {
            return;
        }

        $userId = (int) $transaksi->user_id;
        $stok = StokOperasional::where('user_id', $userId)->lockForUpdate()->get()->keyBy('bahan');

        foreach ($dipakai as $kode => $jumlah) {
            if (! isset($stok[$kode])) {
                continue;
            }

            $baris = $stok[$kode];
            $baris->stok = $baris->stok + $jumlah;
            $baris->save();

            RiwayatStokOperasional::create([
                'user_id' => $userId,
                'bahan' => $kode,
                'jenis' => 'kembali',
                'jumlah' => $jumlah,
                'saldo' => $baris->stok,
                'transaksi_id' => $transaksi->id,
                'dilakukan_oleh' => $oleh?->id,
                'keterangan' => 'Batal ' . $transaksi->kode_transaksi,
            ]);
        }
    }

    /**
     * Admin mengisi ulang stok operasional seorang kasir.
     * $jumlahUnit dalam satuan isi ulang bahan (mis. 1 tabung gas = +50 order).
     * Mengembalikan jumlah stok yang benar-benar bertambah.
     */
    public static function isiUlang(User $kasir, BahanOperasional $bahan, int $jumlahUnit, User $admin, ?string $keterangan = null): int
    {
        self::pastikan($kasir->id);

        $baris = StokOperasional::where('user_id', $kasir->id)->where('bahan', $bahan->kode)->lockForUpdate()->firstOrFail();

        $tambah = $jumlahUnit * max((int) $bahan->isi_per_unit, 1);
        $baris->stok = $baris->stok + $tambah;
        $baris->save();

        RiwayatStokOperasional::create([
            'user_id' => $kasir->id,
            'bahan' => $bahan->kode,
            'jenis' => 'isi_ulang',
            'jumlah' => $tambah,
            'saldo' => $baris->stok,
            'dilakukan_oleh' => $admin->id,
            'keterangan' => $keterangan ?: "Isi ulang {$jumlahUnit} {$bahan->unit_isi}",
        ]);

        return $tambah;
    }

    /**
     * Daftar peringatan untuk lonceng di navbar.
     *  - Kasir : hanya stok miliknya sendiri
     *  - Admin : stok semua kasir aktif (nama kasir ditampilkan)
     * Bentuk tiap baris sama dengan alert stok yang sudah ada: nama, jenis, stok, status.
     */
    public static function alertUntuk(User $user): Collection
    {
        $alert = collect();

        if ($user->role === 'kasir') {
            $kasirs = collect([$user]);
        } elseif ($user->role === 'admin') {
            $kasirs = User::kasir()->aktif()->orderBy('name')->get(['id', 'name']);
        } else {
            return $alert;
        }

        $aktif = self::bahanAktif()->keyBy('kode');
        if ($aktif->isEmpty() || $kasirs->isEmpty()) {
            return $alert;
        }

        $semua = StokOperasional::whereIn('user_id', $kasirs->pluck('id'))
            ->whereIn('bahan', $aktif->keys())
            ->get()
            ->groupBy('user_id');

        foreach ($kasirs as $kasir) {
            $baris = $semua->get($kasir->id, collect());

            // Kasir yang belum punya stok untuk semua bahan aktif: dibuatkan stok awal (hanya sekali)
            if ($baris->count() < $aktif->count()) {
                $baris = self::stokKasir($kasir->id, $aktif)->values();
            }

            foreach ($baris as $b) {
                $b->setRelation('master', $aktif[$b->bahan]);

                if ($b->status_stok === 'aman') {
                    continue;
                }

                $alert->push([
                    'nama' => $user->role === 'admin' ? "{$b->label} · {$kasir->name}" : $b->label,
                    'jenis' => 'Operasional',
                    'stok' => $b->stok,
                    'status' => $b->status_stok,
                ]);
            }
        }

        return $alert;
    }
}
