<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembagian kategori Pengeluaran untuk 5 bahan bawaan:
 *   Gas, Dus kentang, Cup + tutup + sedotan -> Operasional
 *   Minyak, Es batu                         -> Bahan Baku
 * Catatan "Isi ulang ..." / "Stok awal ..." yang sudah ada ikut disesuaikan (dua arah).
 * Aman dijalankan ulang.
 */
return new class extends Migration
{
    private const KATEGORI = [
        'gas' => 'operasional',
        'minyak' => 'bahan_baku',
        'dus' => 'operasional',
        'cup' => 'operasional',
        'es' => 'bahan_baku',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('bahan_operasionals') || ! Schema::hasColumn('bahan_operasionals', 'kategori_pengeluaran')) {
            return;
        }

        foreach (self::KATEGORI as $kode => $kategori) {
            DB::table('bahan_operasionals')->where('kode', $kode)->update(['kategori_pengeluaran' => $kategori]);
        }

        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        $bahan = DB::table('bahan_operasionals')->get(['nama', 'kategori_pengeluaran']);

        $baris = DB::table('pengeluarans')
            ->whereIn('kategori', ['operasional', 'bahan_baku'])
            ->where(fn ($q) => $q->where('deskripsi', 'like', 'Isi ulang %')->orWhere('deskripsi', 'like', 'Stok awal %'))
            ->get(['id', 'deskripsi', 'kategori']);

        foreach ($baris as $b) {
            foreach ($bahan as $x) {
                if (str_starts_with($b->deskripsi, "Isi ulang {$x->nama}:") || str_starts_with($b->deskripsi, "Stok awal {$x->nama}:")) {
                    if ($b->kategori !== $x->kategori_pengeluaran) {
                        DB::table('pengeluarans')->where('id', $b->id)->update(['kategori' => $x->kategori_pengeluaran, 'updated_at' => now()]);
                    }
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
