<?php

use App\Models\Bumbu;
use App\Models\Menu;
use App\Services\StokService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mulai dari awal (seperti baru buka):
 *  - Semua transaksi & pre-order dihapus, total terjual menu = 0, riwayat pergerakan stok dibersihkan.
 *  - Master Menu & Manajemen Bumbu: stok 50 semua (batch exp dibuat ulang).
 *  - Stok Operasional tiap kasir aktif: gas 50 (1 tabung), minyak 50 (5 liter), dus 100, cup 100, es batu 50.
 *  - Pengeluaran: catatan biaya stok lama ("Stok awal ..." / "Isi ulang ...") diganti catatan baru yang
 *      * Menu     : 50 x harga modal menu            (Bahan Baku)
 *      * Operasional: stok / isi per unit x harga per unit, per kasir (sesuai kategori bahan)
 *    Bumbu tidak dicatat sendiri karena modalnya sudah ikut di harga modal menu.
 *    Pengeluaran yang diketik admin sendiri (listrik, sewa, dll) tidak disentuh.
 * Tidak bisa dibatalkan.
 */
return new class extends Migration
{
    private const STOK = 50;

    /** Stok awal bahan operasional yang diminta pemilik usaha */
    private const OPERASIONAL = ['gas' => 50, 'minyak' => 50, 'dus' => 100, 'cup' => 100, 'es' => 50];

    public function up(): void
    {
        if (! Schema::hasTable('transaksis')) {
            return;
        }

        DB::transaction(function () {
            $this->hapusTransaksi();
            $this->aturStokMenuDanBumbu();
            $this->aturStokOperasionalDanBiaya();
        });
    }

    private function hapusTransaksi(): void
    {
        DB::table('transaksi_items')->delete();
        if (Schema::hasTable('pre_order_items')) {
            DB::table('pre_order_items')->delete();
        }
        DB::table('transaksis')->delete();
        if (Schema::hasTable('pre_orders')) {
            DB::table('pre_orders')->delete();
        }
        if (Schema::hasTable('riwayat_stok_operasionals')) {
            DB::table('riwayat_stok_operasionals')->delete();
        }
        if (Schema::hasTable('pergerakan_stoks')) {
            DB::table('pergerakan_stoks')->delete();
        }
    }

    private function aturStokMenuDanBumbu(): void
    {
        $adaBatch = Schema::hasTable('stok_batches');
        if ($adaBatch) {
            DB::table('stok_batches')->delete();
        }

        DB::table('menus')->update(['stok' => 0, 'total_terjual' => 0]);
        DB::table('bumbus')->update(['stok' => 0]);

        $tanggal = today()->toDateString();

        foreach (Menu::with('kategori')->orderBy('id')->get() as $menu) {
            if ($adaBatch) {
                StokService::tambah($menu, self::STOK, null, 'Stok awal');
            } else {
                $menu->update(['stok' => self::STOK]);
            }

            if (Schema::hasTable('pergerakan_stoks')) {
                DB::table('pergerakan_stoks')->insert([
                    'menu_id' => $menu->id, 'user_id' => null, 'jenis' => 'masuk', 'jumlah' => self::STOK,
                    'keterangan' => 'Stok awal', 'tanggal' => $tanggal, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        foreach (Bumbu::orderBy('id')->get() as $bumbu) {
            if ($adaBatch) {
                StokService::tambah($bumbu, self::STOK, null, 'Stok awal');
            } else {
                $bumbu->update(['stok' => self::STOK]);
            }
        }
    }

    private function aturStokOperasionalDanBiaya(): void
    {
        $adaPengeluaran = Schema::hasTable('pengeluarans');

        // Catatan biaya stok yang lama dibuang; yang diketik admin sendiri tidak disentuh
        if ($adaPengeluaran) {
            DB::table('pengeluarans')
                ->where(fn ($q) => $q->where('deskripsi', 'like', 'Stok awal %')->orWhere('deskripsi', 'like', 'Isi ulang %'))
                ->delete();
        }

        // Biaya stok awal menu: 50 x harga modal (Bahan Baku)
        if ($adaPengeluaran) {
            $baris = [];
            foreach (Menu::orderBy('id')->get(['nama_menu', 'harga_modal']) as $menu) {
                $modal = (int) $menu->harga_modal;
                if ($modal <= 0) {
                    continue;
                }
                $baris[] = [
                    'tanggal' => today()->toDateString(),
                    'kategori' => 'bahan_baku',
                    'deskripsi' => Str::limit('Stok awal ' . $menu->nama_menu . ': ' . self::STOK . ' porsi x Rp ' . number_format($modal, 0, ',', '.'), 150, ''),
                    'jumlah' => self::STOK * $modal,
                    'user_id' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            foreach (array_chunk($baris, 100) as $potongan) {
                DB::table('pengeluarans')->insert($potongan);
            }
        }

        if (! Schema::hasTable('bahan_operasionals') || ! Schema::hasTable('stok_operasionals')) {
            return;
        }

        // Stok awal bahan: dus 100, cup 100, es batu 50, dst. (supaya kasir baru juga dapat angka yang sama)
        foreach (self::OPERASIONAL as $kode => $stok) {
            DB::table('bahan_operasionals')->where('kode', $kode)->update(['stok_awal' => $stok, 'updated_at' => now()]);
        }

        $bahan = DB::table('bahan_operasionals')->where('status', 'aktif')->orderBy('urutan')->orderBy('id')->get();
        $semuaBahan = DB::table('bahan_operasionals')->get()->keyBy('kode');

        // Stok semua baris yang ada kembali ke stok awal bahannya
        foreach (DB::table('stok_operasionals')->get() as $b) {
            if (isset($semuaBahan[$b->bahan])) {
                DB::table('stok_operasionals')->where('id', $b->id)->update(['stok' => (int) $semuaBahan[$b->bahan]->stok_awal, 'updated_at' => now()]);
            }
        }

        // Kasir aktif: pastikan punya baris stok untuk tiap bahan aktif, lalu catat biayanya
        $kasirAktif = DB::table('users')->where('role', 'kasir')->where('status', 'aktif')->orderBy('id')->get(['id', 'name']);
        $baris = [];

        foreach ($kasirAktif as $kasir) {
            foreach ($bahan as $b) {
                $ada = DB::table('stok_operasionals')->where('user_id', $kasir->id)->where('bahan', $b->kode)->exists();
                if (! $ada) {
                    DB::table('stok_operasionals')->insert([
                        'user_id' => $kasir->id, 'bahan' => $b->kode, 'stok' => (int) $b->stok_awal,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                $isi = max((int) $b->isi_per_unit, 1);
                $harga = (int) ($b->harga_per_unit ?? 0);
                $unit = ((int) $b->stok_awal) / $isi;
                $biaya = (int) round($unit * $harga);
                if ($harga <= 0 || $biaya <= 0) {
                    continue;
                }

                $unitTeks = floor($unit) == $unit ? (string) (int) $unit : rtrim(rtrim(number_format($unit, 2, '.', ''), '0'), '.');
                $baris[] = [
                    'tanggal' => today()->toDateString(),
                    'kategori' => ($b->kategori_pengeluaran ?? null) ?: 'operasional',
                    'deskripsi' => Str::limit("Stok awal {$b->nama}: {$unitTeks} {$b->unit_isi} untuk kasir {$kasir->name}", 150, ''),
                    'jumlah' => $biaya,
                    'user_id' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }

        if ($adaPengeluaran) {
            foreach (array_chunk($baris, 100) as $potongan) {
                DB::table('pengeluarans')->insert($potongan);
            }
        }
    }

    public function down(): void
    {
        // data yang sudah dihapus tidak bisa dikembalikan
    }
};
