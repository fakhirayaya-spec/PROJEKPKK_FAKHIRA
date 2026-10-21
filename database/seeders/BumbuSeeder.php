<?php

namespace Database\Seeders;

/**
 * Saus & bumbu tabur beserta stoknya.
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class BumbuSeeder extends DumpSeeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'jenis' => 'saus', 'nama' => 'Saus Keju', 'stok' => 90, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-10-07 15:08:42'],
            ['id' => 2, 'jenis' => 'saus', 'nama' => 'Saus Mayo', 'stok' => 100, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 03:54:15'],
            ['id' => 3, 'jenis' => 'saus', 'nama' => 'Saus Blackpaper', 'stok' => 99, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 4, 'jenis' => 'saus', 'nama' => 'Saus Mentai', 'stok' => 100, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 03:54:15'],
            ['id' => 5, 'jenis' => 'saus', 'nama' => 'Saus Lava', 'stok' => 100, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 03:54:15'],
            ['id' => 6, 'jenis' => 'tabur', 'nama' => 'Bubuk Jagung', 'stok' => 100, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 03:54:15'],
            ['id' => 7, 'jenis' => 'tabur', 'nama' => 'Bubuk Pedas', 'stok' => 99, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 09:53:05'],
            ['id' => 8, 'jenis' => 'tabur', 'nama' => 'Bubuk Balado', 'stok' => 100, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-09-29 03:54:15'],
            ['id' => 9, 'jenis' => 'tabur', 'nama' => 'Bubuk Keju', 'stok' => 99, 'stok_minimum' => 20, 'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => '2026-09-29 03:54:15', 'updated_at' => '2026-10-07 15:08:42'],
        ];

        $this->upsertDump('bumbus', $rows, ['jenis', 'nama', 'stok', 'stok_minimum', 'satuan', 'status', 'created_at', 'updated_at']);
    }
}
