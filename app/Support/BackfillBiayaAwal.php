<?php

namespace App\Support;

/**
 * Menyusun catatan Pengeluaran untuk stok awal operasional yang sudah dibuat SEBELUM biaya stok awal
 * ikut dicatat. Murni hitungan (tanpa database) supaya mudah diuji.
 */
class BackfillBiayaAwal
{
    /**
     * @param array $stok   baris stok_operasionals (user_id, bahan, created_at)
     * @param array $bahan  [kode => baris bahan_operasionals]
     * @param array $kasir  [user_id => nama]
     * @param array $ada    deskripsi pengeluaran "Stok awal ..." yang sudah ada
     * @return array baris siap insert ke tabel pengeluarans
     */
    public static function hitung(array $stok, array $bahan, array $kasir, array $ada): array
    {
        $baru = [];

        foreach ($stok as $s) {
            $b = $bahan[$s['bahan']] ?? null;
            if (! $b) {
                continue;
            }

            $harga = (int) ($b['harga_per_unit'] ?? 0);
            $isi = max((int) ($b['isi_per_unit'] ?? 1), 1);
            $unit = ((int) ($b['stok_awal'] ?? 0)) / $isi;
            $biaya = (int) round($unit * $harga);
            if ($harga <= 0 || $biaya <= 0) {
                continue;
            }

            $namaKasir = $kasir[$s['user_id']] ?? 'kasir';

            // Sudah dicatat sebelumnya (otomatis oleh sistem atau oleh menjalankan ulang)? Lewati.
            $sudah = false;
            foreach ($ada as $deskripsi) {
                if (str_starts_with((string) $deskripsi, "Stok awal {$b['nama']}:")
                    && str_contains((string) $deskripsi, "untuk kasir {$namaKasir}")) {
                    $sudah = true;
                    break;
                }
            }
            if ($sudah) {
                continue;
            }

            $unitTeks = floor($unit) == $unit ? (string) (int) $unit : rtrim(rtrim(number_format($unit, 2, '.', ''), '0'), '.');
            $deskripsi = mb_substr("Stok awal {$b['nama']}: {$unitTeks} {$b['unit_isi']} untuk kasir {$namaKasir} (data lama)", 0, 150);

            $waktu = $s['created_at'] ?? date('Y-m-d H:i:s');

            $baru[] = [
                'tanggal' => substr((string) $waktu, 0, 10),
                'kategori' => $b['kategori_pengeluaran'] ?? 'operasional',
                'deskripsi' => $deskripsi,
                'jumlah' => $biaya,
                'user_id' => null,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];

            // Supaya menjalankan ulang tidak mencatat dua kali
            $ada[] = $deskripsi;
        }

        return $baru;
    }
}
