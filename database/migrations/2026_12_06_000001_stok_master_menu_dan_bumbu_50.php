<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stok di Master Menu & Master Bumbu diisi 50 semua (stok standar).
 * Tombol "Isi Semua Stok" di Manajemen Stok / Bumbu membuat stok kasir yang dipilih jadi 50 juga.
 * Setelah ada kasir yang diisi, angka di Master otomatis jadi jumlah stok semua kasir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->update(['stok' => 50]);
        }
        if (Schema::hasTable('bumbus')) {
            DB::table('bumbus')->update(['stok' => 50]);
        }
    }

    public function down(): void
    {
    }
};
