<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mulai jualan dari awal (seperti baru buka):
 *  - Riwayat transaksi, pre-order, pengajuan stok, riwayat pergerakan stok & riwayat stok operasional dihapus.
 *  - Total terjual menu = 0.
 *  - Stok menu & bumbu semua kasir = 0 (batch exp dibersihkan), stok operasional semua kasir = 0.
 *  - Pengeluaran otomatis dari stok dihapus. Pengeluaran yang diketik admin sendiri tidak disentuh.
 *  - Akun, menu, bumbu, bahan operasional, dan harga tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // urutan penting: anak dulu, baru induk
            foreach (['transaksi_items', 'transaksis', 'pre_order_items', 'pre_orders',
                      'permintaan_stoks', 'pergerakan_stoks', 'riwayat_stok_operasionals', 'stok_batches'] as $tabel) {
                if (Schema::hasTable($tabel)) {
                    DB::table($tabel)->delete();
                }
            }

            if (Schema::hasTable('menus')) {
                DB::table('menus')->update(['stok' => 0, 'total_terjual' => 0]);
            }
            if (Schema::hasTable('bumbus')) {
                DB::table('bumbus')->update(['stok' => 0]);
            }
            if (Schema::hasTable('stok_kasirs')) {
                DB::table('stok_kasirs')->update(['stok' => 0, 'updated_at' => now()]);
            }
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
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
