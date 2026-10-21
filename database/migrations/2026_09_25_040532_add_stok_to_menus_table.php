<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedInteger('stok')->default(0)->after('total_terjual');
            $table->unsignedInteger('stok_minimum')->default(5)->after('stok');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['stok', 'stok_minimum']);
        });
    }
};
