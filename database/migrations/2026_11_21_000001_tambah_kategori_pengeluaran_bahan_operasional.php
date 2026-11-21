<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tiap bahan operasional punya kategori Pengeluaran sendiri (bisa diatur di Kelola Bahan):
 *  - Gas, Dus kentang, Cup + tutup + sedotan -> Operasional
 *  - Minyak, Es batu                          -> Bahan Baku
 * Catatan "Isi ulang ..." dan "Stok awal ..." yang sebelumnya tersimpan sebagai Operasional ikut dikoreksi.
 */
return new class extends Migration
{
    private const BAHAN_BAKU = ['minyak', 'es'];

    public function up(): void
    {
        if (! Schema::hasTable('bahan_operasionals')) {
            return;
        }

        if (! Schema::hasColumn('bahan_operasionals', 'kategori_pengeluaran')) {
            Schema::table('bahan_operasionals', function (Blueprint $table) {
                $table->string('kategori_pengeluaran', 20)->default('operasional')->after('harga_per_unit');
            });
        }

        DB::table('bahan_operasionals')->whereIn('kode', self::BAHAN_BAKU)->update(['kategori_pengeluaran' => 'bahan_baku']);

        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        $bahanBaku = DB::table('bahan_operasionals')->where('kategori_pengeluaran', 'bahan_baku')->pluck('nama');
        if ($bahanBaku->isEmpty()) {
            return;
        }

        $baris = DB::table('pengeluarans')
            ->where('kategori', 'operasional')
            ->where(fn ($q) => $q->where('deskripsi', 'like', 'Isi ulang %')->orWhere('deskripsi', 'like', 'Stok awal %'))
            ->get(['id', 'deskripsi']);

        foreach ($baris as $b) {
            foreach ($bahanBaku as $nama) {
                if (str_starts_with($b->deskripsi, "Isi ulang {$nama}:") || str_starts_with($b->deskripsi, "Stok awal {$nama}:")) {
                    DB::table('pengeluarans')->where('id', $b->id)->update(['kategori' => 'bahan_baku', 'updated_at' => now()]);
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        // tidak ada yang perlu dikembalikan
    }
};
