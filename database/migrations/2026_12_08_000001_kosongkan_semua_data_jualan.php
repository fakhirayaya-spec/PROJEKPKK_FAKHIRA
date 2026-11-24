<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kembali ke kondisi awal (seperti baru buka), yang tersisa hanya data master:
 *  - Semua transaksi, pre-order, pengajuan stok, riwayat stok menu & operasional dihapus. Total terjual = 0.
 *  - Stok menu, bumbu, dan operasional SEMUA kasir = 0 (batch exp dibersihkan).
 *  - SEMUA pengeluaran dihapus (karena belum ada stok masuk, belum ada pengeluaran).
 *  - Stok di Master Menu & Master Bumbu tetap 50 (stok standar), sama seperti reset sebelumnya.
 *  - Menu & kategori "Sandwich" dibuang kalau masih tersisa.
 *  - Akun admin/kasir, menu, bumbu, bahan operasional, dan harga tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // urutan penting: anak dulu, baru induk
            foreach (['transaksi_items', 'transaksis', 'pre_order_items', 'pre_orders',
                      'permintaan_stoks', 'pergerakan_stoks', 'riwayat_stok_operasionals',
                      'stok_batches', 'pengeluarans'] as $tabel) {
                if (Schema::hasTable($tabel)) {
                    DB::table($tabel)->delete();
                }
            }

            // Sisa menu/kategori Sandwich (kalau masih ada) ikut dibersihkan
            if (Schema::hasTable('menus')) {
                $idMenu = DB::table('menus')->where('nama_menu', 'like', '%sandwich%')->pluck('id')->all();
                if ($idMenu) {
                    if (Schema::hasTable('stok_kasirs')) {
                        DB::table('stok_kasirs')->where('tipe', 'menu')->whereIn('item_id', $idMenu)->delete();
                    }
                    DB::table('menus')->whereIn('id', $idMenu)->delete();
                }
            }
            if (Schema::hasTable('kategori_menus') && Schema::hasTable('menus')) {
                $idKategori = DB::table('kategori_menus')->where('nama_kategori', 'like', '%sandwich%')->pluck('id')->all();
                if ($idKategori && ! DB::table('menus')->whereIn('kategori_menu_id', $idKategori)->exists()) {
                    DB::table('kategori_menus')->whereIn('id', $idKategori)->delete();
                }
            }

            // Stok semua kasir 0
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
        });
    }

    public function down(): void
    {
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
