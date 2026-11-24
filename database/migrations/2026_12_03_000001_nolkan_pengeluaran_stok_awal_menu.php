<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stok menu & bumbu tiap kasir mulai dari 0 (admin yang mengisi satu per satu), jadi catatan
 * pengeluaran "Stok awal <menu>: 50 porsi x Rp ..." (Bahan Baku) dihapus supaya pengeluaran mulai dari 0.
 *
 * Yang TIDAK disentuh: pengeluaran stok operasional (gas, minyak, dus, cup, es batu), stok operasional,
 * pengeluaran yang diketik admin sendiri, dan stok menu/bumbu yang sudah diisi admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        DB::table('pengeluarans')
            ->where('kategori', 'bahan_baku')
            ->where('deskripsi', 'like', 'Stok awal %: % porsi x Rp %')
            ->delete();
    }

    public function down(): void
    {
        // catatan yang sudah dihapus tidak bisa dikembalikan
    }
};
