<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::aktif()
            ->when($request->cari, fn($q) => $q->where('nama_menu', 'like', '%' . $request->cari . '%'))
            ->orderBy('nama_menu')
            ->paginate(10)
            ->withQueryString();

        return view('kasir.stok.index', compact('menus'));
    }
}
