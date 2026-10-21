<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Kasir\TransaksiController;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\PergerakanStok;
use App\Models\PreOrder;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'stokMenu', 'stokBumbu', 'stokPerhatian'
        );
    }
}
