<?php

namespace App\Support;

/**
 * Kategori Pengeluaran ditentukan OTOMATIS dari keterangannya (tidak bisa dipilih/diubah manual):
 *  - Bahan Baku  : barang yang dijual / dicampur jadi produk (kentang, saus, bubuk, minuman, minyak, es batu, dll.)
 *  - Operasional : biaya agar usaha jalan (gas, kemasan dus/cup, listrik, gaji, sewa, transport, alat, dll.)
 * Kata operasional dicek lebih dulu, jadi "dus kentang" atau "ongkir belanja kentang" tetap Operasional.
 * Kalau tidak cocok dengan keduanya, jatuhnya Operasional.
 * Daftar kata yang sama dipakai halaman Pengeluaran untuk menampilkan kategori saat mengetik.
 */
class KategoriPengeluaran
{
    public static function kataBahanBaku(): array
    {
        return [
            'bahan', 'kentang', 'curly', 'bumbu', 'saus', 'sauce', 'tabur', 'bubuk', 'keju', 'mozzarella', 'balado', 'mayo',
            'mayones', 'mentai', 'blackpaper', 'black pepper', 'lava', 'jagung', 'matcha', 'thai', 'tea', 'teh', 'gula', 'susu',
            'creamer', 'tepung', 'telur', 'bawang', 'cabai', 'cabe', 'garam', 'merica', 'lada', 'penyedap', 'mentega',
            'margarin', 'kecap', 'sambal', 'seasoning', 'barbeque', 'bbq', 'rumput laut', 'cokelat', 'coklat', 'sirup', 'kopi',
            'ayam', 'sosis', 'daging', 'minyak', 'es batu', 'pedas', 'stok awal',
        ];
    }

    public static function kataOperasional(): array
    {
        return [
            'gas', 'elpiji', 'lpg', 'regulator', 'listrik', 'token', 'pdam', 'galon', 'wifi', 'internet', 'pulsa', 'gaji', 'upah',
            'honor', 'sewa', 'bensin', 'transport', 'ongkir', 'ongkos', 'kirim', 'parkir', 'sabun', 'tisu', 'kebersihan',
            'perbaikan', 'servis', 'service', 'perawatan', 'dus', 'cup', 'sedotan', 'kemasan', 'packaging', 'plastik', 'kresek',
            'kantong', 'stiker', 'sticker', 'promosi', 'iklan', 'banner', 'spanduk', 'seragam', 'apron', 'alat', 'peralatan',
            'wajan', 'kompor', 'etalase', 'gerobak', 'pajak', 'izin',
        ];
    }

    /** 'bahan_baku' atau 'operasional' (selalu terisi) */
    public static function tentukan(string $keterangan): string
    {
        $teks = mb_strtolower($keterangan);

        if (self::cocok($teks, self::kataOperasional())) {
            return 'operasional';
        }
        if (self::cocok($teks, self::kataBahanBaku())) {
            return 'bahan_baku';
        }

        return 'operasional';
    }

    /** Cocok kalau kata kunci diawali di awal kata (jadi "regulator" tidak kena kata "gula") */
    private static function cocok(string $teks, array $kata): bool
    {
        $pola = implode('|', array_map(fn ($k) => preg_quote($k, '/'), $kata));

        return (bool) preg_match('/(?<![\p{L}\p{N}])(?:' . $pola . ')/u', $teks);
    }
}
