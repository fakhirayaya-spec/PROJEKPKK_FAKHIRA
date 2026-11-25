<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan stok sekarang punya langkah konfirmasi kasir:
 * menunggu -> disetujui (admin) -> selesai (kasir konfirmasi barang diterima, stok baru masuk).
 * Pengajuan lama yang sudah "disetujui" stoknya sudah masuk, jadi langsung dijadikan "selesai".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permintaan_stoks')) {
            return;
        }

        if (! Schema::hasColumn('permintaan_stoks', 'dikonfirmasi_pada')) {
            Schema::table('permintaan_stoks', function (Blueprint $table) {
                $table->timestamp('dikonfirmasi_pada')->nullable()->after('ditangani_pada');
            });
        }

        DB::table('permintaan_stoks')
            ->where('status', 'disetujui')
            ->whereNull('dikonfirmasi_pada')
            ->update([
                'status' => 'selesai',
                'dikonfirmasi_pada' => DB::raw('COALESCE(ditangani_pada, updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('permintaan_stoks') && Schema::hasColumn('permintaan_stoks', 'dikonfirmasi_pada')) {
            DB::table('permintaan_stoks')->where('status', 'selesai')->update(['status' => 'disetujui']);
            Schema::table('permintaan_stoks', function (Blueprint $table) {
                $table->dropColumn('dikonfirmasi_pada');
            });
        }
    }
};
