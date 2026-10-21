<?php

namespace Database\Seeders;

/**
 * Pre-order + item pre-order.
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class PreOrderSeeder extends DumpSeeder
{
    public function run(): void
    {
        $preOrders = [
            ['id' => 1, 'kode_preorder' => 'PO-261002-001', 'user_id' => 3, 'nama_pelanggan' => 'dinda', 'no_whatsapp' => '098765432765', 'tanggal_ambil' => '2026-10-03', 'jam_ambil' => '09:00', 'catatan' => 'bungkus 10', 'status' => 'selesai', 'total' => 162000, 'dp' => 100000, 'transaksi_id' => 4, 'alasan_batal' => null, 'created_at' => '2026-10-02 01:40:37', 'updated_at' => '2026-10-02 06:52:12'],
            ['id' => 2, 'kode_preorder' => 'PO-261002-002', 'user_id' => 3, 'nama_pelanggan' => 'sebin', 'no_whatsapp' => '089506490585', 'tanggal_ambil' => '2026-10-03', 'jam_ambil' => '10:00', 'catatan' => 'bungkus buat acara keluarga', 'status' => 'diproses', 'total' => 115000, 'dp' => 50000, 'transaksi_id' => null, 'alasan_batal' => null, 'created_at' => '2026-10-02 07:09:33', 'updated_at' => '2026-10-07 15:09:16'],
        ];

        $items = [
            ['id' => 1, 'pre_order_id' => 1, 'menu_id' => 6, 'nama_menu' => 'curly saus keju', 'ukuran' => 'Reguler', 'saus' => 'Saus Keju', 'bubuk_tabur' => 'Tanpa Bubuk Tabur', 'qty' => 9, 'harga_satuan' => 18000, 'modal_satuan' => 14000, 'subtotal' => 162000, 'created_at' => '2026-10-02 01:40:37', 'updated_at' => '2026-10-02 01:40:37'],
            ['id' => 2, 'pre_order_id' => 2, 'menu_id' => 9, 'nama_menu' => 'curly saus mentai', 'ukuran' => 'Reguler', 'saus' => 'Saus Mentai', 'bubuk_tabur' => 'Tanpa Bubuk Tabur', 'qty' => 5, 'harga_satuan' => 18000, 'modal_satuan' => 14000, 'subtotal' => 90000, 'created_at' => '2026-10-02 07:09:33', 'updated_at' => '2026-10-02 07:09:33'],
            ['id' => 3, 'pre_order_id' => 2, 'menu_id' => 11, 'nama_menu' => 'Es teh manis', 'ukuran' => 'Reguler', 'saus' => null, 'bubuk_tabur' => null, 'qty' => 5, 'harga_satuan' => 5000, 'modal_satuan' => 3000, 'subtotal' => 25000, 'created_at' => '2026-10-02 07:09:33', 'updated_at' => '2026-10-02 07:09:33'],
        ];

        $this->upsertDump('pre_orders', $preOrders, ['kode_preorder', 'user_id', 'nama_pelanggan', 'no_whatsapp', 'tanggal_ambil', 'jam_ambil', 'catatan', 'status', 'total', 'dp', 'transaksi_id', 'alasan_batal', 'created_at', 'updated_at']);
        $this->upsertDump('pre_order_items', $items, ['pre_order_id', 'menu_id', 'nama_menu', 'ukuran', 'saus', 'bubuk_tabur', 'qty', 'harga_satuan', 'modal_satuan', 'subtotal', 'created_at', 'updated_at']);
    }
}
