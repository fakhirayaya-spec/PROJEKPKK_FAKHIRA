<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bahan_operasionals')) {
            return;
        }

        // Daftar bahan operasional (master). Admin bisa menambah/mengubah dari halaman Kelola Bahan.
        Schema::create('bahan_operasionals', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('kunci yang dipakai tabel stok & riwayat');
            $table->string('nama', 60)->unique();
            $table->string('satuan', 20)->default('pcs');
            $table->unsignedInteger('stok_awal')->default(0);
            $table->unsignedInteger('stok_menipis')->default(0);
            $table->string('dasar_pakai', 20)->default('kentang')->comment('kentang | minuman | semua_item | transaksi');
            $table->unsignedInteger('per_kelompok')->default(1)->comment('berkurang 1 untuk tiap sekian satuan dasar (dibulatkan ke atas)');
            $table->string('unit_isi', 20)->default('pcs')->comment('satuan yang diketik admin saat isi ulang');
            $table->unsignedInteger('isi_per_unit')->default(1)->comment('1 unit isi ulang = sekian stok');
            $table->string('gambar')->nullable();
            $table->string('ikon', 40)->default('bi-box-seam');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        $now = now();
        $awal = [
            ['gas',    'Gas',                   'order', 50,  10, 'kentang',  3, 'tabung',  50, 'bi-fire',          1],
            ['minyak', 'Minyak',                'order', 50,  10, 'kentang',  3, 'kemasan', 50, 'bi-droplet-half',  2],
            ['dus',    'Dus kentang',           'pcs',   200, 20, 'kentang',  1, 'pcs',     1,  'bi-box-seam',      3],
            ['cup',    'Cup + tutup + sedotan', 'set',   100, 10, 'minuman',  1, 'set',     1,  'bi-cup-straw',     4],
            ['es',     'Es batu',               'porsi', 30,  5,  'minuman',  1, 'porsi',   1,  'bi-snow',          5],
        ];

        foreach ($awal as [$kode, $nama, $satuan, $stokAwal, $menipis, $dasar, $kelompok, $unit, $isi, $ikon, $urutan]) {
            DB::table('bahan_operasionals')->insert([
                'kode' => $kode, 'nama' => $nama, 'satuan' => $satuan,
                'stok_awal' => $stokAwal, 'stok_menipis' => $menipis,
                'dasar_pakai' => $dasar, 'per_kelompok' => $kelompok,
                'unit_isi' => $unit, 'isi_per_unit' => $isi,
                'ikon' => $ikon, 'status' => 'aktif', 'urutan' => $urutan,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Gas & minyak sekarang 50 order. Stok kasir yang masih utuh (belum pernah dipakai / diisi ulang)
        // dari versi sebelumnya (30) ikut dinaikkan jadi 50. Yang sudah ada riwayatnya tidak disentuh.
        if (Schema::hasTable('stok_operasionals') && Schema::hasTable('riwayat_stok_operasionals')) {
            foreach (['gas', 'minyak'] as $kode) {
                DB::table('stok_operasionals')
                    ->where('bahan', $kode)
                    ->where('stok', 30)
                    ->whereNotExists(function ($q) use ($kode) {
                        $q->select(DB::raw(1))
                            ->from('riwayat_stok_operasionals')
                            ->whereColumn('riwayat_stok_operasionals.user_id', 'stok_operasionals.user_id')
                            ->where('riwayat_stok_operasionals.bahan', $kode);
                    })
                    ->update(['stok' => 50, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_operasionals');
    }
};
