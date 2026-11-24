<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dus kentang = kemasan, jadi kategorinya Operasional (bukan Bahan Baku).
 *  - Pengaturan bahan "dus" di Kelola Bahan diubah ke Operasional (berlaku untuk isi stok berikutnya).
 *  - Catatan Pengeluaran lama "Stok awal Dus ..." / "Isi ulang Dus ..." yang sempat jadi Bahan Baku dikembalikan ke Operasional.
 * Aman dijalankan ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bahan_operasionals') || ! Schema::hasColumn('bahan_operasionals', 'kategori_pengeluaran')) {
            return;
        }

        DB::table('bahan_operasionals')->where('kode', 'dus')->update(['kategori_pengeluaran' => 'operasional']);

        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        $nama = DB::table('bahan_operasionals')->where('kode', 'dus')->value('nama') ?: 'Dus kentang';

        DB::table('pengeluarans')
            ->where('kategori', 'bahan_baku')
            ->where(fn ($q) => $q
                ->where('deskripsi', 'like', "Stok awal {$nama}:%")
                ->orWhere('deskripsi', 'like', "Isi ulang {$nama}:%"))
            ->update(['kategori' => 'operasional', 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
