<?php

use App\Support\HapusKasir;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Bersihkan data: yang disisakan HANYA kasir "byeonwooseok" dan "Kasir potato" beserta datanya.
 *
 * Dihapus (milik kasir lain / tidak terhubung ke kedua kasir itu):
 *  - akun kasir lain, foto profil, sesi login, token reset password
 *  - semua transaksi (penjualan, DP, pelunasan) dan pre-order kasir lain (total terjual menu ikut dikurangi)
 *  - pengajuan stok, riwayat stok menu & bumbu, batch exp, stok menu/bumbu/operasional milik kasir lain
 *    (angka stok di Master Menu & Master Bumbu dikurangi sebesar stok kasir yang dihapus)
 *  - SEMUA pengeluaran, kecuali catatan otomatis "Stok awal / Isi stok / Isi ulang ... untuk kasir <nama>"
 *    milik byeonwooseok dan Kasir potato. Pengeluaran yang diketik admin sendiri ikut dihapus.
 *
 * Tidak berubah: akun admin, akun & data byeonwooseok dan Kasir potato, menu, kategori, bumbu, bahan operasional, harga.
 * Pengaman: kalau kedua kasir itu tidak ketemu di database, tidak ada yang dihapus.
 * Laporan & dashboard dihitung langsung dari transaksi/pengeluaran, jadi otomatis ikut bersih.
 * Tidak bisa dikembalikan (backup database dulu sebelum migrate).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $kasirs = DB::table('users')->where('role', 'kasir')->get(['id', 'name', 'email', 'avatar']);

        $simpan = $kasirs->filter(function ($u) {
            $nama = mb_strtolower((string) $u->name);

            return str_contains($nama, 'byeonwooseok') || str_contains($nama, 'potato');
        })->values();

        if ($simpan->isEmpty()) {
            return; // pengaman: kasir yang mau disisakan tidak ketemu, jangan hapus apa pun
        }

        $simpanId = $simpan->pluck('id')->map(fn ($i) => (int) $i)->all();
        $namaSimpan = $simpan->pluck('name')->all();
        $hapus = $kasirs->reject(fn ($u) => in_array((int) $u->id, $simpanId, true))->values();
        $hapusId = $hapus->pluck('id')->map(fn ($i) => (int) $i)->all();

        DB::transaction(function () use ($simpanId, $namaSimpan, $hapusId, $hapus) {
            $this->hapusTransaksiDanPreOrder($simpanId);
            $this->hapusPengajuanStok($simpanId);
            $this->hapusRiwayatStok($simpanId);
            $this->hapusStokDanBatch($simpanId);
            $this->hapusPengeluaran($namaSimpan);
            $this->hapusAkun($hapus, $hapusId);
        });

        // Foto profil dihapus setelah database beres (di luar transaksi)
        foreach ($hapus as $u) {
            if ($u->avatar) {
                try {
                    Storage::disk('public')->delete($u->avatar);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }

    // ------------------------------------------------------------------

    private function hapusTransaksiDanPreOrder(array $simpanId): void
    {
        $poHapus = Schema::hasTable('pre_orders')
            ? DB::table('pre_orders')->whereNotIn('user_id', $simpanId)->pluck('id')->all()
            : [];

        if (Schema::hasTable('transaksis')) {
            $punyaPoId = Schema::hasColumn('transaksis', 'pre_order_id');

            $trxs = DB::table('transaksis')
                ->where(function ($q) use ($simpanId, $poHapus, $punyaPoId) {
                    $q->whereNotIn('user_id', $simpanId);
                    if ($poHapus && $punyaPoId) {
                        $q->orWhereIn('pre_order_id', $poHapus);
                    }
                })
                ->get(['id', 'status']);

            $trxId = $trxs->pluck('id')->all();
            $selesaiId = $trxs->where('status', 'selesai')->pluck('id')->all();

            // Total terjual menu dikurangi sebesar transaksi selesai yang dihapus
            if (Schema::hasTable('transaksi_items') && Schema::hasTable('menus')) {
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

        foreach (array_chunk($poHapus, 500) as $potong) {
            if (Schema::hasTable('pre_order_items')) {
                DB::table('pre_order_items')->whereIn('pre_order_id', $potong)->delete();
            }
            DB::table('pre_orders')->whereIn('id', $potong)->delete();
        }
    }

    private function hapusPengajuanStok(array $simpanId): void
    {
        if (Schema::hasTable('permintaan_stoks')) {
            DB::table('permintaan_stoks')->whereNotIn('user_id', $simpanId)->delete();
        }
    }

    /** Riwayat pergerakan stok menu & bumbu: sisakan hanya yang kasir pemiliknya byeonwooseok / Kasir potato. */
    private function hapusRiwayatStok(array $simpanId): void
    {
        foreach (['pergerakan_stoks', 'pergerakan_bumbus'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            // punya kasir pemilik, tapi bukan kedua kasir itu
            DB::table($tabel)->whereNotNull('kasir_id')->whereNotIn('kasir_id', $simpanId)->delete();

            // tanpa kasir pemilik (data lama/pusat): sisakan hanya kalau dicatat oleh kedua kasir itu
            DB::table($tabel)->whereNull('kasir_id')
                ->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $simpanId))
                ->delete();
        }

        if (Schema::hasTable('riwayat_stok_operasionals')) {
            DB::table('riwayat_stok_operasionals')->whereNotIn('user_id', $simpanId)->delete();
        }
    }

    private function hapusStokDanBatch(array $simpanId): void
    {
        // Master Menu / Master Bumbu = total stok semua kasir, jadi kurangi sebesar stok kasir yang dihapus
        if (Schema::hasTable('stok_kasirs')) {
            $stokHapus = DB::table('stok_kasirs')
                ->whereNotIn('user_id', $simpanId)
                ->where('stok', '>', 0)
                ->selectRaw('tipe, item_id, SUM(stok) as total')
                ->groupBy('tipe', 'item_id')
                ->get();

            foreach ($stokHapus as $s) {
                $tabel = $s->tipe === 'menu' ? 'menus' : 'bumbus';
                if (! Schema::hasTable($tabel)) {
                    continue;
                }
                $sekarang = (int) DB::table($tabel)->where('id', $s->item_id)->value('stok');
                DB::table($tabel)->where('id', $s->item_id)->update(['stok' => max(0, $sekarang - (int) $s->total)]);
            }

            DB::table('stok_kasirs')->whereNotIn('user_id', $simpanId)->delete();
        }

        if (Schema::hasTable('stok_batches') && Schema::hasColumn('stok_batches', 'user_id')) {
            DB::table('stok_batches')
                ->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $simpanId))
                ->delete();
        }

        if (Schema::hasTable('stok_operasionals')) {
            DB::table('stok_operasionals')->whereNotIn('user_id', $simpanId)->delete();
        }
    }

    /** Hapus semua pengeluaran, kecuali catatan stok otomatis milik kasir yang disisakan. */
    private function hapusPengeluaran(array $namaSimpan): void
    {
        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        $hapus = [];

        DB::table('pengeluarans')->orderBy('id')->chunkById(500, function ($rows) use (&$hapus, $namaSimpan) {
            foreach ($rows as $p) {
                $milikKasirSimpan = false;
                foreach ($namaSimpan as $nama) {
                    if (HapusKasir::pengeluaranMilikKasir((string) $nama, $p->deskripsi)) {
                        $milikKasirSimpan = true;
                        break;
                    }
                }
                if (! $milikKasirSimpan) {
                    $hapus[] = $p->id;
                }
            }
        });

        foreach (array_chunk($hapus, 500) as $potong) {
            DB::table('pengeluarans')->whereIn('id', $potong)->delete();
        }
    }

    private function hapusAkun($hapus, array $hapusId): void
    {
        if (! $hapusId) {
            return;
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->whereIn('user_id', $hapusId)->delete();
        }
        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->whereIn('email', $hapus->pluck('email')->all())->delete();
        }

        DB::table('users')->whereIn('id', $hapusId)->delete();
    }

    public function down(): void
    {
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
