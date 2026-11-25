<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Services\StokService;
use Illuminate\Http\Request;

class StokController extends Controller
{
    /** Kasir hanya melihat stok menu & bumbu miliknya sendiri (read-only) */
    public function index(Request $request)
    {
        $menus = Menu::aktif()
            ->when($request->cari, fn($q) => $q->where('nama_menu', 'like', '%' . $request->cari . '%'))
            ->orderBy('nama_menu')
            ->paginate(10)
            ->withQueryString();

        StokService::terapkanStokKasir($menus, 'menu', (int) auth()->id());

        // Bumbu (saus & bubuk tabur) milik kasir ini, dipakai juga untuk pesanan/pre-order
        $bumbus = Bumbu::aktif()->orderBy('jenis')->orderBy('nama')->get();
        StokService::terapkanStokKasir($bumbus, 'bumbu', (int) auth()->id());

        return view('kasir.stok.index', compact('menus', 'bumbus'));
    }
}
