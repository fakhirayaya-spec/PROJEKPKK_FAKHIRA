<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\PergerakanStok;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::with('kategori');

        if ($request->filled('cari')) {
            $query->where('nama_menu', 'like', '%' . $request->cari . '%');
        }

        $menus = $query->orderBy('nama_menu')->paginate(10)->withQueryString();

        // Hitung status stok tiap menu untuk filter (kalau dipilih)
        if ($request->filled('status_stok')) {
            $menus = $query->get()->filter(function ($menu) use ($request) {
                return $menu->status_stok === $request->status_stok;
            });
        }

        return view('admin.manajemen-stok.index', compact('menus'));
    }

    public function tambahStok(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:150',
        ]);

        PergerakanStok::create([
            'menu_id' => $menu->id,
            'user_id' => auth()->id(),
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?? 'Restock manual',
            'tanggal' => now()->toDateString(),
        ]);

        $menu->increment('stok', $data['jumlah']);

        return back()->with('sukses', 'Stok "' . $menu->nama_menu . '" berhasil ditambahkan.');
    }

    public function riwayat(Menu $menu)
    {
        $riwayat = $menu->pergerakanStoks()->with('user')->latest('tanggal')->latest('id')->take(20)->get();

        return view('admin.manajemen-stok.riwayat', compact('menu', 'riwayat'));
    }
}
