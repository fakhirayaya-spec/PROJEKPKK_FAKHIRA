<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stok menu & bumbu milik tiap kasir. Kolom menus.stok & bumbus.stok tetap ada sebagai TOTAL semua kasir
        // (dipakai Dashboard & Laporan admin), dan selalu diperbarui bersama stok kasir.
        if (Schema::hasTable('stok_kasirs')) {
            return;
        }

        Schema::create('stok_kasirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipe', 10)->comment('menu | bumbu');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('stok')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'tipe', 'item_id']);
            $table->index(['tipe', 'item_id']);
        });

        // Batch exp sekarang dimiliki satu kasir
        if (Schema::hasTable('stok_batches') && ! Schema::hasColumn('stok_batches', 'user_id')) {
            Schema::table('stok_batches', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            });
        }

        // Riwayat pergerakan: kasir pemilik stok (user_id tetap = siapa yang melakukan)
        if (Schema::hasTable('pergerakan_stoks') && ! Schema::hasColumn('pergerakan_stoks', 'kasir_id')) {
            Schema::table('pergerakan_stoks', function (Blueprint $table) {
                $table->unsignedBigInteger('kasir_id')->nullable()->index();
            });
        }

        // Stok lama (satu angka untuk semua) dinolkan: semua kasir mulai dari 0 dan admin mengisi per kasir.
        $now = now();

        if (Schema::hasTable('menus')) {
            $ada = DB::table('menus')->where('stok', '>', 0)->get(['id', 'stok']);
            foreach ($ada as $menu) {
                DB::table('pergerakan_stoks')->insert([
                    'menu_id' => $menu->id, 'user_id' => null, 'jenis' => 'keluar', 'jumlah' => $menu->stok,
                    'keterangan' => 'Stok pusat dinolkan (stok dibagi per kasir)',
                    'tanggal' => $now->toDateString(), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            DB::table('menus')->update(['stok' => 0]);
        }

        if (Schema::hasTable('bumbus')) {
            DB::table('bumbus')->update(['stok' => 0]);
        }

        if (Schema::hasTable('stok_batches')) {
            DB::table('stok_batches')->whereNull('user_id')->where('sisa', '>', 0)->update([
                'dibuang' => DB::raw('dibuang + sisa'),
                'sisa' => 0,
                'dibuang_pada' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_kasirs');
    }
};
