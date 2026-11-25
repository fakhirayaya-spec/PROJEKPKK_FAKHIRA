<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Support\RiwayatStokQuery;
use Illuminate\Http\Request;

/** Riwayat pergerakan stok menu & bumbu milik kasir yang login saja. */
class RiwayatStokController extends Controller
{
    public function index(Request $request)
    {
        $tipe = $request->query('tipe') === 'bumbu' ? 'bumbu' : 'menu';

        return view('kasir.riwayat-stok.index', [
            'tipe' => $tipe,
            'riwayat' => RiwayatStokQuery::jalankan($tipe, $request, (int) auth()->id()),
        ]);
    }
}
