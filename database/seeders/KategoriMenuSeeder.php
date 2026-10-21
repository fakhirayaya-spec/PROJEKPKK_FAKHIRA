<?php

namespace Database\Seeders;

/**
 * Kategori menu.
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class KategoriMenuSeeder extends DumpSeeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'nama_kategori' => 'Kentang Goreng', 'created_at' => '2026-09-22 19:26:46', 'updated_at' => '2026-09-22 19:26:46'],
            ['id' => 2, 'nama_kategori' => 'minuman', 'created_at' => '2026-09-22 22:41:06', 'updated_at' => '2026-09-22 22:41:06'],
        ];

        $this->upsertDump('kategori_menus', $rows, ['nama_kategori', 'created_at', 'updated_at']);
    }
}
