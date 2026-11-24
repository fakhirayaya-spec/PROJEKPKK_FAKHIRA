<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Transaksi;
use App\Services\StokService;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $transaksiSayaHariIni = Transaksi::milikKasir($userId)->hariIni()->where('status', 'selesai')->count();
        $penjualanSayaHariIni = Transaksi::milikKasir($userId)->hariIni()->where('status', 'selesai')->sum('total');
        $menuAktif = Menu::aktif()->get(['id', 'stok_minimum']);
        StokService::terapkanStokKasir($menuAktif, 'menu', (int) $userId); // stok milik kasir ini
        $stokMenipisHabis = $menuAktif->filter(fn ($m) => $m->stok <= $m->stok_minimum)->count();
        $transaksiTerakhirSaya = Transaksi::milikKasir($userId)->latest()->limit(5)->get();

        return view('kasir.dashboard', compact(
            'transaksiSayaHariIni',
            'penjualanSayaHariIni',
            'stokMenipisHabis',
            'transaksiTerakhirSaya',
        ));
    }
}
