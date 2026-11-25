<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bumbu & saus dihargai per bungkus: harga_per_bungkus dan isi_per_bungkus (berapa kali pakai).
 * Bawaan: bubuk tabur Rp7.000 / 50 pakai, saus Rp14.000 / 25 pakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bumbus')) {
            return;
        }

        Schema::table('bumbus', function (Blueprint $table) {
            if (! Schema::hasColumn('bumbus', 'harga_per_bungkus')) {
                $table->unsignedInteger('harga_per_bungkus')->default(0)->after('satuan');
            }
            if (! Schema::hasColumn('bumbus', 'isi_per_bungkus')) {
                $table->unsignedInteger('isi_per_bungkus')->default(1)->after('satuan');
            }
        });

        DB::table('bumbus')->where('jenis', 'tabur')->where('harga_per_bungkus', 0)->update(['harga_per_bungkus' => 7000, 'isi_per_bungkus' => 50]);
        DB::table('bumbus')->where('jenis', 'saus')->where('harga_per_bungkus', 0)->update(['harga_per_bungkus' => 14000, 'isi_per_bungkus' => 25]);
    }

    public function down(): void
    {
        if (Schema::hasTable('bumbus')) {
            Schema::table('bumbus', function (Blueprint $table) {
                foreach (['harga_per_bungkus', 'isi_per_bungkus'] as $kolom) {
                    if (Schema::hasColumn('bumbus', $kolom)) {
                        $table->dropColumn($kolom);
                    }
                }
            });
        }
    }
};
