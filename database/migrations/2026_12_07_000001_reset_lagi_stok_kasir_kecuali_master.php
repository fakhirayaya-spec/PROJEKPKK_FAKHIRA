<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reset lagi setelah masa coba-coba (seperti baru buka):
 *  - Riwayat transaksi, pre-order, pengajuan stok, riwayat stok menu & operasional dihapus. Total terjual = 0.
 *  - Stok menu, bumbu, dan operasional SEMUA kasir = 0 (batch exp dibersihkan).
 *  - Pengeluaran otomatis dari stok dihapus ("Stok awal", "Isi stok", "Isi ulang", "Restock").
 *    Pengeluaran yang diketik admin sendiri tidak disentuh.
 *  - Stok Master Menu & Master Bumbu TETAP 50.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (['transaksi_items', 'transaksis', 'pre_order_items', 'pre_orders',
                      'permintaan_stoks', 'pergerakan_stoks', 'riwayat_stok_operasionals', 'stok_batches'] as $tabel) {
                if (Schema::hasTable($tabel)) {
                    DB::table($tabel)->delete();
                }
            }

            if (Schema::hasTable('stok_kasirs')) {
                DB::table('stok_kasirs')->update(['stok' => 0, 'updated_at' => now()]);
            }
            if (Schema::hasTable('stok_operasionals')) {
                DB::table('stok_operasionals')->update(['stok' => 0, 'updated_at' => now()]);
            }

            // Master tetap 50
            if (Schema::hasTable('menus')) {
                DB::table('menus')->update(['stok' => 50, 'total_terjual' => 0]);
            }
            if (Schema::hasTable('bumbus')) {
                DB::table('bumbus')->update(['stok' => 50]);
            }

            if (Schema::hasTable('pengeluarans')) {
                DB::table('pengeluarans')
                    ->where(fn ($q) => $q
                        ->where('deskripsi', 'like', 'Stok awal %')
                        ->orWhere('deskripsi', 'like', 'Isi stok %')
                        ->orWhere('deskripsi', 'like', 'Isi ulang %')
                        ->orWhere('deskripsi', 'like', 'Restock %'))
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
