<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Menu kategori Minuman tidak pakai exp (dibuat dadakan): batch exp-nya dihapus, stoknya tidak disentuh.
 * 2. Bumbu lebih awet: batch bumbu yang exp-nya masih memakai standar lama (14 hari) dijadikan 30 hari (1 bulan).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stok_batches')) {
            return;
        }

        $idMinuman = DB::table('menus')
            ->join('kategori_menus', 'kategori_menus.id', '=', 'menus.kategori_menu_id')
            ->where('kategori_menus.nama_kategori', 'like', '%minum%')
            ->pluck('menus.id');

        if ($idMinuman->isNotEmpty()) {
            DB::table('stok_batches')->where('tipe', 'menu')->whereIn('item_id', $idMinuman)->delete();
        }

        DB::table('stok_batches')
            ->where('tipe', 'bumbu')
            ->where('sisa', '>', 0)
            ->whereRaw('tanggal_exp = DATE_ADD(tanggal_masuk, INTERVAL 14 DAY)')
            ->update(['tanggal_exp' => DB::raw('DATE_ADD(tanggal_masuk, INTERVAL 30 DAY)')]);
    }

    public function down(): void
    {
        // tidak ada yang perlu dikembalikan
    }
};
