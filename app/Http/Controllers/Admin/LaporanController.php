<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Kasir\TransaksiController;
use App\Models\BahanOperasional;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\PergerakanStok;
use App\Models\PreOrder;
use App\Models\RiwayatStokOperasional;
use App\Models\StokOperasional;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\XlsxWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LaporanController extends Controller
{
    private function rentang(Request $request): array
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $dari = $request->filled('dari') ? Carbon::parse($request->dari)->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? Carbon::parse($request->sampai)->endOfDay() : now()->endOfDay();

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
        }
        if ($dari->diffInDays($sampai) > 366) {
            $dari = $sampai->copy()->subDays(366)->startOfDay();
        }

        return [$dari, $sampai];
    }

    private function itemQuery(Carbon $dari, Carbon $sampai)
    {
        return DB::table('transaksi_items as ti')
            ->join('transaksis as t', 't.id', '=', 'ti.transaksi_id')
            ->where('t.status', 'selesai')
            ->whereBetween('t.created_at', [$dari, $sampai]);
    }

    public function index(Request $request)
    {
        [$dari, $sampai] = $this->rentang($request);

        return view('admin.laporan.index', $this->dataLaporan($dari, $sampai));
    }

    /** Halaman laporan siap cetak (A4) — dari browser pilih "Simpan sebagai PDF" atau langsung print. */
    public function cetak(Request $request)
    {
        [$dari, $sampai] = $this->rentang($request);

        $daftarTransaksi = Transaksi::with('kasir')
            ->where('status', 'selesai')
            ->whereBetween('created_at', [$dari, $sampai])
            ->orderBy('created_at')
            ->get();

        return view('admin.laporan.cetak', $this->dataLaporan($dari, $sampai) + [
            'daftarTransaksi' => $daftarTransaksi,
            'dicetakOleh' => auth()->user()->name,
        ]);
    }

    /** Unduh laporan sebagai file Excel (.xlsx), isinya sama dengan versi cetak, dibagi per sheet. */
    public function excel(Request $request)
    {
        if (! XlsxWriter::tersedia()) {
            return back()->with('gagal', 'Ekstensi PHP "zip" belum aktif, jadi file Excel belum bisa dibuat. Buka php.ini (XAMPP: Config > PHP) lalu aktifkan baris extension=zip dan restart Apache.');
        }

        [$dari, $sampai] = $this->rentang($request);
        $d = $this->dataLaporan($dari, $sampai);

        $daftarTransaksi = Transaksi::with('kasir')
            ->where('status', 'selesai')
            ->whereBetween('created_at', [$dari, $sampai])
            ->orderBy('created_at')
            ->get();

        $labelKategori = ['bahan_baku' => 'Bahan Baku', 'operasional' => 'Operasional', 'lainnya' => 'Lainnya'];
        $labelStatus = ['aman' => 'Aman', 'menipis' => 'Menipis', 'habis' => 'Habis'];
        $labelMetode = ['cash' => 'Tunai', 'qris' => 'QRIS', 'paylater' => 'Paylater'];

        $periode = 'Periode: ' . $dari->format('d M Y') . ' - ' . $sampai->format('d M Y');
        $kepala = fn (string $judul) => [
            [[$judul, 'judul']],
            [['Potato Curly Crunch  |  ' . $periode, 'sub']],
            [],
        ];
        $h = fn (array $kolom) => array_map(fn ($k) => [$k, 'header'], $kolom);

        $xlsx = new XlsxWriter();

        // ---------- 1. Ringkasan ----------
        $ringkas = $kepala('Laporan Penjualan & Keuangan');
        $ringkas[] = $h(['Keterangan', 'Nilai']);
        foreach ([
            ['Total Penjualan', $d['totalPenjualan'], 'rp'],
            ['Jumlah Transaksi', $d['jumlahTransaksi'], 'angka'],
            ['Rata-rata per Transaksi', $d['rataRata'], 'rp'],
            ['Item Terjual', $d['itemTerjual'], 'angka'],
            ['Transaksi Dibatalkan (jumlah)', $d['transaksiBatal'], 'angka'],
            ['Transaksi Dibatalkan (nominal)', $d['nominalBatal'], 'rp'],
            ['Modal (HPP)', $d['totalModal'], 'rp'],
            ['Laba Kotor', $d['labaKotor'], $d['labaKotor'] >= 0 ? 'hijau_rp' : 'merah_rp'],
            ['Margin Laba Kotor', (float) $d['marginPersen'], 'persen'],
            ['Pengeluaran Operasional & Lainnya', $d['pengeluaranOperasional'], 'rp'],
            ['Keuntungan Bersih', $d['keuntunganBersih'], $d['keuntunganBersih'] >= 0 ? 'hijau_rp' : 'merah_rp'],
            ['Margin Bersih', (float) $d['marginBersih'], 'persen'],
            ['Pengeluaran Bahan Baku (tercatat)', $d['pengeluaranBahanBaku'], 'rp'],
        ] as [$label, $nilai, $gaya]) {
            $ringkas[] = [[$label, 'tebal'], [$nilai, $gaya]];
        }
        $ringkas[] = [];
        $ringkas[] = [['Keuntungan Bersih = Laba Kotor - Pengeluaran Operasional & Lainnya. Pengeluaran Bahan Baku tidak dikurangkan lagi karena sudah masuk Modal (HPP).', 'catatan']];
        if ($d['menuModalKosong'] > 0) {
            $ringkas[] = [["* {$d['menuModalKosong']} menu yang terjual belum punya harga modal, sehingga labanya masih dianggap sama dengan penjualan.", 'catatan']];
        }
        $xlsx->sheet('Ringkasan', $ringkas);

        // ---------- 2. Harian ----------
        $harian = $kepala('Rincian Keuangan Harian');
        $harian[] = $h(['Tanggal', 'Transaksi', 'Item', 'Penjualan', 'Modal', 'Laba Kotor', 'Pengeluaran', 'Untung Bersih']);
        foreach ($d['rincianHarian'] as $r) {
            $harian[] = [$r->tanggal->format('d/m/Y'), [$r->transaksi, 'angka'], [$r->item, 'angka'], [$r->penjualan, 'rp'],
                [$r->modal, 'rp'], [$r->laba_kotor, 'rp'], [$r->pengeluaran, 'rp'], [$r->bersih, $r->bersih >= 0 ? 'hijau_rp' : 'merah_rp']];
        }
        if ($d['rincianHarian']->count()) {
            $harian[] = [['TOTAL', 'tebal'], [(int) $d['rincianHarian']->sum('transaksi'), 'tebal'], [(int) $d['rincianHarian']->sum('item'), 'tebal'],
                [(int) $d['rincianHarian']->sum('penjualan'), 'tebal_rp'], [(int) $d['rincianHarian']->sum('modal'), 'tebal_rp'],
                [(int) $d['rincianHarian']->sum('laba_kotor'), 'tebal_rp'], [(int) $d['rincianHarian']->sum('pengeluaran'), 'tebal_rp'],
                [(int) $d['rincianHarian']->sum('bersih'), 'tebal_rp']];
        } else {
            $harian[] = [['Belum ada data pada periode ini.', 'catatan']];
        }
        $xlsx->sheet('Harian', $harian);

        // ---------- 3. Penjualan per menu ----------
        $menu = $kepala('Penjualan per Menu');
        $menu[] = $h(['No', 'Menu', 'Qty', 'Omzet', 'Laba']);
        foreach ($d['penjualanMenu'] as $i => $m) {
            $menu[] = [[$i + 1, 'angka'], $m->nama_menu, [(int) $m->qty, 'angka'], [(int) $m->omzet, 'rp'], [(int) $m->laba, (int) $m->laba >= 0 ? 'hijau_rp' : 'merah_rp']];
        }
        if ($d['penjualanMenu']->isEmpty()) {
            $menu[] = [['Belum ada penjualan pada periode ini.', 'catatan']];
        }
        $xlsx->sheet('Penjualan per Menu', $menu);

        // ---------- 4. Metode bayar & kasir ----------
        $mk = $kepala('Penjualan per Metode Bayar & per Kasir');
        $mk[] = $h(['Metode Bayar', 'Transaksi', 'Total']);
        foreach ($d['perMetode'] as $m) {
            $mk[] = [$labelMetode[$m->metode_bayar] ?? strtoupper((string) $m->metode_bayar), [(int) $m->jumlah, 'angka'], [(int) $m->total, 'rp']];
        }
        $mk[] = [];
        $mk[] = $h(['Kasir', 'Transaksi', 'Total']);
        foreach ($d['perKasir'] as $k) {
            $mk[] = [$k->name, [(int) $k->jumlah, 'angka'], [(int) $k->total, 'rp']];
        }
        $xlsx->sheet('Metode & Kasir', $mk);

        // ---------- 5. Pengeluaran ----------
        $pg = $kepala('Pengeluaran');
        $pg[] = $h(['No', 'Tanggal', 'Kategori', 'Keterangan', 'Dicatat Oleh', 'Jumlah']);
        foreach ($d['daftarPengeluaran'] as $i => $p) {
            $pg[] = [[$i + 1, 'angka'], $p->tanggal->format('d/m/Y'), $labelKategori[$p->kategori] ?? $p->kategori, $p->deskripsi, $p->pencatat->name ?? '-', [(int) $p->jumlah, 'rp']];
        }
        if ($d['daftarPengeluaran']->count()) {
            foreach ($labelKategori as $key => $label) {
                $pg[] = ['', '', '', '', ["Subtotal {$label}", 'tebal'], [(int) ($d['pengeluaranPerKategori'][$key]->total ?? 0), 'tebal_rp']];
            }
            $pg[] = ['', '', '', '', ['TOTAL PENGELUARAN', 'tebal'], [$d['totalPengeluaran'], 'tebal_rp']];
        } else {
            $pg[] = [['Belum ada pengeluaran pada periode ini.', 'catatan']];
        }
        $xlsx->sheet('Pengeluaran', $pg);

        // ---------- 6. Pre-order ----------
        $po = $kepala('Pre-Order (berdasarkan tanggal ambil)');
        $po[] = $h(['Status', 'Jumlah', 'Total Pesanan', 'DP Diterima']);
        foreach ($d['labelPreOrder'] as $key => $label) {
            if (! isset($d['preOrderStatus'][$key])) {
                continue;
            }
            $s = $d['preOrderStatus'][$key];
            $po[] = [$label, [(int) $s->jumlah, 'angka'], [(int) $s->total, 'rp'], [(int) $s->dp, 'rp']];
        }
        if ($d['totalPreOrder'] === 0) {
            $po[] = [['Tidak ada pre-order pada periode ini.', 'catatan']];
        }
        $po[] = [];
        $po[] = [['Pre-order berstatus Selesai otomatis tercatat sebagai transaksi sehingga sudah termasuk dalam penjualan.', 'catatan']];
        $xlsx->sheet('Pre-Order', $po);

        // ---------- 7. Stok ----------
        $st = $kepala('Laporan Stok Menu');
        $st[] = $h(['Menu', 'Kategori', 'Masuk', 'Keluar', 'Stok Saat Ini', 'Minimum', 'Status']);
        foreach ($d['stokMenu'] as $s) {
            $st[] = [$s->nama, $s->kategori, [$s->masuk, 'angka'], [$s->keluar, 'angka'], [$s->stok, 'angka'], [$s->minimum, 'angka'], $labelStatus[$s->status] ?? $s->status];
        }
        $st[] = [];
        $st[] = [['Laporan Stok Saus & Bumbu Tabur', 'judul']];
        $st[] = $h(['Nama', 'Jenis', 'Terpakai (Periode)', 'Stok Saat Ini', 'Minimum', 'Satuan', 'Status']);
        foreach ($d['stokBumbu'] as $b) {
            $st[] = [$b->nama, $b->jenis, [$b->dipakai, 'angka'], [$b->stok, 'angka'], [$b->minimum, 'angka'], $b->satuan, $labelStatus[$b->status] ?? $b->status];
        }
        $xlsx->sheet('Stok', $st);

        // ---------- 7b. Stok operasional ----------
        $so = $kepala('Laporan Stok Operasional');
        $so[] = $h(['Bahan', 'Satuan', 'Terpakai (Periode)', 'Dikembalikan', 'Isi Ulang', 'Stok Saat Ini (Semua Kasir)', 'Kasir Menipis', 'Kasir Habis']);
        foreach ($d['stokOperasional'] as $o) {
            $so[] = [$o->nama, $o->satuan, [$o->terpakai, 'angka'], [$o->kembali, 'angka'], [$o->isi_ulang, 'angka'], [$o->stok, 'angka'], [$o->kasir_menipis, 'angka'], [$o->kasir_habis, 'angka']];
        }
        if ($d['stokOperasional']->isEmpty()) {
            $so[] = [['Belum ada data stok operasional.', 'catatan']];
        }
        $so[] = [];
        $so[] = [['Rincian per Kasir', 'judul']];
        $so[] = $h(['Kasir', 'Bahan', 'Satuan', 'Terpakai (Periode)', 'Isi Ulang', 'Stok Saat Ini', 'Minimum', 'Status']);
        foreach ($d['stokOperasionalKasir'] as $o) {
            $so[] = [$o->kasir, $o->bahan, $o->satuan, [$o->terpakai, 'angka'], [$o->isi_ulang, 'angka'], [$o->stok, 'angka'], [$o->minimum, 'angka'], $labelStatus[$o->status] ?? $o->status];
        }
        $xlsx->sheet('Stok Operasional', $so);

        // ---------- 8. Daftar transaksi ----------
        $tr = $kepala('Daftar Transaksi');
        $tr[] = $h(['No', 'Kode', 'Tanggal', 'Kasir', 'Metode', 'Total']);
        foreach ($daftarTransaksi as $i => $t) {
            $tr[] = [[$i + 1, 'angka'], $t->kode_transaksi, $t->created_at->format('d/m/Y H:i'), $t->kasir->name ?? '-', $labelMetode[$t->metode_bayar] ?? strtoupper((string) $t->metode_bayar), [(int) $t->total, 'rp']];
        }
        if ($daftarTransaksi->count()) {
            $tr[] = ['', '', '', '', ['TOTAL', 'tebal'], [$d['totalPenjualan'], 'tebal_rp']];
        } else {
            $tr[] = [['Tidak ada transaksi pada periode ini.', 'catatan']];
        }
        if ($d['daftarBatal']->count()) {
            $tr[] = [];
            $tr[] = [['Transaksi Dibatalkan', 'judul']];
            $tr[] = $h(['No', 'Kode', 'Tanggal', 'Kasir', 'Alasan', 'Total']);
            foreach ($d['daftarBatal'] as $i => $t) {
                $tr[] = [[$i + 1, 'angka'], $t->kode_transaksi, $t->created_at->format('d/m/Y H:i'), $t->kasir->name ?? '-', $t->alasan_batal ?? '-', [(int) $t->total, 'rp']];
            }
        }
        $xlsx->sheet('Transaksi', $tr);

        $file = tempnam(sys_get_temp_dir(), 'laporan_');
        $xlsx->simpan($file);

        $nama = 'Laporan_PotatoCurly_' . $dari->format('Ymd') . '-' . $sampai->format('Ymd') . '.xlsx';

        return response()->download($file, $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function dataLaporan(Carbon $dari, Carbon $sampai): array
    {
        $tglDari = $dari->toDateString();
        $tglSampai = $sampai->toDateString();

        $base = fn() => Transaksi::where('transaksis.status', 'selesai')->whereBetween('transaksis.created_at', [$dari, $sampai]);

        // ================= PENJUALAN =================
        $totalPenjualan = (int) $base()->sum('total');
        $jumlahTransaksi = $base()->count();
        $rataRata = $jumlahTransaksi > 0 ? (int) round($totalPenjualan / $jumlahTransaksi) : 0;
        $itemTerjual = (int) $this->itemQuery($dari, $sampai)->sum('ti.qty');

        // --- Laba: penjualan item dikurangi modal (modal tersimpan per item; item lama pakai modal menu saat ini) ---
        $modalExpr = 'COALESCE(ti.modal_satuan, m_modal.harga_modal, 0)';
        $hitungLaba = $this->itemQuery($dari, $sampai)
            ->leftJoin('menus as m_modal', 'm_modal.id', '=', 'ti.menu_id')
            ->selectRaw("COALESCE(SUM(ti.subtotal), 0) as omzet, COALESCE(SUM(ti.qty * $modalExpr), 0) as modal")
            ->first();
        $totalModal = (int) $hitungLaba->modal;
        $labaKotor = (int) $hitungLaba->omzet - $totalModal;
        $marginPersen = $hitungLaba->omzet > 0 ? round($labaKotor / $hitungLaba->omzet * 100, 1) : 0;
        $menuModalKosong = $this->itemQuery($dari, $sampai)
            ->leftJoin('menus as m_modal', 'm_modal.id', '=', 'ti.menu_id')
            ->whereRaw("$modalExpr = 0")
            ->distinct()->count('ti.menu_id');

        // ================= PENGELUARAN & KEUNTUNGAN BERSIH =================
        // Hanya Operasional + Lainnya yang mengurangi laba. "Bahan Baku" tidak dikurangkan lagi karena
        // biayanya sudah masuk Modal (HPP) tiap item yang terjual -> supaya tidak terpotong dua kali.
        $pengeluaranPerKategori = Pengeluaran::rentang($tglDari, $tglSampai)
            ->selectRaw('kategori, COUNT(*) as banyak, SUM(jumlah) as total')
            ->groupBy('kategori')
            ->get()
            ->keyBy('kategori');

        $totalPengeluaran = (int) $pengeluaranPerKategori->sum('total');
        $pengeluaranBahanBaku = (int) ($pengeluaranPerKategori['bahan_baku']->total ?? 0);
        $pengeluaranOperasional = (int) (($pengeluaranPerKategori['operasional']->total ?? 0) + ($pengeluaranPerKategori['lainnya']->total ?? 0));
        $keuntunganBersih = $labaKotor - $pengeluaranOperasional;
        $marginBersih = $hitungLaba->omzet > 0 ? round($keuntunganBersih / $hitungLaba->omzet * 100, 1) : 0;

        $daftarPengeluaran = Pengeluaran::rentang($tglDari, $tglSampai)
            ->with('pencatat')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        // ================= TRANSAKSI DIBATALKAN =================
        $daftarBatal = Transaksi::with('kasir')
            ->where('status', 'dibatalkan')
            ->whereBetween('created_at', [$dari, $sampai])
            ->orderBy('created_at')
            ->get();
        $transaksiBatal = $daftarBatal->count();
        $nominalBatal = (int) $daftarBatal->sum('total');

        // ================= RINCIAN HARIAN (hari tanpa aktivitas tidak ditampilkan) =================
        $hariTransaksi = $base()
            ->selectRaw('DATE(transaksis.created_at) as tgl, SUM(transaksis.total) as total, COUNT(*) as jumlah')
            ->groupBy('tgl')
            ->get()
            ->keyBy('tgl');

        $hariModal = $this->itemQuery($dari, $sampai)
            ->leftJoin('menus as m_modal', 'm_modal.id', '=', 'ti.menu_id')
            ->selectRaw("DATE(t.created_at) as tgl, SUM(ti.qty) as item, SUM(ti.qty * $modalExpr) as modal")
            ->groupBy('tgl')
            ->get()
            ->keyBy('tgl');

        $hariPengeluaran = Pengeluaran::rentang($tglDari, $tglSampai)->operasional()
            ->selectRaw('tanggal as tgl, SUM(jumlah) as total')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tgl');

        $semuaTanggal = collect($hariTransaksi->keys())
            ->merge($hariModal->keys())
            ->merge($hariPengeluaran->keys())
            ->unique()
            ->sort()
            ->values();

        $rincianHarian = $semuaTanggal->map(function ($tgl) use ($hariTransaksi, $hariModal, $hariPengeluaran) {
            $penjualan = (int) ($hariTransaksi[$tgl]->total ?? 0);
            $modal = (int) ($hariModal[$tgl]->modal ?? 0);
            $pengeluaran = (int) ($hariPengeluaran[$tgl]->total ?? 0);

            return (object) [
                'tanggal' => Carbon::parse($tgl),
                'transaksi' => (int) ($hariTransaksi[$tgl]->jumlah ?? 0),
                'item' => (int) ($hariModal[$tgl]->item ?? 0),
                'penjualan' => $penjualan,
                'modal' => $modal,
                'laba_kotor' => $penjualan - $modal,
                'pengeluaran' => $pengeluaran,
                'bersih' => $penjualan - $modal - $pengeluaran,
            ];
        });

        // ================= RINGKASAN PER KELOMPOK =================
        $perMetode = $base()
            ->selectRaw('metode_bayar, COUNT(*) as jumlah, SUM(total) as total')
            ->groupBy('metode_bayar')
            ->get();

        $penjualanMenu = $this->itemQuery($dari, $sampai)
            ->join('menus as m', 'm.id', '=', 'ti.menu_id')
            ->groupBy('m.id', 'm.nama_menu')
            ->selectRaw('m.nama_menu, SUM(ti.qty) as qty, SUM(ti.subtotal) as omzet, SUM(ti.subtotal - ti.qty * COALESCE(ti.modal_satuan, m.harga_modal, 0)) as laba')
            ->orderByDesc('qty')
            ->get();

        $perKasir = $base()
            ->join('users as u', 'u.id', '=', 'transaksis.user_id')
            ->groupBy('u.id', 'u.name')
            ->selectRaw('u.name, COUNT(*) as jumlah, SUM(transaksis.total) as total')
            ->orderByDesc('total')
            ->get();

        // ================= PRE-ORDER (berdasarkan tanggal ambil) =================
        $preOrderStatus = PreOrder::whereBetween('tanggal_ambil', [$tglDari, $tglSampai])
            ->selectRaw('status, COUNT(*) as jumlah, SUM(total) as total, SUM(dp) as dp')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $labelPreOrder = PreOrder::LABEL_STATUS;
        $totalPreOrder = (int) $preOrderStatus->sum('jumlah');

        // ================= STOK BAHAN =================
        // Stok menu saat ini + pergerakan (masuk/keluar) selama periode
        $gerak = PergerakanStok::whereBetween('tanggal', [$tglDari, $tglSampai])
            ->selectRaw('menu_id, jenis, SUM(jumlah) as total')
            ->groupBy('menu_id', 'jenis')
            ->get();

        $stokMenu = Menu::aktif()->with('kategori')->orderBy('nama_menu')->get()->map(function ($m) use ($gerak) {
            return (object) [
                'nama' => $m->nama_menu,
                'kategori' => $m->kategori->nama_kategori ?? '-',
                'stok' => (int) $m->stok,
                'minimum' => (int) $m->stok_minimum,
                'status' => $m->status_stok,
                'masuk' => (int) $gerak->where('menu_id', $m->id)->where('jenis', 'masuk')->sum('total'),
                'keluar' => (int) $gerak->where('menu_id', $m->id)->where('jenis', 'keluar')->sum('total'),
            ];
        });

        // Saus & bubuk tabur yang terpakai dari item transaksi selesai pada periode
        $pakaiSaus = $this->itemQuery($dari, $sampai)
            ->whereNotNull('ti.saus')->where('ti.saus', '!=', TransaksiController::TANPA_SAUS)
            ->groupBy('ti.saus')->selectRaw('ti.saus as nama, SUM(ti.qty) as qty')
            ->pluck('qty', 'nama');
        $pakaiBubuk = $this->itemQuery($dari, $sampai)
            ->whereNotNull('ti.bubuk_tabur')->where('ti.bubuk_tabur', '!=', TransaksiController::TANPA_BUBUK)
            ->groupBy('ti.bubuk_tabur')->selectRaw('ti.bubuk_tabur as nama, SUM(ti.qty) as qty')
            ->pluck('qty', 'nama');

        $stokBumbu = Bumbu::aktif()->orderBy('jenis', 'desc')->orderBy('nama')->get()->map(function ($b) use ($pakaiSaus, $pakaiBubuk) {
            $dipakai = $b->jenis === 'saus' ? ($pakaiSaus[$b->nama] ?? 0) : ($pakaiBubuk[$b->nama] ?? 0);

            return (object) [
                'nama' => $b->nama,
                'jenis' => $b->jenis === 'saus' ? 'Saus' : 'Bumbu tabur',
                'stok' => (int) $b->stok,
                'minimum' => (int) $b->stok_minimum,
                'satuan' => $b->satuan,
                'status' => $b->status_stok,
                'dipakai' => (int) $dipakai,
            ];
        });

        // Stok operasional (gas, minyak, dus, cup, es batu, dll): ringkasan per bahan + rincian per kasir
        [$stokOperasional, $stokOperasionalKasir] = $this->dataStokOperasional($dari, $sampai);

        $stokPerhatian = $stokMenu->whereIn('status', ['habis', 'menipis'])->count()
            + $stokBumbu->whereIn('status', ['habis', 'menipis'])->count();

        return compact(
            'dari', 'sampai',
            'totalPenjualan', 'jumlahTransaksi', 'rataRata', 'itemTerjual',
            'totalModal', 'labaKotor', 'marginPersen', 'menuModalKosong',
            'totalPengeluaran', 'pengeluaranBahanBaku', 'pengeluaranOperasional', 'keuntunganBersih', 'marginBersih',
            'pengeluaranPerKategori', 'daftarPengeluaran',
            'daftarBatal', 'transaksiBatal', 'nominalBatal',
            'rincianHarian', 'perMetode', 'penjualanMenu', 'perKasir',
            'preOrderStatus', 'labelPreOrder', 'totalPreOrder',
            'stokMenu', 'stokBumbu', 'stokPerhatian', 'stokOperasional', 'stokOperasionalKasir'
        );
    }

    /**
     * Laporan stok operasional. Return [ringkasan per bahan, rincian per kasir x bahan].
     * Terpakai / dikembalikan / isi ulang dihitung dari riwayat pada periode; stok = sisa saat ini.
     */
    private function dataStokOperasional(Carbon $dari, Carbon $sampai): array
    {
        if (! Schema::hasTable('bahan_operasionals') || ! Schema::hasTable('stok_operasionals')) {
            return [collect(), collect()];
        }

        $bahanList = BahanOperasional::orderBy('urutan')->orderBy('id')->get();
        $kasirs = User::kasir()->orderBy('name')->get(['id', 'name', 'status'])->keyBy('id');

        $gerak = collect();
        if (Schema::hasTable('riwayat_stok_operasionals')) {
            $gerak = RiwayatStokOperasional::whereBetween('created_at', [$dari, $sampai])
                ->selectRaw('user_id, bahan, jenis, SUM(jumlah) as total')
                ->groupBy('user_id', 'bahan', 'jenis')
                ->get();
        }
        $jumlah = fn ($user, $bahan, $jenis) => (int) $gerak
            ->where('bahan', $bahan)->where('jenis', $jenis)
            ->when($user !== null, fn ($c) => $c->where('user_id', $user))
            ->sum('total');

        $stokBaris = StokOperasional::whereIn('user_id', $kasirs->keys())->get();

        $ringkas = collect();
        $perKasir = collect();

        foreach ($bahanList as $b) {
            $baris = $stokBaris->where('bahan', $b->kode)->filter(fn ($r) => ($kasirs[$r->user_id]->status ?? 'aktif') === 'aktif');
            $minimum = (int) $b->stok_menipis;

            $terpakai = $jumlah(null, $b->kode, 'pakai');
            $isiUlang = $jumlah(null, $b->kode, 'isi_ulang');
            $kembali = $jumlah(null, $b->kode, 'kembali');

            if ($b->status !== 'aktif' && $terpakai + $isiUlang + $kembali === 0) {
                continue; // bahan nonaktif tanpa aktivitas tidak perlu tampil
            }

            $ringkas->push((object) [
                'nama' => $b->nama,
                'satuan' => $b->satuan,
                'terpakai' => $terpakai,
                'kembali' => $kembali,
                'isi_ulang' => $isiUlang,
                'stok' => (int) $baris->sum('stok'),
                'minimum' => $minimum,
                'kasir_menipis' => $baris->filter(fn ($r) => $r->stok > 0 && $r->stok <= $minimum)->count(),
                'kasir_habis' => $baris->filter(fn ($r) => $r->stok <= 0)->count(),
            ]);

            foreach ($baris as $r) {
                $stok = (int) $r->stok;
                $perKasir->push((object) [
                    'kasir' => $kasirs[$r->user_id]->name ?? '-',
                    'bahan' => $b->nama,
                    'satuan' => $b->satuan,
                    'terpakai' => $jumlah($r->user_id, $b->kode, 'pakai'),
                    'isi_ulang' => $jumlah($r->user_id, $b->kode, 'isi_ulang'),
                    'stok' => $stok,
                    'minimum' => $minimum,
                    'status' => $stok <= 0 ? 'habis' : ($stok <= $minimum ? 'menipis' : 'aman'),
                ]);
            }
        }

        return [$ringkas, $perKasir->sortBy([['kasir', 'asc']])->values()];
    }
}
