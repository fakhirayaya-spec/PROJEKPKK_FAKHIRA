<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriMenu;
use Illuminate\Http\Request;

class KategoriMenuController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_menus,nama_kategori',
        ]);

        KategoriMenu::create($request->only('nama_kategori'));

        return back()->with('sukses', 'Kategori berhasil ditambahkan.');
    }

    public function destroy(KategoriMenu $kategoriMenu)
    {
        if ($kategoriMenu->menus()->exists()) {
            return back()->with('gagal', 'Kategori tidak bisa dihapus karena masih dipakai oleh menu lain.');
        }

        $kategoriMenu->delete();

        return back()->with('sukses', 'Kategori berhasil dihapus.');
    }
}
