<?php

namespace App\Support;

/** Aturan pencocokan untuk penghapusan akun kasir (dipakai migration penghapusan kasir). */
class HapusKasir
{
    /** Nama mengandung kata "jeno" (bukan bagian dari kata lain, jadi "Jenova" tidak ikut). */
    public static function namaJeno(?string $nama): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])jeno(?![\p{L}\p{N}])/iu', (string) $nama);
    }

    /** Catatan Pengeluaran otomatis "Stok awal ... / Isi ulang ... untuk kasir <nama>" milik kasir ini. */
    public static function pengeluaranMilikKasir(string $nama, ?string $deskripsi): bool
    {
        $d = (string) $deskripsi;

        if (! preg_match('/^(Stok awal|Isi ulang|Isi stok) /u', $d)) {
            return false;
        }

        return (bool) preg_match('/ untuk kasir ' . preg_quote($nama, '/') . '(?: \(data lama\))?$/u', $d);
    }
}
