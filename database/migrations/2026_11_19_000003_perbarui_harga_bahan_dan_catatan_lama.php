<?php

use App\Support\BackfillIsiUlang;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga sebenarnya dari pemilik usaha:
 *  - Gas (tabung pink)        Rp 70.000 per tabung
 *  - Minyak                   Rp 42.000 per kemasan
 *  - Dus kentang              Rp 50.000 per 100 pcs   -> Rp 500 per pcs
 *  - Cup + tutup + sedotan    Rp 75.000 per 100 set   -> Rp 750 per set
 *  - Es batu                  Rp 5.000 per kg         -> dihitung Rp 500 per porsi (sekitar 10 porsi per kg)
 *
 * Harga disimpan di Kelola Bahan, lalu catatan "(data lama)" di Pengeluaran dihitung ulang dengan harga ini.
 * Catatan isi ulang yang diketik sendiri biayanya (setelah update sebelumnya) tidak disentuh.
 */
return new class extends Migration
{
    private const HARGA = [
        'gas' => 70000,
        'minyak' => 42000,
        'dus' => 500,
        'cup' => 750,
        'es' => 500,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('bahan_operasionals') || ! Schema::hasTable('riwayat_stok_operasionals') || ! Schema::hasTable('pengeluarans')
            || ! Schema::hasColumn('bahan_operasionals', 'harga_per_unit')) {
            return;
        }

        foreach (self::HARGA as $kode => $harga) {
            DB::table('bahan_operasionals')->where('kode', $kode)->update(['harga_per_unit' => $harga]);
        }

        // Buang catatan lama yang dibuat dengan harga taksiran, lalu catat ulang dengan harga yang benar
        DB::table('pengeluarans')->where('deskripsi', 'like', 'Isi ulang %(data lama)')->delete();

        $bahan = DB::table('bahan_operasionals')->get()->keyBy('kode')->map(fn ($b) => (array) $b)->all();
        $kasir = DB::table('users')->pluck('name', 'id')->all();
        $riwayat = DB::table('riwayat_stok_operasionals')->where('jenis', 'isi_ulang')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $ada = DB::table('pengeluarans')->where('deskripsi', 'like', 'Isi ulang %')->get(['deskripsi', 'user_id', 'created_at'])->map(fn ($r) => (array) $r)->all();

        foreach (array_chunk(BackfillIsiUlang::hitung($riwayat, $bahan, $kasir, $ada), 100) as $potongan) {
            DB::table('pengeluarans')->insert($potongan);
        }
    }

    public function down(): void
    {
        // tidak ada yang perlu dikembalikan
    }
};
