<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksis', 'uang_diterima')) {
                $table->unsignedInteger('uang_diterima')->nullable()->after('total');
            }
            if (! Schema::hasColumn('transaksis', 'kembalian')) {
                $table->unsignedInteger('kembalian')->nullable()->after('uang_diterima');
            }
            if (! Schema::hasColumn('transaksis', 'alasan_batal')) {
                $table->string('alasan_batal')->nullable()->after('status');
            }
            if (! Schema::hasColumn('transaksis', 'dibatalkan_oleh')) {
                $table->foreignId('dibatalkan_oleh')->nullable()->after('alasan_batal')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('transaksis', 'dibatalkan_pada')) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibatalkan_oleh');
            $table->dropColumn(['uang_diterima', 'kembalian', 'alasan_batal', 'dibatalkan_pada']);
        });
    }
};
