<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pergerakan_bumbus')) {
            return;
        }

        Schema::create('pergerakan_bumbus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bumbu_id')->nullable()->constrained('bumbus')->nullOnDelete();
            $table->string('nama', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kasir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->unsignedInteger('jumlah');
            $table->string('keterangan')->nullable();
            $table->date('tanggal');
            $table->timestamps();

            $table->index(['kasir_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergerakan_bumbus');
    }
};
