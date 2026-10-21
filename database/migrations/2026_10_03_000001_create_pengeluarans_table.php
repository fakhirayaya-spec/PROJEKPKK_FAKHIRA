<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('kategori', ['bahan_baku', 'operasional', 'lainnya'])->default('operasional');
            $table->string('deskripsi');
            $table->unsignedInteger('jumlah');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('admin yang mencatat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
