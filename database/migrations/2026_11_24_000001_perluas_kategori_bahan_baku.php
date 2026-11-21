<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kentang, bumbu, saus, dan bahan makanan sejenis harusnya "Bahan Baku", bukan "Operasional".
 * Versi lebih lengkap dari pengecekan sebelumnya (kata kunci lebih banyak, dan tidak salah kena kata
 * yang cuma mirip, misalnya "regulator" bukan "gula"). Aman dijalankan ulang.
 *
 *  1. Bahan di Stok Operasional yang namanya bahan makanan -> Bahan Baku.
 *  2. Catatan Pengeluaran yang masih "Operasional":
 *       - catatan otomatis ("Isi ulang ..." / "Stok awal ...") mengikuti kategori bahannya
 *       - catatan manual yang menyebut bahan makanan -> Bahan Baku
 *     kecuali menyebut biaya yang jelas operasional (gas, listrik, sewa, gaji, dst).
 */
return new class extends Migration
{
    private const BAHAN_MAKANAN = [
        'kentang', 'bumbu', 'saus', 'tabur', 'bubuk', 'keju', 'mozzarella', 'balado', 'mayo', 'mentai',
        'blackpaper', 'lava', 'jagung', 'matcha', 'thai tea', 'gula', 'susu', 'tepung', 'telur', 'bawang',
        'cabai', 'cabe', 'garam', 'merica', 'lada', 'penyedap', 'mentega', 'margarin', 'kecap', 'sambal',
        'seasoning', 'barbeque', 'bbq', 'rumput laut', 'cokelat', 'coklat', 'sirup', 'teh', 'kopi',
        'ayam', 'sosis', 'daging',
    ];

    /** Biaya yang jelas operasional: kalau disebut, catatan manual dibiarkan Operasional */
    private const JELAS_OPERASIONAL = [
        'gas', 'regulator', 'listrik', 'sewa', 'gaji', 'bensin', 'parkir', 'wifi', 'internet', 'pulsa',
        'token', 'galon', 'servis', 'service', 'perbaikan', 'kompor', 'wajan',
    ];

    /** Cocok kalau kata kunci diawali di awal kata (jadi "regulator" tidak kena kata "gula") */
    private static function mengandung(string $teks, array $daftar): bool
    {
        $teks = mb_strtolower($teks);

        foreach ($daftar as $kata) {
            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($kata, '/') . '/u', $teks)) {
                return true;
            }
        }

        return false;
    }

    public function up(): void
    {
        $punyaBahan = Schema::hasTable('bahan_operasionals') && Schema::hasColumn('bahan_operasionals', 'kategori_pengeluaran');

        if ($punyaBahan) {
            foreach (DB::table('bahan_operasionals')->get(['id', 'nama', 'kategori_pengeluaran']) as $b) {
                if ($b->kategori_pengeluaran !== 'bahan_baku'
                    && self::mengandung($b->nama, self::BAHAN_MAKANAN)
                    && ! self::mengandung($b->nama, self::JELAS_OPERASIONAL)) {
                    DB::table('bahan_operasionals')->where('id', $b->id)->update(['kategori_pengeluaran' => 'bahan_baku']);
                }
            }
        }

        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        $bahan = $punyaBahan ? DB::table('bahan_operasionals')->get(['nama', 'kategori_pengeluaran']) : collect();

        foreach (DB::table('pengeluarans')->where('kategori', 'operasional')->get(['id', 'deskripsi']) as $p) {
            $target = null;

            foreach ($bahan as $x) {
                if (str_starts_with($p->deskripsi, "Isi ulang {$x->nama}:") || str_starts_with($p->deskripsi, "Stok awal {$x->nama}:")) {
                    $target = $x->kategori_pengeluaran;
                    break;
                }
            }

            if ($target === null
                && self::mengandung($p->deskripsi, self::BAHAN_MAKANAN)
                && ! self::mengandung($p->deskripsi, self::JELAS_OPERASIONAL)) {
                $target = 'bahan_baku';
            }

            if ($target === 'bahan_baku') {
                DB::table('pengeluarans')->where('id', $p->id)->update(['kategori' => 'bahan_baku', 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // tidak ada yang perlu dikembalikan
    }
};
