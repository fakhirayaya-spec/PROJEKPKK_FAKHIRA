<?php

namespace App\Console\Commands;

use App\Support\HapusKasir;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus semua data kasir selain "byeonwooseok" dan "Kasir potato" (mis. Jeno).
 * Bisa dijalankan berkali-kali (beda dengan migration yang hanya jalan sekali).
 *
 *   php artisan kasir:bersihkan --dry-run   (lihat dulu apa yang akan dihapus, tidak ada yang berubah)
 *   php artisan kasir:bersihkan             (hapus beneran, ada pertanyaan konfirmasi)
 *
 * Yang dihapus untuk kasir lain: akun + foto + sesi login, transaksi (penjualan, DP, pelunasan), pre-order,
 * pengajuan stok (restok), riwayat stok menu/bumbu/operasional, stok per kasir, batch exp, dan catatan
 * Pengeluaran yang menyebut kasir itu ("Stok awal / Isi stok / Isi ulang ... untuk kasir X" dan
 * "Restock ... (pengajuan kasir X)"). Total terjual menu dan angka stok Master Menu/Bumbu ikut disesuaikan.
 *
 * Tidak disentuh: akun admin, byeonwooseok, Kasir potato beserta datanya, menu, bumbu, bahan, harga.
 * Tidak bisa dikembalikan, jadi backup database dulu.
 */
class BersihkanKasirLainCommand extends Command
{
    protected $signature = 'kasir:bersihkan
        {--dry-run : Hanya tampilkan yang akan dihapus, tidak ada yang berubah}
        {--nama=* : Nama kasir tambahan yang catatan pengeluarannya ikut dihapus, kalau akunnya sudah tidak ada (bawaan Jeno)}
        {--semua-pengeluaran : Hapus SEMUA pengeluaran kecuali catatan stok milik kasir yang disisakan}
        {--force : Lewati pertanyaan konfirmasi}';

    protected $description = 'Hapus data kasir selain byeonwooseok dan Kasir potato (transaksi, pengeluaran, restok, dll)';

    /** @var array<int, array{0: string, 1: int}> */
    private array $ringkasan = [];

    /** @var array<int, string> */
    private array $fotoHapus = [];

    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->error('Tabel users tidak ditemukan. Sudah php artisan migrate?');

