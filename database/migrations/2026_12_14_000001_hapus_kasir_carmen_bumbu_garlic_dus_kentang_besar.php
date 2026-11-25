<?php

use App\Support\HapusKasir;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Pembersihan data (sekali jalan, aman dijalankan ulang: kalau datanya sudah tidak ada, tidak terjadi apa-apa):
 *
 *  1) Kasir "Carmen" dihapus beserta semua yang terhubung ke dia:
 *     transaksi (penjualan, DP, pelunasan) dan pre-order, pengajuan stok, riwayat stok menu/bumbu/operasional,
 *     batch exp, stok menu/bumbu/operasional, catatan Pengeluaran yang menyebut kasir Carmen
 *     ("Stok awal / Isi stok / Isi ulang ... untuk kasir Carmen" dan "Restock ... (pengajuan kasir Carmen)"),
 *     sesi login, foto profil, lalu akunnya.
 *     Total terjual menu dikurangi, dan angka stok Master Menu/Bumbu dikurangi sebesar stok Carmen.
 *     Kasir "byeonwooseok" dan "Kasir potato" tidak pernah ikut tersentuh.
 *
 *  2) Bumbu / saus "Garlic" dihapus beserta stok per kasir, batch exp, pengajuan, dan riwayat stoknya.
 *
 *  3) Bahan operasional "Dus Kentang Besar" dihapus beserta stok per kasir, pengajuan, riwayat pemakaian, dan gambarnya.
 *
 * Tidak disentuh: nama garlic di struk/riwayat transaksi lama, catatan Pengeluaran milik kasir lain, akun admin, harga, menu.
 * Tidak bisa dikembalikan (backup database dulu sebelum migrate).
 */
