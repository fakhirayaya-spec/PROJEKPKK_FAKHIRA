<?php

namespace Database\Seeders;

/**
 * Pengeluaran (kategori Operasional): stok awal menu x harga modal, dan stok awal bumbu.
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class PengeluaranSeeder extends DumpSeeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly tabur jagung: 20 pcs x Rp 12.000', 'jumlah' => 240000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 2, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly tabur pedas: 20 pcs x Rp 12.000', 'jumlah' => 240000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 3, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly tabur balado: 20 pcs x Rp 12.000', 'jumlah' => 240000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 4, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly tabur keju: 20 pcs x Rp 12.000', 'jumlah' => 240000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 5, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly saus blackpaper: 20 pcs x Rp 14.000', 'jumlah' => 280000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 6, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly saus keju: 20 pcs x Rp 14.000', 'jumlah' => 280000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 7, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly saus lava: 20 pcs x Rp 14.000', 'jumlah' => 280000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 8, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly saus mayo: 20 pcs x Rp 14.000', 'jumlah' => 280000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 9, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal curly saus mentai: 20 pcs x Rp 14.000', 'jumlah' => 280000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 10, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal matcha: 20 pcs x Rp 10.000', 'jumlah' => 200000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 11, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Es teh manis: 20 pcs x Rp 3.000', 'jumlah' => 60000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 12, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Thai tea: 20 pcs x Rp 7.000', 'jumlah' => 140000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 13, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Saus Keju: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 14, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Saus Mayo: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 15, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Saus Blackpaper: 99 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 99000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 16, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Saus Mentai: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 17, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Saus Lava: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 18, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Bubuk Jagung: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 19, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Bubuk Pedas: 99 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 99000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 20, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Bubuk Balado: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
            ['id' => 21, 'tanggal' => '2026-09-29', 'kategori' => 'operasional', 'deskripsi' => 'Stok awal Bubuk Keju: 100 porsi x Rp 1.000 (perkiraan harga)', 'jumlah' => 100000, 'user_id' => 1, 'created_at' => '2026-09-28 19:00:00', 'updated_at' => '2026-09-28 19:00:00'],
        ];

        $this->upsertDump('pengeluarans', $rows, ['tanggal', 'kategori', 'deskripsi', 'jumlah', 'user_id', 'created_at', 'updated_at']);
    }
}
