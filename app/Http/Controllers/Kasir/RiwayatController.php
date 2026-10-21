<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;

class RiwayatController extends Controller
{
    public function index()
    {
        $transaksis = Transaksi::milikKasir(auth()->id())
            ->latest()
            ->paginate(10);

        return view('kasir.riwayat.index', compact('transaksis'));
    }
}
