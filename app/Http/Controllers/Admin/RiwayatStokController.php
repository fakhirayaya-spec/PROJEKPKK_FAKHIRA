<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RiwayatStokQuery;
use Illuminate\Http\Request;

/** Riwayat pergerakan stok menu & bumbu untuk SEMUA kasir (ada pencarian & filter). */
class RiwayatStokController extends Controller
{
    public function menu(Request $request)
    {
        return $this->tampil('menu', $request);
    }

    public function bumbu(Request $request)
    {
        return $this->tampil('bumbu', $request);
    }

    private function tampil(string $tipe, Request $request)
    {
        return view('admin.riwayat-stok.index', [
            'tipe' => $tipe,
            'riwayat' => RiwayatStokQuery::jalankan($tipe, $request),
            'kasirs' => User::kasir()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
