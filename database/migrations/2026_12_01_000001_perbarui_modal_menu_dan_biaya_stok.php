<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga modal baru per menu, lalu biaya "Stok awal" menu di Pengeluaran dihitung ulang (50 porsi x modal).
 *  - varian tabur : modal Rp 6.000
 *  - varian saus  : modal Rp 8.000
 *  - matcha       : modal Rp 10.000
 *  - es teh       : modal Rp 3.000
 *  - thai tea     : modal Rp 7.000
 * Yang diubah hanya harga modal menu dan catatan "Stok awal <menu>: 50 porsi x Rp ..." di Pengeluaran
 * (kategori Bahan Baku). Harga jual, stok, transaksi lama, dan pengeluaran lain tidak disentuh.
 * Transaksi lama menyimpan modalnya sendiri, jadi laporan laba lama tidak bergeser.
 */
return new class extends Migration
{
    private const STOK_AWAL = 50;

    public function up(): void
    {
        DB::transaction(function () {
            // 1) Harga modal
            DB::table('menus')->whereNotNull('bubuk_bawaan_id')->whereNull('saus_bawaan_id')->update(['harga_modal' => 6000]);
            DB::table('menus')->whereNotNull('saus_bawaan_id')->whereNull('bubuk_bawaan_id')->update(['harga_modal' => 8000]);
            DB::table('menus')->whereRaw('LOWER(nama_menu) = ?', ['matcha'])->update(['harga_modal' => 10000]);
            DB::table('menus')->whereRaw('LOWER(nama_menu) LIKE ?', ['es teh%'])->update(['harga_modal' => 3000]);
            DB::table('menus')->whereRaw('LOWER(nama_menu) LIKE ?', ['thai tea%'])->update(['harga_modal' => 7000]);

            if (! Schema::hasTable('pengeluarans')) {
                return;
            }

            // 2) Catatan biaya stok awal MENU yang lama dibuang (bentuknya "Stok awal <menu>: N porsi x Rp ...")
            DB::table('pengeluarans')->where('deskripsi', 'like', 'Stok awal %: % porsi x Rp %')->delete();

            // 3) Dicatat ulang: 50 x harga modal, kategori Bahan Baku
            $baris = [];
            foreach (DB::table('menus')->orderBy('id')->get(['nama_menu', 'harga_modal']) as $menu) {
                $modal = (int) $menu->harga_modal;
                if ($modal <= 0) {
                    continue;
                }
                $baris[] = [
                    'tanggal' => today()->toDateString(),
                    'kategori' => 'bahan_baku',
                    'deskripsi' => mb_substr('Stok awal ' . $menu->nama_menu . ': ' . self::STOK_AWAL . ' porsi x Rp ' . number_format($modal, 0, ',', '.'), 0, 150),
                    'jumlah' => self::STOK_AWAL * $modal,
                    'user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($baris, 100) as $potongan) {
                DB::table('pengeluarans')->insert($potongan);
            }
        });
    }

    public function down(): void
    {
        // modal dan catatan lama tidak disimpan
    }
};
