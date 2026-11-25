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

    /** Nama mengandung kata utuh ini, huruf besar/kecil tidak berpengaruh (mis. "Carmen", tapi bukan "Carmenia"). */
    public static function namaMengandungKata(string $kata, ?string $nama): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])' . preg_quote($kata, '/') . '(?![\p{L}\p{N}])/iu', (string) $nama);
    }

    /**
     * Catatan Pengeluaran apa pun yang menyebut "kasir <nama>": "Stok awal / Isi stok / Isi ulang ... untuk kasir <nama>"
     * maupun "Restock ... (pengajuan kasir <nama>)".
     */
    public static function pengeluaranMenyebutKasir(string $nama, ?string $deskripsi): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])kasir\s+' . preg_quote(trim($nama), '/') . '(?![\p{L}\p{N}])/iu', (string) $deskripsi);
    }
}
