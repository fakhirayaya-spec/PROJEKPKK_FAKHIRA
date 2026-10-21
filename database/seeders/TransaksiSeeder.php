<?php

namespace Database\Seeders;

/**
 * Transaksi + item transaksi (termasuk transaksi yang dibatalkan).
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class TransaksiSeeder extends DumpSeeder
{
    public function run(): void
    {
        $transaksis = [
            ['id' => 2, 'kode_transaksi' => 'TRX-260929-001', 'user_id' => 3, 'metode_bayar' => 'qris', 'total' => 33000, 'uang_diterima' => null, 'kembalian' => null, 'status' => 'selesai', 'alasan_batal' => null, 'dibatalkan_oleh' => null, 'dibatalkan_pada' => null, 'created_at' => '2026-09-29 09:53:05', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 3, 'kode_transaksi' => 'TRX-260930-001', 'user_id' => 3, 'metode_bayar' => 'cash', 'total' => 38000, 'uang_diterima' => 50000, 'kembalian' => 12000, 'status' => 'dibatalkan', 'alasan_batal' => 'salah pilih menu', 'dibatalkan_oleh' => 3, 'dibatalkan_pada' => '2026-09-30 01:46:48', 'created_at' => '2026-09-30 01:46:34', 'updated_at' => '2026-09-30 01:46:48'],
            ['id' => 4, 'kode_transaksi' => 'TRX-261002-001', 'user_id' => 3, 'metode_bayar' => 'cash', 'total' => 162000, 'uang_diterima' => 162000, 'kembalian' => 0, 'status' => 'selesai', 'alasan_batal' => null, 'dibatalkan_oleh' => null, 'dibatalkan_pada' => null, 'created_at' => '2026-10-02 06:52:12', 'updated_at' => '2026-10-02 06:52:12'],
            ['id' => 5, 'kode_transaksi' => 'TRX-261007-001', 'user_id' => 3, 'metode_bayar' => 'cash', 'total' => 30000, 'uang_diterima' => 50000, 'kembalian' => 20000, 'status' => 'selesai', 'alasan_batal' => null, 'dibatalkan_oleh' => null, 'dibatalkan_pada' => null, 'created_at' => '2026-10-07 15:08:42', 'updated_at' => '2026-10-07 15:08:42'],
        ];

        $items = [
            ['id' => 1, 'transaksi_id' => 2, 'menu_id' => 5, 'ukuran' => 'Jumbo', 'saus' => 'Saus Blackpaper', 'bubuk_tabur' => 'Bubuk Pedas', 'qty' => 1, 'harga_satuan' => 23000, 'modal_satuan' => null, 'subtotal' => 23000, 'created_at' => '2026-09-29 09:53:05', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 2, 'transaksi_id' => 2, 'menu_id' => 11, 'ukuran' => 'Jumbo', 'saus' => 'Tanpa Saus Tambahan', 'bubuk_tabur' => 'Tanpa Bubuk Tabur', 'qty' => 1, 'harga_satuan' => 10000, 'modal_satuan' => null, 'subtotal' => 10000, 'created_at' => '2026-09-29 09:53:05', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 3, 'transaksi_id' => 3, 'menu_id' => 4, 'ukuran' => 'Jumbo', 'saus' => 'Saus Keju', 'bubuk_tabur' => 'Bubuk Keju', 'qty' => 1, 'harga_satuan' => 23000, 'modal_satuan' => 12000, 'subtotal' => 23000, 'created_at' => '2026-09-30 01:46:34', 'updated_at' => '2026-09-30 01:46:34'],
            ['id' => 4, 'transaksi_id' => 3, 'menu_id' => 12, 'ukuran' => 'Jumbo', 'saus' => null, 'bubuk_tabur' => null, 'qty' => 1, 'harga_satuan' => 15000, 'modal_satuan' => 7000, 'subtotal' => 15000, 'created_at' => '2026-09-30 01:46:34', 'updated_at' => '2026-09-30 01:46:34'],
            ['id' => 5, 'transaksi_id' => 4, 'menu_id' => 6, 'ukuran' => 'Reguler', 'saus' => 'Saus Keju', 'bubuk_tabur' => 'Tanpa Bubuk Tabur', 'qty' => 9, 'harga_satuan' => 18000, 'modal_satuan' => 14000, 'subtotal' => 162000, 'created_at' => '2026-10-02 06:52:12', 'updated_at' => '2026-10-02 06:52:12'],
            ['id' => 6, 'transaksi_id' => 5, 'menu_id' => 4, 'ukuran' => 'Reguler', 'saus' => 'Saus Keju', 'bubuk_tabur' => 'Bubuk Keju', 'qty' => 1, 'harga_satuan' => 18000, 'modal_satuan' => 12000, 'subtotal' => 18000, 'created_at' => '2026-10-07 15:08:42', 'updated_at' => '2026-10-07 15:08:42'],
            ['id' => 7, 'transaksi_id' => 5, 'menu_id' => 10, 'ukuran' => 'Reguler', 'saus' => null, 'bubuk_tabur' => null, 'qty' => 1, 'harga_satuan' => 12000, 'modal_satuan' => 10000, 'subtotal' => 12000, 'created_at' => '2026-10-07 15:08:42', 'updated_at' => '2026-10-07 15:08:42'],
        ];

        $this->upsertDump('transaksis', $transaksis, ['kode_transaksi', 'user_id', 'metode_bayar', 'total', 'uang_diterima', 'kembalian', 'status', 'alasan_batal', 'dibatalkan_oleh', 'dibatalkan_pada', 'created_at', 'updated_at']);
        $this->upsertDump('transaksi_items', $items, ['transaksi_id', 'menu_id', 'ukuran', 'saus', 'bubuk_tabur', 'qty', 'harga_satuan', 'modal_satuan', 'subtotal', 'created_at', 'updated_at']);
    }
}
