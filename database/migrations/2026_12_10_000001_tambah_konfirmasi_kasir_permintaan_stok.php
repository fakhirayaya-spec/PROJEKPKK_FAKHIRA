<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_stoks', function (Blueprint $table) {
            if (! Schema::hasColumn('permintaan_stoks', 'biaya')) {
                $table->unsignedBigInteger('biaya')->default(0)->after('jumlah_disetujui');   // biaya yang diisi admin saat menyetujui
            }
            if (! Schema::hasColumn('permintaan_stoks', 'dikonfirmasi_pada')) {
                $table->timestamp('dikonfirmasi_pada')->nullable()->after('ditangani_pada');  // kasir menekan "terima"
            }
        });

        // Pengajuan lama berstatus "disetujui" stoknya SUDAH ditambahkan (alur lama),
        // jadi langsung dianggap berhasil supaya tidak minta konfirmasi dan tidak menambah stok dua kali.
        DB::table('permintaan_stoks')->where('status', 'disetujui')->update([
            'status' => 'diterima',
            'dikonfirmasi_pada' => DB::raw('ditangani_pada'),
        ]);
    }

    public function down(): void
    {
        Schema::table('permintaan_stoks', function (Blueprint $table) {
            $table->dropColumn(['biaya', 'dikonfirmasi_pada']);
        });
    }
};