return new class extends Migration
{
    /** File di storage publik yang dihapus setelah database beres */
    private array $fileHapus = [];

    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::transaction(function () {
            $this->hapusKasirCarmen();
            $this->hapusBumbuGarlic();
            $this->hapusDusKentangBesar();
        });

        foreach ($this->fileHapus as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    // ------------------------------------------------------------------
    // 1) Kasir Carmen
    // ------------------------------------------------------------------

    private function hapusKasirCarmen(): void
    {
        $carmen = DB::table('users')->where('role', 'kasir')->get(['id', 'name', 'email', 'avatar'])
            ->filter(function ($u) {
                $nama = mb_strtolower((string) $u->name);

                // pengaman: kasir yang harus disisakan tidak pernah ikut terhapus
                if (str_contains($nama, 'potato') || str_contains($nama, 'byeonwooseok')) {
                    return false;
                }

                return HapusKasir::namaMengandungKata('carmen', $u->name);
            })
            ->values();

        foreach ($carmen as $u) {
            $this->hapusDataKasir($u);
        }
    }

    private function hapusDataKasir(object $u): void
    {
        $id = (int) $u->id;

        $this->hapusTransaksiDanPreOrder($id);

        if (Schema::hasTable('permintaan_stoks')) {
            DB::table('permintaan_stoks')->where('user_id', $id)->delete();
        }

        // Riwayat pergerakan stok menu & bumbu: yang stoknya milik Carmen, atau yang dicatat oleh Carmen
        foreach (['pergerakan_stoks', 'pergerakan_bumbus'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            DB::table($tabel)->where(function ($q) use ($tabel, $id) {
                $q->where('user_id', $id);
                if (Schema::hasColumn($tabel, 'kasir_id')) {
                    $q->orWhere('kasir_id', $id);
                }
            })->delete();
        }

        if (Schema::hasTable('riwayat_stok_operasionals')) {
            DB::table('riwayat_stok_operasionals')->where('user_id', $id)->delete();
        }

        // Master Menu / Master Bumbu = total stok semua kasir, jadi kurangi sebesar stok Carmen
        if (Schema::hasTable('stok_kasirs')) {
            $stokHapus = DB::table('stok_kasirs')
                ->where('user_id', $id)
                ->where('stok', '>', 0)
                ->get(['tipe', 'item_id', 'stok']);

            foreach ($stokHapus as $s) {
                $tabel = $s->tipe === 'menu' ? 'menus' : 'bumbus';
                if (! Schema::hasTable($tabel)) {
                    continue;
                }
                $sekarang = (int) DB::table($tabel)->where('id', $s->item_id)->value('stok');
                DB::table($tabel)->where('id', $s->item_id)->update(['stok' => max(0, $sekarang - (int) $s->stok)]);
            }

            DB::table('stok_kasirs')->where('user_id', $id)->delete();
        }

        if (Schema::hasTable('stok_batches') && Schema::hasColumn('stok_batches', 'user_id')) {
            DB::table('stok_batches')->where('user_id', $id)->delete();
        }

        if (Schema::hasTable('stok_operasionals')) {
            DB::table('stok_operasionals')->where('user_id', $id)->delete();
        }

        $this->hapusPengeluaranKasir((string) $u->name);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $id)->delete();
        }
        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $u->email)->delete();
        }
        if ($u->avatar) {
            $this->fileHapus[] = $u->avatar;
        }

        DB::table('users')->where('id', $id)->delete();
    }

    private function hapusTransaksiDanPreOrder(int $id): void
    {
        $poId = Schema::hasTable('pre_orders')
            ? DB::table('pre_orders')->where('user_id', $id)->pluck('id')->all()
            : [];

        if (Schema::hasTable('transaksis')) {
            $punyaPoId = Schema::hasColumn('transaksis', 'pre_order_id');

            $trxs = DB::table('transaksis')
                ->where(function ($q) use ($id, $poId, $punyaPoId) {
                    $q->where('user_id', $id);
                    if ($poId && $punyaPoId) {
                        $q->orWhereIn('pre_order_id', $poId);
                    }
                })
                ->get(['id', 'status']);

            $trxId = $trxs->pluck('id')->all();
            $selesaiId = $trxs->where('status', 'selesai')->pluck('id')->all();

            $this->kurangiTotalTerjual($selesaiId);

            foreach (array_chunk($trxId, 500) as $potong) {
                if (Schema::hasTable('transaksi_items')) {
                    DB::table('transaksi_items')->whereIn('transaksi_id', $potong)->delete();
                }
                if (Schema::hasTable('riwayat_stok_operasionals')) {
                    DB::table('riwayat_stok_operasionals')->whereIn('transaksi_id', $potong)->delete();
                }
                DB::table('transaksis')->whereIn('id', $potong)->delete();
            }
        }

        foreach (array_chunk($poId, 500) as $potong) {
            if (Schema::hasTable('pre_order_items')) {
                DB::table('pre_order_items')->whereIn('pre_order_id', $potong)->delete();
            }
            DB::table('pre_orders')->whereIn('id', $potong)->delete();
        }
    }

    /** Total terjual menu dikurangi sebesar item pada transaksi selesai yang dihapus */
    private function kurangiTotalTerjual(array $selesaiId): void
    {
        if (! $selesaiId || ! Schema::hasTable('transaksi_items') || ! Schema::hasTable('menus')) {
            return;
        }

        $kurang = [];
        foreach (array_chunk($selesaiId, 500) as $potong) {
            $baris = DB::table('transaksi_items')
                ->whereIn('transaksi_id', $potong)
                ->whereNotNull('menu_id')
                ->selectRaw('menu_id, SUM(qty) as total')
                ->groupBy('menu_id')
                ->get();

            foreach ($baris as $b) {
                $kurang[$b->menu_id] = ($kurang[$b->menu_id] ?? 0) + (int) $b->total;
            }
        }

        foreach ($kurang as $menuId => $jumlah) {
            $sekarang = (int) DB::table('menus')->where('id', $menuId)->value('total_terjual');
            DB::table('menus')->where('id', $menuId)->update(['total_terjual' => max(0, $sekarang - $jumlah)]);
        }
    }

    /** Catatan Pengeluaran yang menyebut "kasir <nama>"; pengeluaran umum yang diketik admin tidak ikut. */
    private function hapusPengeluaranKasir(string $nama): void
    {
        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        $hapus = [];

        DB::table('pengeluarans')->where('deskripsi', 'like', '%kasir%')
            ->chunkById(500, function ($rows) use (&$hapus, $nama) {
                foreach ($rows as $p) {
                    if (HapusKasir::pengeluaranMenyebutKasir($nama, $p->deskripsi)) {
                        $hapus[] = $p->id;
                    }
                }
            });

        foreach (array_chunk($hapus, 500) as $potong) {
            DB::table('pengeluarans')->whereIn('id', $potong)->delete();
        }
    }

    // ------------------------------------------------------------------
    // 2) Bumbu Garlic
    // ------------------------------------------------------------------

    private function hapusBumbuGarlic(): void
    {
        $ids = [];

        if (Schema::hasTable('bumbus')) {
            $ids = DB::table('bumbus')->get(['id', 'nama'])
                ->filter(fn ($b) => mb_stripos((string) $b->nama, 'garlic') !== false)
                ->pluck('id')->map(fn ($i) => (int) $i)->all();
        }

        // riwayat & pengajuan dibersihkan juga lewat nama, jadi sisa dari bumbu yang dulu terhapus lewat tombol Hapus ikut bersih
        if (Schema::hasTable('pergerakan_bumbus')) {
            DB::table('pergerakan_bumbus')->where(function ($q) use ($ids) {
                $q->where('nama', 'like', '%garlic%');
                if ($ids) {
                    $q->orWhereIn('bumbu_id', $ids);
                }
            })->delete();
        }

        if (Schema::hasTable('permintaan_stoks')) {
            DB::table('permintaan_stoks')->where('jenis', 'bumbu')->where(function ($q) use ($ids) {
                $q->where('nama_item', 'like', '%garlic%');
                if ($ids) {
                    $q->orWhereIn('item_id', $ids);
                }
            })->delete();
        }

        if (! $ids) {
            return;
        }

        if (Schema::hasTable('stok_kasirs')) {
            DB::table('stok_kasirs')->where('tipe', 'bumbu')->whereIn('item_id', $ids)->delete();
        }
        if (Schema::hasTable('stok_batches')) {
            DB::table('stok_batches')->where('tipe', 'bumbu')->whereIn('item_id', $ids)->delete();
        }

        DB::table('bumbus')->whereIn('id', $ids)->delete();
    }

    // ------------------------------------------------------------------
    // 3) Bahan operasional Dus Kentang Besar
    // ------------------------------------------------------------------

    private function hapusDusKentangBesar(): void
    {
        if (! Schema::hasTable('bahan_operasionals')) {
            return;
        }

        // nama harus memuat ketiga kata, jadi "Dus kentang" biasa (kode "dus") tidak ikut terhapus
        $bahans = DB::table('bahan_operasionals')->get(['id', 'kode', 'nama', 'gambar'])
            ->filter(function ($b) {
                $n = (string) $b->nama;

                return mb_stripos($n, 'dus') !== false
                    && mb_stripos($n, 'kentang') !== false
                    && mb_stripos($n, 'besar') !== false;
            })
            ->values();

        if ($bahans->isEmpty()) {
            return;
        }

        $kode = $bahans->pluck('kode')->all();

        if (Schema::hasTable('riwayat_stok_operasionals')) {
            DB::table('riwayat_stok_operasionals')->whereIn('bahan', $kode)->delete();
        }
        if (Schema::hasTable('stok_operasionals')) {
            DB::table('stok_operasionals')->whereIn('bahan', $kode)->delete();
        }
        if (Schema::hasTable('permintaan_stoks')) {
            DB::table('permintaan_stoks')->whereIn('bahan_kode', $kode)->delete();
        }

        foreach ($bahans as $b) {
            if ($b->gambar) {
                $this->fileHapus[] = $b->gambar;
            }
        }

        DB::table('bahan_operasionals')->whereIn('id', $bahans->pluck('id')->all())->delete();
    }

    public function down(): void
    {
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
