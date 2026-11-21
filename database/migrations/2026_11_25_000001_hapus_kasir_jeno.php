<?php

use App\Http\Controllers\Kasir\TransaksiController;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Services\StokService;
use App\Support\HapusKasir;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Menghapus kasir bernama "Jeno" beserta semua yang terhubung ke dia:
 *  - stok operasional + riwayatnya, dan catatan Pengeluaran "Stok awal / Isi ulang ... untuk kasir Jeno"
 *  - semua transaksi (penjualan, DP, pelunasan) dan pre-order Jeno; stok menu & bumbu dikembalikan
 *    dan total terjual menu dikurangi, seolah transaksi itu tidak pernah ada
 *  - riwayat pergerakan stok yang dibuat Jeno, sesi login, foto profil, lalu akun-nya
 * Kalau tidak ada kasir bernama Jeno, tidak terjadi apa-apa. Tidak bisa dikembalikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $target = DB::table('users')->where('role', 'kasir')->get(['id', 'name', 'email', 'avatar'])
            ->filter(fn ($u) => HapusKasir::namaJeno($u->name));

        foreach ($target as $u) {
            DB::transaction(fn () => $this->hapus($u));
        }
    }

    private function hapus(object $u): void
    {
        $id = $u->id;

        $poIds = Schema::hasTable('pre_orders') ? DB::table('pre_orders')->where('user_id', $id)->pluck('id')->all() : [];
        $punyaPoId = Schema::hasColumn('transaksis', 'pre_order_id');

        $trxs = DB::table('transaksis')
            ->where(function ($q) use ($id, $poIds, $punyaPoId) {
                $q->where('user_id', $id);
                if ($poIds && $punyaPoId) {
                    $q->orWhereIn('pre_order_id', $poIds);
                }
            })
            ->get(['id', 'kode_transaksi', 'status']);

        $keterangan = [];

        // 1) Transaksi selesai: kembalikan stok menu & bumbu, kurangi total terjual
        foreach ($trxs as $t) {
            $keterangan[] = 'Transaksi ' . $t->kode_transaksi;
            $keterangan[] = 'Batal ' . $t->kode_transaksi;

            if ($t->status !== 'selesai') {
                continue;
            }

            foreach (DB::table('transaksi_items')->where('transaksi_id', $t->id)->get() as $item) {
                $menu = $item->menu_id ? Menu::lockForUpdate()->find($item->menu_id) : null;
                if ($menu) {
                    StokService::kembali($menu, (int) $item->qty);
                    $menu->decrement('total_terjual', min((int) $menu->total_terjual, (int) $item->qty));
                }
                $this->kembalikanBumbu($item);
            }
        }

        // 2) Pre-order yang masih aktif dan stoknya sudah terpotong: kembalikan stok
        //    (yang sudah selesai ikut tertangani lewat transaksi pelunasannya di atas)
        foreach ($poIds as $poId) {
            $po = DB::table('pre_orders')->where('id', $poId)->first(['kode_preorder', 'status', 'stok_dipotong']);
            $keterangan[] = 'Batal pre-order ' . $po->kode_preorder;

            if (! $po->stok_dipotong || ! in_array($po->status, ['baru', 'diproses', 'siap'], true)) {
                continue;
            }

            foreach (DB::table('pre_order_items')->where('pre_order_id', $poId)->get() as $item) {
                $menu = $item->menu_id ? Menu::lockForUpdate()->find($item->menu_id) : null;
                if ($menu) {
                    StokService::kembali($menu, (int) $item->qty);
                }
                $this->kembalikanBumbu($item);
            }
        }

        // 3) Hapus transaksi & pre-order
        $trxIds = $trxs->pluck('id')->all();
        if ($trxIds) {
            DB::table('transaksi_items')->whereIn('transaksi_id', $trxIds)->delete();
            if (Schema::hasTable('riwayat_stok_operasionals')) {
                DB::table('riwayat_stok_operasionals')->whereIn('transaksi_id', $trxIds)->delete();
            }
            DB::table('transaksis')->whereIn('id', $trxIds)->delete();
        }
        if ($poIds) {
            DB::table('pre_order_items')->whereIn('pre_order_id', $poIds)->delete();
            DB::table('pre_orders')->whereIn('id', $poIds)->delete();
        }

        // 4) Pergerakan stok yang dibuat Jeno / mencatat transaksinya
        if (Schema::hasTable('pergerakan_stoks')) {
            DB::table('pergerakan_stoks')
                ->where(function ($q) use ($id, $keterangan) {
                    $q->where('user_id', $id);
                    if ($keterangan) {
                        $q->orWhereIn('keterangan', $keterangan);
                    }
                })
                ->delete();
        }

        // 5) Stok operasional, riwayat, dan biayanya di Pengeluaran
        if (Schema::hasTable('riwayat_stok_operasionals')) {
            DB::table('riwayat_stok_operasionals')->where('user_id', $id)->delete();
        }
        if (Schema::hasTable('stok_operasionals')) {
            DB::table('stok_operasionals')->where('user_id', $id)->delete();
        }
        if (Schema::hasTable('pengeluarans')) {
            $hapus = DB::table('pengeluarans')
                ->where(fn ($q) => $q->where('deskripsi', 'like', 'Stok awal %')->orWhere('deskripsi', 'like', 'Isi ulang %'))
                ->get(['id', 'deskripsi'])
                ->filter(fn ($p) => HapusKasir::pengeluaranMilikKasir($u->name, $p->deskripsi))
                ->pluck('id')->all();

            foreach (array_chunk($hapus, 200) as $potongan) {
                DB::table('pengeluarans')->whereIn('id', $potongan)->delete();
            }
        }

        // 6) Sesi login, token reset password, foto, dan akunnya
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $id)->delete();
        }
        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $u->email)->delete();
        }
        if ($u->avatar) {
            try {
                Storage::disk('public')->delete($u->avatar);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        DB::table('users')->where('id', $id)->delete();
    }

    private function kembalikanBumbu(object $item): void
    {
        foreach ([
            ['saus', $item->saus ?? null, TransaksiController::TANPA_SAUS],
            ['tabur', $item->bubuk_tabur ?? null, TransaksiController::TANPA_BUBUK],
        ] as [$jenis, $nama, $tanpa]) {
            if ($nama && $nama !== $tanpa) {
                $bumbu = Bumbu::lockForUpdate()->where('jenis', $jenis)->where('nama', $nama)->first();
                if ($bumbu) {
                    StokService::kembali($bumbu, (int) $item->qty);
                }
            }
        }
    }

    public function down(): void
    {
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
