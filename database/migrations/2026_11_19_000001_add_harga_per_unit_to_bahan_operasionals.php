<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harga beli per satuan isi ulang bahan operasional (mis. 1 tabung gas = Rp 22.000).
 * Dipakai untuk mengisi otomatis biaya saat admin isi ulang stok; biaya itu otomatis masuk ke Pengeluaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bahan_operasionals') && ! Schema::hasColumn('bahan_operasionals', 'harga_per_unit')) {
            Schema::table('bahan_operasionals', function (Blueprint $table) {
                $table->unsignedInteger('harga_per_unit')->default(0)->after('isi_per_unit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bahan_operasionals', 'harga_per_unit')) {
            Schema::table('bahan_operasionals', function (Blueprint $table) {
                $table->dropColumn('harga_per_unit');
            });
        }
    }
};
