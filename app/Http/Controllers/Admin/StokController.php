<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\StokBatch;
use App\Services\StokService;
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

        $expMenu = StokService::expTerdekat('menu');

        return view('admin.manajemen-stok.index', compact('menus', 'expMenu'));
    }

    public function tambahStok(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:150',
            'tanggal_exp' => 'nullable|date|after_or_equal:today',
        ]);

        PergerakanStok::create([
            'menu_id' => $menu->id,
            'user_id' => auth()->id(),
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?? 'Restock manual',
            'tanggal' => now()->toDateString(),
        ]);

        // Stok masuk dicatat sebagai batch dengan tanggal exp (default 14 hari / 2 minggu dari hari ini)
        StokService::tambah($menu, (int) $data['jumlah'], $data['tanggal_exp'] ?? null, $data['keterangan'] ?? 'Restock manual');

        return back()->with('sukses', 'Stok "' . $menu->nama_menu . '" berhasil ditambahkan.');
    }

    public function riwayat(Menu $menu)
    {
        $riwayat = $menu->pergerakanStoks()->with('user')->latest('tanggal')->latest('id')->take(20)->get();

        $batches = StokBatch::where('tipe', 'menu')->where('item_id', $menu->id)->orderByDesc('id')->take(10)->get();

        return view('admin.manajemen-stok.riwayat', compact('menu', 'riwayat', 'batches'));
    }
}
