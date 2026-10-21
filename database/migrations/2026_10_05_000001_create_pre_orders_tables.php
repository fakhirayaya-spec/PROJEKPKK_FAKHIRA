<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pre_orders')) {
            Schema::create('pre_orders', function (Blueprint $table) {
                $table->id();
                $table->string('kode_preorder')->unique();
                $table->foreignId('user_id')->constrained('users');          // yang mencatat pesanan
                $table->string('nama_pelanggan', 100);
                $table->string('no_whatsapp', 20)->nullable();
                $table->date('tanggal_ambil');
                $table->string('jam_ambil', 5)->nullable();                    // format HH:MM
                $table->text('catatan')->nullable();
                $table->enum('status', ['baru', 'diproses', 'siap', 'selesai', 'dibatalkan'])->default('baru');
                $table->unsignedInteger('total')->default(0);
                $table->unsignedInteger('dp')->default(0);                     // uang muka yang sudah dibayar
                $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete(); // terisi saat pre-order diselesaikan
                $table->string('alasan_batal')->nullable();
                $table->timestamps();

                $table->index(['status', 'tanggal_ambil']);
            });
        }

        if (! Schema::hasTable('pre_order_items')) {
            Schema::create('pre_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pre_order_id')->constrained('pre_orders')->cascadeOnDelete();
                $table->foreignId('menu_id')->nullable()->constrained('menus')->nullOnDelete();
                $table->string('nama_menu');                                    // salinan nama, supaya tetap terbaca kalau menu dihapus
                $table->string('ukuran', 20);
                $table->string('saus')->nullable();
                $table->string('bubuk_tabur')->nullable();
                $table->unsignedInteger('qty');
                $table->unsignedInteger('harga_satuan');
                $table->unsignedInteger('modal_satuan')->default(0);
                $table->unsignedInteger('subtotal');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_order_items');
        Schema::dropIfExists('pre_orders');
    }
};
