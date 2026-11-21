<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sistem PO baru:
 *  - pre_orders.stok_dipotong : penanda stok sudah dipotong saat pesanan di-"Proses"
 *  - transaksis.jenis         : penjualan (biasa) | dp (uang muka PO) | pelunasan (sisa bayar PO)
 *  - transaksis.pre_order_id  : PO asal DP / pelunasan
 * Dengan begini pemasukan di Dashboard & Laporan otomatis ikut DP (saat DP diterima) lalu sisanya (saat pelunasan).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pre_orders', 'stok_dipotong')) {
            Schema::table('pre_orders', function (Blueprint $table) {
                $table->boolean('stok_dipotong')->default(false)->after('status');
            });
        }

        if (! Schema::hasColumn('transaksis', 'jenis')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->enum('jenis', ['penjualan', 'dp', 'pelunasan'])->default('penjualan')->after('kode_transaksi');
            });
        }
        if (! Schema::hasColumn('transaksis', 'pre_order_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('pre_order_id')->nullable()->after('jenis')
                    ->constrained('pre_orders')->nullOnDelete();
            });
        }

        // PO aktif yang DP-nya sudah tercatat di PO tapi belum tercatat sebagai pemasukan: catat sekarang
        // (tanggalnya mengikuti tanggal PO dibuat; metode dianggap cash karena dulu tidak dicatat).
        $po = DB::table('pre_orders')->whereIn('status', ['baru', 'diproses', 'siap'])->where('dp', '>', 0)->get();
        foreach ($po as $p) {
            $sudahAda = DB::table('transaksis')->where('pre_order_id', $p->id)->where('jenis', 'dp')->exists();
            if ($sudahAda) {
                continue;
            }
            DB::table('transaksis')->insert([
                'kode_transaksi' => 'DP-' . $p->kode_preorder . '-1',
                'jenis' => 'dp',
                'pre_order_id' => $p->id,
                'user_id' => $p->user_id,
                'metode_bayar' => 'cash',
                'total' => $p->dp,
                'uang_diterima' => $p->dp,
                'kembalian' => 0,
                'status' => 'selesai',
                'created_at' => $p->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksis', 'pre_order_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pre_order_id');
            });
        }
        if (Schema::hasColumn('transaksis', 'jenis')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropColumn('jenis');
            });
        }
        if (Schema::hasColumn('pre_orders', 'stok_dipotong')) {
            Schema::table('pre_orders', function (Blueprint $table) {
                $table->dropColumn('stok_dipotong');
            });
        }
    }
};
