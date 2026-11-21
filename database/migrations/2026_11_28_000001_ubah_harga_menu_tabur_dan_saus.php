<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Harga baru: varian tabur Rp 8.000, varian saus Rp 10.000.
 * (Tambahan saus di menu tabur jadi Rp 2.000 diatur di kode TransaksiController.)
 * Hanya mengubah harga jual menu. Harga modal, stok, dan transaksi lama tidak disentuh
 * (transaksi lama menyimpan harga saat itu sendiri).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Menu dengan bubuk tabur bawaan (tanpa saus bawaan)
        DB::table('menus')->whereNotNull('bubuk_bawaan_id')->whereNull('saus_bawaan_id')->update(['harga' => 8000, 'updated_at' => now()]);

        // Menu dengan saus bawaan (tanpa bubuk bawaan)
        DB::table('menus')->whereNotNull('saus_bawaan_id')->whereNull('bubuk_bawaan_id')->update(['harga' => 10000, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // harga lama tidak disimpan
    }
};
