<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bahan operasional bisa dibatasi untuk satu ukuran saja (Reguler / Jumbo).
 * Kosong (null) = berlaku untuk semua ukuran, seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bahan_operasionals') && ! Schema::hasColumn('bahan_operasionals', 'ukuran')) {
            Schema::table('bahan_operasionals', function (Blueprint $table) {
                $table->string('ukuran', 20)->nullable()->after('dasar_pakai');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bahan_operasionals') && Schema::hasColumn('bahan_operasionals', 'ukuran')) {
            Schema::table('bahan_operasionals', function (Blueprint $table) {
                $table->dropColumn('ukuran');
            });
        }
    }
};
