<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga modal (HPP) per porsi, dipakai untuk hitung laba di Laporan
        Schema::table('menus', function (Blueprint $table) {
            if (! Schema::hasColumn('menus', 'harga_modal')) {
                $table->unsignedInteger('harga_modal')->default(0)->after('harga');
            }
        });

        // Modal disimpan per item transaksi, jadi laba lama tidak berubah kalau modal menu diganti nanti
        Schema::table('transaksi_items', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_items', 'modal_satuan')) {
                $table->unsignedInteger('modal_satuan')->nullable()->after('harga_satuan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('harga_modal');
        });
        Schema::table('transaksi_items', function (Blueprint $table) {
            $table->dropColumn('modal_satuan');
        });
    }
};
