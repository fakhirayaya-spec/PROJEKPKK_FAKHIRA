<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Transaksi;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $transaksiSayaHariIni = Transaksi::milikKasir($userId)->hariIni()->where('status', 'selesai')->count();
        $penjualanSayaHariIni = Transaksi::milikKasir($userId)->hariIni()->where('status', 'selesai')->sum('total');
        $stokMenipisHabis = Menu::aktif()->whereColumn('stok', '<=', 'stok_minimum')->count();
        $transaksiTerakhirSaya = Transaksi::milikKasir($userId)->latest()->limit(5)->get();

        return view('kasir.dashboard', compact(
            'transaksiSayaHariIni',
            'penjualanSayaHariIni',
            'stokMenipisHabis',
            'transaksiTerakhirSaya',
        ));
    }
}