            return self::FAILURE;
        }

        $kasirs = DB::table('users')->where('role', 'kasir')->get(['id', 'name', 'email', 'avatar']);
        $simpan = $kasirs->filter(fn ($u) => $this->dijaga($u->name))->values();
        $hapus = $kasirs->reject(fn ($u) => $this->dijaga($u->name))->values();

        if ($simpan->isEmpty()) {
            $this->error('Kasir byeonwooseok / Kasir potato tidak ketemu di database, jadi tidak ada yang dihapus (pengaman).');

            return self::FAILURE;
        }

        $namaSimpan = $simpan->pluck('name')->all();
        $hapusId = $hapus->pluck('id')->map(fn ($i) => (int) $i)->all();

        $extra = $this->option('nama') ?: ['Jeno'];
        $namaHapus = collect($hapus->pluck('name')->all())
            ->merge($extra)
            ->map(fn ($n) => trim((string) $n))
            ->filter(fn ($n) => $n !== '' && ! $this->dijaga($n))
            ->unique()
            ->values()
            ->all();

        $this->line('Kasir yang DISISAKAN : ' . implode(', ', $namaSimpan));
        $this->line('Kasir yang DIHAPUS   : ' . ($hapus->isEmpty() ? '(tidak ada akun lain)' : $hapus->pluck('name')->implode(', ')));
        $this->line('Catatan pengeluaran yang menyebut kasir: ' . implode(', ', $namaHapus));
        if ($this->option('semua-pengeluaran')) {
            $this->warn('Mode --semua-pengeluaran: semua pengeluaran lain (termasuk yang diketik admin) juga dihapus.');
        }
        $this->newLine();

        $dry = (bool) $this->option('dry-run');

        if (! $dry && ! $this->option('force')) {
            if (! $this->confirm('Hapus permanen? Pastikan database sudah di-backup.', false)) {
                $this->info('Dibatalkan, tidak ada yang berubah.');

                return self::SUCCESS;
            }
        }

        DB::beginTransaction();
        try {
            $this->bersihkan($hapus, $hapusId, $namaHapus, $namaSimpan);

            if ($dry) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if (! $dry) {
            foreach ($this->fotoHapus as $path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->table([$dry ? 'Akan dihapus (pratinjau)' : 'Sudah dihapus', 'Jumlah'], $this->ringkasan);
        $this->newLine();
        $this->info($dry
            ? 'Pratinjau saja, belum ada yang berubah. Jalankan tanpa --dry-run untuk menghapus.'
            : 'Selesai. Data kasir lain sudah dibersihkan.');

        return self::SUCCESS;
    }

    /** Kasir yang tidak boleh tersentuh. */
    private function dijaga(?string $nama): bool
    {
        $n = mb_strtolower((string) $nama);

        return str_contains($n, 'byeonwooseok') || str_contains($n, 'potato');
    }

    private function catat(string $label, int $jumlah): void
    {
        $this->ringkasan[] = [$label, $jumlah];
    }

    private function bersihkan($hapus, array $hapusId, array $namaHapus, array $namaSimpan): void
    {
        // ---- 1) Pre-order & transaksi -----------------------------------------------------
        $po = collect();
        if ($hapusId && Schema::hasTable('pre_orders')) {
            $po = DB::table('pre_orders')->whereIn('user_id', $hapusId)->get(['id', 'kode_preorder']);
        }
        $poIds = $po->pluck('id')->all();

        $trxs = collect();
        if (Schema::hasTable('transaksis')) {
            $punyaPoId = Schema::hasColumn('transaksis', 'pre_order_id');
            $trxs = DB::table('transaksis')
                ->where(function ($q) use ($hapusId, $poIds, $punyaPoId) {
                    $q->whereIn('user_id', $hapusId);
                    if ($poIds && $punyaPoId) {
                        $q->orWhereIn('pre_order_id', $poIds);
                    }
                })
                ->get(['id', 'kode_transaksi', 'status']);
        }

        $keterangan = [];
        foreach ($trxs as $t) {
            $keterangan[] = 'Transaksi ' . $t->kode_transaksi;
            $keterangan[] = 'Batal ' . $t->kode_transaksi;
        }
        foreach ($po as $p) {
            $keterangan[] = 'Batal pre-order ' . $p->kode_preorder;
        }

        // total terjual menu dikurangi sebesar transaksi selesai yang dihapus
        $selesaiId = $trxs->where('status', 'selesai')->pluck('id')->all();
        if ($selesaiId && Schema::hasTable('transaksi_items') && Schema::hasTable('menus')) {
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

        $nTrx = 0;
        foreach (array_chunk($trxs->pluck('id')->all(), 500) as $potong) {
            if (Schema::hasTable('transaksi_items')) {
                DB::table('transaksi_items')->whereIn('transaksi_id', $potong)->delete();
            }
            if (Schema::hasTable('riwayat_stok_operasionals')) {
                DB::table('riwayat_stok_operasionals')->whereIn('transaksi_id', $potong)->delete();
            }
            $nTrx += DB::table('transaksis')->whereIn('id', $potong)->delete();
        }
        $this->catat('Transaksi (penjualan, DP, pelunasan)', $nTrx);

        $nPo = 0;
        foreach (array_chunk($poIds, 500) as $potong) {
            if (Schema::hasTable('pre_order_items')) {
                DB::table('pre_order_items')->whereIn('pre_order_id', $potong)->delete();
            }
            $nPo += DB::table('pre_orders')->whereIn('id', $potong)->delete();
        }
        $this->catat('Pre-order', $nPo);

        // ---- 2) Riwayat stok menu & bumbu -------------------------------------------------
        foreach (['pergerakan_stoks' => 'Riwayat stok menu', 'pergerakan_bumbus' => 'Riwayat stok bumbu'] as $tabel => $label) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }
            $punyaKasirId = Schema::hasColumn($tabel, 'kasir_id');
            $n = 0;

            if ($hapusId && $punyaKasirId) {
                $n += DB::table($tabel)->whereIn('kasir_id', $hapusId)->delete();
            }
            if ($hapusId) {
                $q = DB::table($tabel)->whereIn('user_id', $hapusId);
                if ($punyaKasirId) {
                    $q->whereNull('kasir_id');
                }
                $n += $q->delete();
            }
            foreach (array_chunk($keterangan, 200) as $potong) {
                $n += DB::table($tabel)->whereIn('keterangan', $potong)->delete();
            }
            $this->catat($label, $n);
        }

        // ---- 3) Stok per kasir (Master Menu/Bumbu = total stok semua kasir, jadi dikurangi) ---
        $nStok = 0;
        if ($hapusId && Schema::hasTable('stok_kasirs')) {
            $stokHapus = DB::table('stok_kasirs')
                ->whereIn('user_id', $hapusId)
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

            $nStok = DB::table('stok_kasirs')->whereIn('user_id', $hapusId)->delete();
        }
        $this->catat('Stok menu & bumbu per kasir', $nStok);

        $nBatch = 0;
        if ($hapusId && Schema::hasTable('stok_batches') && Schema::hasColumn('stok_batches', 'user_id')) {
            $nBatch = DB::table('stok_batches')->whereIn('user_id', $hapusId)->delete();
        }
        $this->catat('Batch exp stok', $nBatch);

        $nOp = 0;
        if ($hapusId && Schema::hasTable('riwayat_stok_operasionals')) {
            $nOp += DB::table('riwayat_stok_operasionals')->whereIn('user_id', $hapusId)->delete();
        }
        if ($hapusId && Schema::hasTable('stok_operasionals')) {
            $nOp += DB::table('stok_operasionals')->whereIn('user_id', $hapusId)->delete();
        }
        $this->catat('Stok & riwayat operasional (gas, minyak, dus, dll)', $nOp);

        // ---- 4) Pengajuan stok / restok ---------------------------------------------------
        $nReq = 0;
        if ($hapusId && Schema::hasTable('permintaan_stoks')) {
            $nReq = DB::table('permintaan_stoks')->whereIn('user_id', $hapusId)->delete();
        }
        $this->catat('Pengajuan stok (restok)', $nReq);

        // ---- 5) Pengeluaran ---------------------------------------------------------------
        $nPengeluaran = 0;
        if (Schema::hasTable('pengeluarans')) {
            $semua = (bool) $this->option('semua-pengeluaran');
            $ids = [];

            DB::table('pengeluarans')->chunkById(500, function ($rows) use (&$ids, $namaHapus, $namaSimpan, $semua) {
                foreach ($rows as $p) {
                    $hapusIni = false;

                    foreach ($namaHapus as $nama) {
                        if (HapusKasir::pengeluaranMenyebutKasir($nama, $p->deskripsi)) {
                            $hapusIni = true;
                            break;
                        }
                    }

                    if (! $hapusIni && $semua) {
                        $milikSimpan = false;
                        foreach ($namaSimpan as $nama) {
                            if (HapusKasir::pengeluaranMilikKasir((string) $nama, $p->deskripsi)) {
                                $milikSimpan = true;
                                break;
                            }
                        }
                        $hapusIni = ! $milikSimpan;
                    }

                    if ($hapusIni) {
                        $ids[] = $p->id;
                    }
                }
            });

            foreach (array_chunk($ids, 500) as $potong) {
                $nPengeluaran += DB::table('pengeluarans')->whereIn('id', $potong)->delete();
            }
        }
        $this->catat('Pengeluaran', $nPengeluaran);

        // ---- 6) Akun, sesi login, foto ----------------------------------------------------
        if ($hapusId) {
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $hapusId)->delete();
            }
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->whereIn('email', $hapus->pluck('email')->all())->delete();
            }
            foreach ($hapus as $u) {
                if ($u->avatar) {
                    $this->fotoHapus[] = $u->avatar;
                }
            }
        }
        $this->catat('Akun kasir', $hapusId ? DB::table('users')->whereIn('id', $hapusId)->delete() : 0);
    }
}
