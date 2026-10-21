<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('kode_menu')->unique();
            $table->foreignId('kategori_menu_id')->nullable()
                ->constrained('kategori_menus')->nullOnDelete();
            $table->string('nama_menu');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->string('gambar')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->unsignedInteger('total_terjual')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
