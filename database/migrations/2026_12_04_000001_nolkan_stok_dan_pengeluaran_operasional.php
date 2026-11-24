<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mulai coba dari awal:
 *  - Stok operasional (gas, minyak, dus, cup, es batu, dst.) semua kasir jadi 0. Cara kerjanya tidak berubah,
 *    admin mengisi lewat Isi Ulang.
 *  - Pengeluaran otomatis dari stok dihapus: "Stok awal ...", "Isi ulang ...", "Restock ..." (menu, bumbu, operasional).
 *    Pengeluaran yang diketik admin sendiri (listrik, sewa, dll) tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            if (Schema::hasTable('stok_operasionals')) {
                DB::table('stok_operasionals')->update(['stok' => 0, 'updated_at' => now()]);
            }

            if (Schema::hasTable('pengeluarans')) {
                DB::table('pengeluarans')
                    ->where(fn ($q) => $q
                        ->where('deskripsi', 'like', 'Stok awal %')
                        ->orWhere('deskripsi', 'like', 'Isi ulang %')
                        ->orWhere('deskripsi', 'like', 'Restock %'))
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        // data yang sudah dinolkan/dihapus tidak bisa dikembalikan
    }
};
