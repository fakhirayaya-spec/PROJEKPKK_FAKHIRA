<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sisa stok operasional per kasir (gas, minyak, dus, cup, es)
        if (! Schema::hasTable('stok_operasionals')) {
            Schema::create('stok_operasionals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('bahan', 20);
                $table->unsignedInteger('stok')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'bahan']);
            });
        }

        // Riwayat pemakaian / pengembalian / isi ulang
        if (! Schema::hasTable('riwayat_stok_operasionals')) {
            Schema::create('riwayat_stok_operasionals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('kasir pemilik stok');
                $table->string('bahan', 20);
                $table->string('jenis', 20)->comment('pakai | kembali | isi_ulang');
                $table->unsignedInteger('jumlah');
                $table->unsignedInteger('saldo')->comment('sisa stok setelah kejadian ini');
                $table->unsignedBigInteger('transaksi_id')->nullable()->index();
                $table->unsignedBigInteger('dilakukan_oleh')->nullable()->comment('user yang melakukan (admin saat isi ulang)');
                $table->string('keterangan')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_stok_operasionals');
        Schema::dropIfExists('stok_operasionals');
    }
};
