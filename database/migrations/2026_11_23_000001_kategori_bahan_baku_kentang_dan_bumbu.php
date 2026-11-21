<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kentang, bumbu, saus, dan bahan makanan sejenis harusnya kategori "Bahan Baku", bukan "Operasional".
 *
 * 1. Bahan di Stok Operasional (Kelola Bahan) yang namanya bahan makanan diubah ke Bahan Baku,
 *    jadi isi ulang / stok awal berikutnya otomatis tercatat sebagai Bahan Baku.
 * 2. Catatan Pengeluaran lama yang masih "Operasional":
 *      - catatan otomatis stok operasional ("Isi ulang ..." / "Stok awal ...") mengikuti kategori bahannya
 *      - catatan yang diketik manual dan deskripsinya menyebut bahan makanan diubah ke Bahan Baku
 * Gas, dus, cup, listrik, sewa, dan biaya lain TIDAK diubah. Aman dijalankan ulang.
 */
return new class extends Migration
{
    /** Kata kunci bahan makanan (tidak membedakan huruf besar/kecil) */
    private const KATA_BAHAN_BAKU = [
        'kentang', 'bumbu', 'saus', 'tabur', 'bubuk', 'keju', 'balado', 'mayo', 'mentai',
        'blackpaper', 'lava', 'jagung', 'matcha', 'thai tea', 'gula', 'susu',
    ];

    private static function bahanMakanan(?string $teks): bool
    {
        $teks = mb_strtolower((string) $teks);

        foreach (self::KATA_BAHAN_BAKU as $kata) {
            if (str_contains($teks, $kata)) {
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
                if ($b->kategori_pengeluaran !== 'bahan_baku' && self::bahanMakanan($b->nama)) {
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

            if ($target === null && self::bahanMakanan($p->deskripsi)) {
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
