<?php

use App\Models\Bumbu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Bumbu::pastikanTabel(); // sudah aman dipanggil berulang
    }

    public function down(): void
    {
        Schema::dropIfExists('bumbus');
    }
};
