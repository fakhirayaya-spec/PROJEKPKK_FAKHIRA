<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            if (! Schema::hasColumn('menus', 'saus_bawaan_id')) {
                $table->foreignId('saus_bawaan_id')->nullable()->after('harga_modal')->constrained('bumbus')->nullOnDelete();
            }
            if (! Schema::hasColumn('menus', 'bubuk_bawaan_id')) {
                $table->foreignId('bubuk_bawaan_id')->nullable()->after('saus_bawaan_id')->constrained('bumbus')->nullOnDelete();
            }
        });

        // Isi otomatis dari nama menu: "curly saus mentai" -> Saus Mentai, "curly tabur jagung" -> Bubuk Jagung
        $bumbus = DB::table('bumbus')->get();
        foreach (DB::table('menus')->get() as $menu) {
            $nama = mb_strtolower($menu->nama_menu);
            $update = [];

            foreach ($bumbus as $b) {
                if ($b->jenis === 'saus') {
                    $kata = trim(preg_replace('/^saus\s+/i', '', mb_strtolower($b->nama)));
                    if ($kata !== '' && str_contains($nama, 'saus ' . $kata) && empty($update['saus_bawaan_id'])) {
                        $update['saus_bawaan_id'] = $b->id;
                    }
                } else {
                    $kata = trim(preg_replace('/^(bubuk|bumbu)\s+/i', '', mb_strtolower($b->nama)));
                    if ($kata !== '' && str_contains($nama, 'tabur ' . $kata) && empty($update['bubuk_bawaan_id'])) {
                        $update['bubuk_bawaan_id'] = $b->id;
                    }
                }
            }

            if ($update) {
                DB::table('menus')->where('id', $menu->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saus_bawaan_id');
            $table->dropConstrainedForeignId('bubuk_bawaan_id');
        });
    }
};
