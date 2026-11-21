<?php

namespace App\Support;

/**
 * Menyusun catatan Pengeluaran untuk isi ulang bahan operasional yang terjadi SEBELUM
 * biaya isi ulang ikut dicatat. Murni hitungan (tanpa database) supaya mudah diuji.
 */
class BackfillIsiUlang
{
    /**
     * @param array $riwayat  baris riwayat_stok_operasionals berjenis isi_ulang
     * @param array $bahan    [kode => baris bahan_operasionals]
     * @param array $kasir    [user_id => nama]
     * @param array $ada      pengeluaran "Isi ulang ..." yang sudah ada (deskripsi, user_id, created_at)
     * @return array baris siap insert ke tabel pengeluarans
     */
    public static function hitung(array $riwayat, array $bahan, array $kasir, array $ada): array
    {
        $baru = [];
        $dipakai = [];

        foreach ($riwayat as $r) {
            $b = $bahan[$r['bahan']] ?? null;
            if (! $b) {
                continue;
            }

            $harga = (int) ($b['harga_per_unit'] ?? 0);
            $isi = max((int) ($b['isi_per_unit'] ?? 1), 1);
            $unit = ((int) $r['jumlah']) / $isi;
            $biaya = (int) round($unit * $harga);
            if ($harga <= 0 || $biaya <= 0) {
                continue;
            }

            $namaKasir = $kasir[$r['user_id']] ?? 'kasir';
            $waktu = strtotime((string) $r['created_at']);

            // Sudah ada pengeluaran untuk isi ulang ini (dicatat otomatis setelah update sebelumnya)? Lewati.
            $sudah = false;
            foreach ($ada as $i => $p) {
                if (isset($dipakai[$i])) {
                    continue;
                }
                $cocok = str_starts_with((string) $p['deskripsi'], "Isi ulang {$b['nama']}:")
                    && str_contains((string) $p['deskripsi'], "untuk kasir {$namaKasir}")
                    && abs(strtotime((string) $p['created_at']) - $waktu) <= 10;
                if ($cocok) {
                    $dipakai[$i] = true;
                    $sudah = true;
                    break;
                }
            }
            if ($sudah) {
                continue;
            }

            $unitTeks = floor($unit) == $unit ? (string) (int) $unit : rtrim(rtrim(number_format($unit, 2, '.', ''), '0'), '.');
            $deskripsi = mb_substr("Isi ulang {$b['nama']}: {$unitTeks} {$b['unit_isi']} untuk kasir {$namaKasir} (data lama)", 0, 150);

            $baru[] = [
                'tanggal' => substr((string) $r['created_at'], 0, 10),
                'kategori' => 'operasional',
                'deskripsi' => $deskripsi,
                'jumlah' => $biaya,
                'user_id' => $r['dilakukan_oleh'] ?? null,
                'created_at' => $r['created_at'],
                'updated_at' => $r['created_at'],
            ];

            // Supaya menjalankan ulang tidak mencatat dua kali
            $ada[] = ['deskripsi' => $deskripsi, 'user_id' => $r['dilakukan_oleh'] ?? null, 'created_at' => $r['created_at']];
            $dipakai[count($ada) - 1] = false;
        }

        return $baru;
    }
}
