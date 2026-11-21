<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batch stok: setiap kali stok masuk dicatat sebagai 1 batch dengan tanggal exp sendiri.
 * Stok yang sudah ada sebelum fitur ini dipasang dianggap 1 batch yang exp 14 hari (2 minggu) dari hari migrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stok_batches')) {
            Schema::create('stok_batches', function (Blueprint $table) {
                $table->id();
                $table->enum('tipe', ['menu', 'bumbu']);
                $table->unsignedBigInteger('item_id');
                $table->unsignedInteger('jumlah_awal');
                $table->unsignedInteger('sisa');
                $table->unsignedInteger('dibuang')->default(0);          // jumlah yang dibuang karena kadaluarsa
                $table->date('tanggal_masuk');
                $table->date('tanggal_exp');
                $table->timestamp('dibuang_pada')->nullable();
                $table->string('keterangan')->nullable();
                $table->timestamps();

                $table->index(['tipe', 'item_id', 'tanggal_exp']);
            });
        }

        $now = now();
        $masuk = today()->toDateString();
        $exp = today()->addDays(14)->toDateString();

        $baris = function (string $tipe, $item) use ($now, $masuk, $exp) {
            return [
                'tipe' => $tipe, 'item_id' => $item->id,
                'jumlah_awal' => $item->stok, 'sisa' => $item->stok, 'dibuang' => 0,
                'tanggal_masuk' => $masuk, 'tanggal_exp' => $exp,
                'keterangan' => 'Stok awal saat fitur tanggal exp dipasang',
                'created_at' => $now, 'updated_at' => $now,
            ];
        };

        foreach (DB::table('menus')->where('stok', '>', 0)->get(['id', 'stok']) as $m) {
            if (! DB::table('stok_batches')->where('tipe', 'menu')->where('item_id', $m->id)->exists()) {
                DB::table('stok_batches')->insert($baris('menu', $m));
            }
        }

        if (Schema::hasTable('bumbus')) {
            foreach (DB::table('bumbus')->where('stok', '>', 0)->get(['id', 'stok']) as $b) {
                if (! DB::table('stok_batches')->where('tipe', 'bumbu')->where('item_id', $b->id)->exists()) {
                    DB::table('stok_batches')->insert($baris('bumbu', $b));
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_batches');
    }
};
