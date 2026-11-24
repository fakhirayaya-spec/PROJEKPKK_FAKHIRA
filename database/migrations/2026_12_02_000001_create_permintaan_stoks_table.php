<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permintaan_stoks')) {
            return;
        }

        Schema::create('permintaan_stoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // kasir yang mengajukan
            $table->string('jenis', 20);                                       // operasional | menu | bumbu
            $table->unsignedBigInteger('item_id')->nullable();                 // id menu / bumbu
            $table->string('bahan_kode', 50)->nullable();                      // kode bahan operasional
            $table->string('nama_item', 150);                                  // nama saat diajukan
            $table->string('satuan', 30)->nullable();
            $table->unsignedInteger('jumlah');
            $table->string('catatan', 200)->nullable();                        // catatan kasir
            $table->string('status', 15)->default('menunggu');                 // menunggu | disetujui | ditolak | dibatalkan
            $table->unsignedInteger('jumlah_disetujui')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditangani_pada')->nullable();
            $table->string('catatan_admin', 200)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_stoks');
    }
};
