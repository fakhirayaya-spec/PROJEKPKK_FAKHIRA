<?php

namespace Database\Seeders;

/**
 * Riwayat pergerakan stok menu.
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class PergerakanStokSeeder extends DumpSeeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'menu_id' => 2, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 10, 'keterangan' => 'dari supplier', 'tanggal' => '2026-09-25', 'created_at' => '2026-09-24 21:15:14', 'updated_at' => '2026-09-24 21:15:14'],
            ['id' => 2, 'menu_id' => 2, 'user_id' => 2, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-260925-001', 'tanggal' => '2026-09-25', 'created_at' => '2026-09-24 23:23:08', 'updated_at' => '2026-09-24 23:23:08'],
            ['id' => 3, 'menu_id' => 2, 'user_id' => 2, 'jenis' => 'keluar', 'jumlah' => 2, 'keterangan' => 'Transaksi TRX-260925-001', 'tanggal' => '2026-09-25', 'created_at' => '2026-09-24 23:23:08', 'updated_at' => '2026-09-24 23:23:08'],
            ['id' => 4, 'menu_id' => 10, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:56:46', 'updated_at' => '2026-09-28 18:56:46'],
            ['id' => 5, 'menu_id' => 12, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:57:02', 'updated_at' => '2026-09-28 18:57:02'],
            ['id' => 6, 'menu_id' => 5, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:57:15', 'updated_at' => '2026-09-28 18:57:15'],
            ['id' => 7, 'menu_id' => 6, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:57:24', 'updated_at' => '2026-09-28 18:57:24'],
            ['id' => 8, 'menu_id' => 7, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:57:35', 'updated_at' => '2026-09-28 18:57:35'],
            ['id' => 9, 'menu_id' => 8, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:57:46', 'updated_at' => '2026-09-28 18:57:46'],
            ['id' => 10, 'menu_id' => 9, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:57:55', 'updated_at' => '2026-09-28 18:57:55'],
            ['id' => 11, 'menu_id' => 3, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:58:05', 'updated_at' => '2026-09-28 18:58:05'],
            ['id' => 12, 'menu_id' => 1, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:58:16', 'updated_at' => '2026-09-28 18:58:16'],
            ['id' => 13, 'menu_id' => 4, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:58:25', 'updated_at' => '2026-09-28 18:58:25'],
            ['id' => 14, 'menu_id' => 2, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 13, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:58:37', 'updated_at' => '2026-09-28 18:58:37'],
            ['id' => 15, 'menu_id' => 11, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 20, 'keterangan' => 'barang dari supplier', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-28 18:58:47', 'updated_at' => '2026-09-28 18:58:47'],
            ['id' => 16, 'menu_id' => 5, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-260929-001', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-29 09:53:05', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 17, 'menu_id' => 11, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-260929-001', 'tanggal' => '2026-09-29', 'created_at' => '2026-09-29 09:53:05', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 18, 'menu_id' => 4, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-260930-001', 'tanggal' => '2026-09-30', 'created_at' => '2026-09-30 01:46:34', 'updated_at' => '2026-09-30 01:46:34'],
            ['id' => 19, 'menu_id' => 12, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-260930-001', 'tanggal' => '2026-09-30', 'created_at' => '2026-09-30 01:46:34', 'updated_at' => '2026-09-30 01:46:34'],
            ['id' => 20, 'menu_id' => 4, 'user_id' => 3, 'jenis' => 'masuk', 'jumlah' => 1, 'keterangan' => 'Batal TRX-260930-001', 'tanggal' => '2026-09-30', 'created_at' => '2026-09-30 01:46:48', 'updated_at' => '2026-09-30 01:46:48'],
            ['id' => 21, 'menu_id' => 12, 'user_id' => 3, 'jenis' => 'masuk', 'jumlah' => 1, 'keterangan' => 'Batal TRX-260930-001', 'tanggal' => '2026-09-30', 'created_at' => '2026-09-30 01:46:48', 'updated_at' => '2026-09-30 01:46:48'],
            ['id' => 22, 'menu_id' => 11, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 1, 'keterangan' => 'Restock manual', 'tanggal' => '2026-10-02', 'created_at' => '2026-10-02 06:48:16', 'updated_at' => '2026-10-02 06:48:16'],
            ['id' => 23, 'menu_id' => 5, 'user_id' => 1, 'jenis' => 'masuk', 'jumlah' => 1, 'keterangan' => 'Restock manual', 'tanggal' => '2026-10-02', 'created_at' => '2026-10-02 06:48:26', 'updated_at' => '2026-10-02 06:48:26'],
            ['id' => 24, 'menu_id' => 6, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 9, 'keterangan' => 'Pre-order PO-261002-001 (TRX-261002-001)', 'tanggal' => '2026-10-02', 'created_at' => '2026-10-02 06:52:12', 'updated_at' => '2026-10-02 06:52:12'],
            ['id' => 25, 'menu_id' => 4, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-261007-001', 'tanggal' => '2026-10-07', 'created_at' => '2026-10-07 15:08:42', 'updated_at' => '2026-10-07 15:08:42'],
            ['id' => 26, 'menu_id' => 10, 'user_id' => 3, 'jenis' => 'keluar', 'jumlah' => 1, 'keterangan' => 'Transaksi TRX-261007-001', 'tanggal' => '2026-10-07', 'created_at' => '2026-10-07 15:08:42', 'updated_at' => '2026-10-07 15:08:42'],
        ];

        $this->upsertDump('pergerakan_stoks', $rows, ['menu_id', 'user_id', 'jenis', 'jumlah', 'keterangan', 'tanggal', 'created_at', 'updated_at']);
    }
}
