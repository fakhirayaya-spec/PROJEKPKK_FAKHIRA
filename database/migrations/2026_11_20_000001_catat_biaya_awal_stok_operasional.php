<?php

use App\Support\BackfillBiayaAwal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya stok awal operasional (gas, minyak, dus, cup, es batu, ...) yang sudah dibagikan ke tiap kasir
 * sebelum ini belum tercatat. Di sini dicatat ke Pengeluaran (kategori operasional, ditandai "(data lama)")
 * memakai harga per unit tiap bahan di Kelola Bahan. Stok awal kasir BARU ke depannya dicatat otomatis oleh sistem.
 *
 * Aman dijalankan ulang: catatan "Stok awal ..." yang sudah ada tidak dicatat dua kali.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['bahan_operasionals', 'stok_operasionals', 'pengeluarans'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                return;
            }
        }
        if (! Schema::hasColumn('bahan_operasionals', 'harga_per_unit')) {
            return;
        }

        $bahan = DB::table('bahan_operasionals')->get()->keyBy('kode')->map(fn ($b) => (array) $b)->all();
        $kasir = DB::table('users')->pluck('name', 'id')->all();
        $stok = DB::table('stok_operasionals')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $ada = DB::table('pengeluarans')->where('deskripsi', 'like', 'Stok awal %')->pluck('deskripsi')->all();

        foreach (array_chunk(BackfillBiayaAwal::hitung($stok, $bahan, $kasir, $ada), 100) as $potongan) {
            DB::table('pengeluarans')->insert($potongan);
        }
    }

    public function down(): void
    {
        // tidak ada yang perlu dikembalikan
    }
};
