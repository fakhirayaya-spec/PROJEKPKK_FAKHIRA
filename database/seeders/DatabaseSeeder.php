<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database sama persis dengan db_potato.sql (akun, kategori, bumbu, menu, stok,
     * transaksi, pre-order, dan pengeluaran).
     *
     * Urutan penting karena ada foreign key.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            KategoriMenuSeeder::class,
            BumbuSeeder::class,
            MenuSeeder::class,
            PergerakanStokSeeder::class,
            TransaksiSeeder::class,
            PreOrderSeeder::class,
            PengeluaranSeeder::class,
        ]);
    }
}
