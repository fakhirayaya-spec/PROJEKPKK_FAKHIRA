<?php

namespace App\Http\Controllers;

use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'kasir') {
            return redirect()->route('kasir.dashboard');
        }

        // --- Rentang tanggal (default 7 hari terakhir, bisa diganti lewat date picker) ---
        $request->validate(['dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date']]);
        $sampai = $request->filled('sampai') ? Carbon::parse($request->sampai)->endOfDay() : now()->endOfDay();
        $dari = $request->filled('dari') ? Carbon::parse($request->dari)->startOfDay() : now()->subDays(6)->startOfDay();
        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
        }
        if ($dari->diffInDays($sampai) > 90) {
            $dari = $sampai->copy()->subDays(90)->startOfDay();
        }

        // --- Kasir ---
        $totalKasir = User::kasir()->count();
        $kasirAktif = User::kasir()->aktif()->count();

        // --- Stok: menu + saus + bumbu tabur (semua yang aktif) ---
        $bahan = collect();
        foreach (Menu::aktif()->with('kategori')->get() as $m) {
            $bahan->push((object) [
                'nama' => $m->nama_menu, 'jenis' => $m->kategori->nama_kategori ?? 'Menu',
                'stok' => $m->stok, 'satuan' => 'pcs', 'status' => $m->status_stok,
            ]);
        }
        foreach (Bumbu::aktif()->get() as $b) {
            $bahan->push((object) [
                'nama' => $b->nama, 'jenis' => $b->jenis === 'saus' ? 'Saus' : 'Bumbu tabur',
                'stok' => $b->stok, 'satuan' => $b->satuan, 'status' => $b->status_stok,
            ]);
        }

        $stokAman = $bahan->where('status', 'aman')->count();
        $stokMenipis = $bahan->where('status', 'menipis')->count();
        $stokHabis = $bahan->where('status', 'habis')->count();
        $totalItemStok = max($bahan->count(), 1);

        $peringatanStok = $bahan->whereIn('status', ['habis', 'menipis'])
            ->sortBy(fn ($b) => [$b->status === 'habis' ? 0 : 1, $b->stok])
            ->take(5)->values();

        // --- Penjualan hari ini vs kemarin ---
        $penjualanHariIni = (int) Transaksi::hariIni()->where('status', 'selesai')->sum('total');
        $jumlahTransaksiHariIni = Transaksi::hariIni()->where('status', 'selesai')->count();
        $penjualanKemarin = (int) Transaksi::whereDate('created_at', today()->subDay())->where('status', 'selesai')->sum('total');
        $persenPenjualan = $penjualanKemarin > 0
            ? round((($penjualanHariIni - $penjualanKemarin) / $penjualanKemarin) * 100, 1)
            : null; // belum ada data pembanding

        // --- Data sesuai rentang tanggal ---
        $dalamRentang = fn () => Transaksi::where('status', 'selesai')->whereBetween('created_at', [$dari, $sampai]);

        $totalRentang = (int) $dalamRentang()->sum('total');
        $jumlahRentang = $dalamRentang()->count();

        // --- Keuntungan (laba kotor dikurangi pengeluaran operasional) di rentang terpilih ---
        $totalModalRentang = (int) DB::table('transaksi_items as ti')
            ->join('transaksis as t', 't.id', '=', 'ti.transaksi_id')
            ->leftJoin('menus as m', 'm.id', '=', 'ti.menu_id')
            ->where('t.status', 'selesai')
            ->whereBetween('t.created_at', [$dari, $sampai])
            ->selectRaw('COALESCE(SUM(ti.qty * COALESCE(ti.modal_satuan, m.harga_modal, 0)), 0) as modal')
            ->value('modal');
        $labaKotorRentang = $totalRentang - $totalModalRentang;
        // Hanya pengeluaran operasional+lainnya yang mengurangi laba (lihat catatan
        // panjang di LaporanController@index) — bahan baku sudah masuk HPP per item.
        $pengeluaranRentang = (int) Pengeluaran::rentang($dari->toDateString(), $sampai->toDateString())->operasional()->sum('jumlah');
        $keuntunganBersihRentang = $labaKotorRentang - $pengeluaranRentang;

        $transaksiTerbaru = $dalamRentang()->with('kasir')->latest()->limit(5)->get();

        // Menu terlaris dihitung dari item transaksi yang benar-benar tercatat
        $menuTerlaris = DB::table('transaksi_items as ti')
            ->join('transaksis as t', 't.id', '=', 'ti.transaksi_id')
            ->join('menus as m', 'm.id', '=', 'ti.menu_id')
            ->where('t.status', 'selesai')
            ->whereBetween('t.created_at', [$dari, $sampai])
            ->groupBy('m.id', 'm.nama_menu')
            ->selectRaw('m.nama_menu, SUM(ti.qty) as terjual')
            ->orderByDesc('terjual')
            ->limit(3)
            ->get();

        // Grafik harian (hari tanpa transaksi tetap tampil 0), nilai dalam rupiah asli
        $harian = $dalamRentang()
            ->selectRaw('DATE(created_at) as tgl, SUM(total) as total, COUNT(*) as jumlah')
            ->groupBy('tgl')->get()->keyBy('tgl');

        $labelHari = $dataPenjualan = $dataJumlahTransaksi = [];
        foreach (CarbonPeriod::create($dari->copy()->startOfDay(), $sampai->copy()->startOfDay()) as $hari) {
            $key = $hari->format('Y-m-d');
            $labelHari[] = $hari->translatedFormat('d M');
            $dataPenjualan[] = (int) ($harian[$key]->total ?? 0);
            $dataJumlahTransaksi[] = (int) ($harian[$key]->jumlah ?? 0);
        }

        return view('dashboard', compact(
            'dari', 'sampai', 'totalKasir', 'kasirAktif',
            'stokAman', 'stokMenipis', 'stokHabis', 'totalItemStok', 'peringatanStok',
            'menuTerlaris', 'penjualanHariIni', 'jumlahTransaksiHariIni', 'persenPenjualan',
            'totalRentang', 'jumlahRentang', 'transaksiTerbaru',
            'labelHari', 'dataPenjualan', 'dataJumlahTransaksi',
            'labaKotorRentang', 'pengeluaranRentang', 'keuntunganBersihRentang',
        ));
    }
}
