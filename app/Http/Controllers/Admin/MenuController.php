<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bumbu;
use App\Models\KategoriMenu;
use App\Models\Menu;
use App\Models\PergerakanStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        // 'terjual' dihitung dari transaksi yang benar-benar selesai (bukan kolom total_terjual yang bisa basi)
        $query = Menu::with(['kategori', 'sausBawaan', 'bubukBawaan'])
            ->withSum(['transaksiItems as terjual' => function ($q) {
                $q->whereHas('transaksi', fn ($t) => $t->where('status', 'selesai'));
            }], 'qty');

        if ($request->filled('cari')) {
            $query->where('nama_menu', 'like', '%' . $request->cari . '%');
        }

        if ($request->filled('kategori_menu_id')) {
            $query->where('kategori_menu_id', $request->kategori_menu_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $menus = $query->orderBy('kode_menu')->paginate(10)->withQueryString();
        $kategoris = KategoriMenu::orderBy('nama_kategori')->get();

        return view('admin.master-menu.index', compact('menus', 'kategoris'));
    }

    public function create()
    {
        $kategoris = KategoriMenu::orderBy('nama_kategori')->get();

        [$sausList, $taburList] = $this->daftarBumbu();

        return view('admin.master-menu.create', compact('kategoris', 'sausList', 'taburList'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        }

        $urutanBerikutnya = (Menu::max('id') ?? 0) + 1;
        $data['kode_menu'] = 'MN-' . str_pad($urutanBerikutnya, 3, '0', STR_PAD_LEFT);
        $data['total_terjual'] = 0;
        $data['stok'] = (int) ($data['stok'] ?? 0);
        $data['stok_minimum'] = (int) ($data['stok_minimum'] ?? 5);

        $menu = Menu::create($data);

        if ($menu->stok > 0) {
            PergerakanStok::create([
                'menu_id' => $menu->id,
                'user_id' => auth()->id(),
                'jenis' => 'masuk',
                'jumlah' => $menu->stok,
                'keterangan' => 'Stok awal saat menu dibuat',
                'tanggal' => today(),
            ]);
        }

        return redirect()->route('admin.menu.index')->with('sukses', 'Menu baru berhasil ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        $kategoris = KategoriMenu::orderBy('nama_kategori')->get();

        [$sausList, $taburList] = $this->daftarBumbu();

        return view('admin.master-menu.edit', compact('menu', 'kategoris', 'sausList', 'taburList'));
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $this->validasi($request);
        unset($data['stok']); // stok hanya diubah lewat Manajemen Stok supaya tercatat di riwayat
        if (! array_key_exists('stok_minimum', $data) || $data['stok_minimum'] === null) {
            unset($data['stok_minimum']);
        }

        if ($request->hasFile('gambar')) {
            if ($menu->gambar) {
                Storage::disk('public')->delete($menu->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        }

        $menu->update($data);

        return redirect()->route('admin.menu.index')->with('sukses', 'Menu berhasil diperbarui.');
    }

    public function destroy(Menu $menu)
    {
        // Menu yang sudah pernah terjual tidak boleh dihapus (riwayat transaksi masih memakainya)
        if ($menu->transaksiItems()->exists()) {
            return redirect()->route('admin.menu.index')
                ->with('gagal', "Menu \"{$menu->nama_menu}\" sudah pernah terjual, jadi tidak bisa dihapus. Ubah statusnya jadi Nonaktif saja.");
        }

        if ($menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);
        }

        $menu->delete();

        return redirect()->route('admin.menu.index')->with('sukses', 'Menu berhasil dihapus.');
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'nama_menu' => 'required|string|max:150',
            'kategori_menu_id' => 'required|exists:kategori_menus,id',
            'harga' => 'required|integer|min:0',
            'harga_modal' => 'nullable|integer|min:0',
            'stok' => 'nullable|integer|min:0',
            'stok_minimum' => 'nullable|integer|min:0',
            'saus_bawaan_id' => 'nullable|exists:bumbus,id',
            'bubuk_bawaan_id' => 'nullable|exists:bumbus,id',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $data['harga_modal'] = (int) ($data['harga_modal'] ?? 0);

        // Pastikan saus bawaan benar-benar saus, dan bubuk bawaan benar-benar bumbu tabur
        if (! empty($data['saus_bawaan_id']) && ! Bumbu::where('id', $data['saus_bawaan_id'])->where('jenis', 'saus')->exists()) {
            $data['saus_bawaan_id'] = null;
        }
        if (! empty($data['bubuk_bawaan_id']) && ! Bumbu::where('id', $data['bubuk_bawaan_id'])->where('jenis', 'tabur')->exists()) {
            $data['bubuk_bawaan_id'] = null;
        }
        $data['saus_bawaan_id'] = $data['saus_bawaan_id'] ?? null;
        $data['bubuk_bawaan_id'] = $data['bubuk_bawaan_id'] ?? null;

        return $data;
    }

    private function daftarBumbu(): array
    {
        return [
            Bumbu::jenis('saus')->orderBy('nama')->get(),
            Bumbu::jenis('tabur')->orderBy('nama')->get(),
        ];
    }
}
