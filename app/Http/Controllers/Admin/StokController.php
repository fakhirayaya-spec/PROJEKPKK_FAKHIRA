<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\StokBatch;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Manajemen stok menu. Stok dimiliki per kasir: admin memilih kasirnya dulu,
 * lalu melihat / menambah stok khusus untuk kasir itu.
 */
class StokController extends Controller
{
    private function kasirAktif()
    {
        return User::kasir()->aktif()->orderBy('name')->get();
    }

    public function index(Request $request)
    {
        $kasirs = $this->kasirAktif();
        $kasirTerpilih = $kasirs->firstWhere('id', (int) $request->kasir) ?? $kasirs->first();

        $menus = new LengthAwarePaginator([], 0, 10, 1);
        $expMenu = collect();
        $daftarIsi = collect(); // semua menu (tanpa filter) untuk form "Isi Semua Stok"

        if ($kasirTerpilih) {
            $query = Menu::with('kategori');

            if ($request->filled('cari')) {
                $query->where('nama_menu', 'like', '%' . $request->cari . '%');
            }

            $semua = $query->orderBy('nama_menu')->get();

            // angka stok yang tampil = stok milik kasir terpilih
            StokService::terapkanStokKasir($semua, 'menu', $kasirTerpilih->id);

            if ($request->filled('status_stok')) {
                $semua = $semua->filter(fn ($menu) => $menu->status_stok === $request->status_stok)->values();
            }

            $halaman = Paginator::resolveCurrentPage();
            $menus = new LengthAwarePaginator(
                $semua->forPage($halaman, 10)->values(),
                $semua->count(),
                10,
                $halaman,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $expMenu = StokService::expTerdekat('menu', $kasirTerpilih->id);

            $daftarIsi = Menu::with('kategori')->orderBy('nama_menu')->get();
            StokService::terapkanStokKasir($daftarIsi, 'menu', $kasirTerpilih->id);
        }

        return view('admin.manajemen-stok.index', compact('menus', 'expMenu', 'kasirs', 'kasirTerpilih', 'daftarIsi'));
    }

    public function tambahStok(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:150',
            'tanggal_exp' => 'nullable|date|after_or_equal:today',
        ]);

        $kasir = User::findOrFail($data['kasir_id']);

        DB::transaction(function () use ($menu, $data) {
            PergerakanStok::create([
                'menu_id' => $menu->id,
                'user_id' => auth()->id(),
                'kasir_id' => $data['kasir_id'],
                'jenis' => 'masuk',
                'jumlah' => $data['jumlah'],
                'keterangan' => $data['keterangan'] ?? 'Restock manual',
                'tanggal' => now()->toDateString(),
            ]);

            // Stok masuk dicatat sebagai batch milik kasir itu dengan tanggal exp (default 14 hari dari hari ini)
            StokService::tambah($menu, (int) $data['jumlah'], (int) $data['kasir_id'], $data['tanggal_exp'] ?? null, $data['keterangan'] ?? 'Restock manual');
            StokService::catatBiayaMenu($menu, (int) $data['jumlah'], (int) $data['kasir_id'], auth()->id());
        });

        return back()->with('sukses', 'Stok "' . $menu->nama_menu . '" untuk ' . $kasir->name . ' berhasil ditambahkan.');
    }

    /**
     * Isi stok SEMUA menu untuk satu kasir sekaligus: tiap menu dibuat jadi 50 (sama dengan Master Menu).
     * Menu yang sudah 50 atau lebih dilewati. Mau isi jumlah lain untuk satu menu, pakai tombol Tambah Stok.
     */
    public function isiSemua(Request $request)
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'tanggal_exp' => 'nullable|date|after_or_equal:today',
        ], [
            'tanggal_exp.after_or_equal' => 'Tanggal exp tidak boleh sebelum hari ini.',
        ]);

        $kasir = User::findOrFail($data['kasir_id']);
        $target = StokService::STOK_STANDAR;
        $diisi = 0;

        DB::transaction(function () use ($data, $target, &$diisi) {
            foreach (Menu::with('kategori')->orderBy('id')->get() as $menu) {
                $kurang = $target - StokService::stokDi($menu, (int) $data['kasir_id']);
                if ($kurang <= 0) {
                    continue;
                }

                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => auth()->id(),
                    'kasir_id' => $data['kasir_id'],
                    'jenis' => 'masuk',
                    'jumlah' => $kurang,
                    'keterangan' => 'Isi semua stok (jadi ' . $target . ')',
                    'tanggal' => now()->toDateString(),
                ]);

                StokService::tambah($menu, $kurang, (int) $data['kasir_id'], $data['tanggal_exp'] ?? null, 'Isi semua stok');
                StokService::catatBiayaMenu($menu, $kurang, (int) $data['kasir_id'], auth()->id());
                $diisi++;
            }
        });

        if ($diisi === 0) {
            return back()->with('sukses', 'Semua menu ' . $kasir->name . ' sudah ' . $target . ' atau lebih, tidak ada yang perlu diisi.');
        }

        return back()->with('sukses', 'Stok ' . $diisi . ' menu untuk ' . $kasir->name . ' sekarang ' . $target . '. Biayanya tercatat di Pengeluaran (Bahan Baku).');
    }

    public function riwayat(Request $request, Menu $menu)
    {
        $kasirs = $this->kasirAktif();
        $kasirTerpilih = $kasirs->firstWhere('id', (int) $request->kasir) ?? $kasirs->first();
        abort_unless($kasirTerpilih, 404, 'Belum ada kasir aktif.');

        $menu->load('kategori');
        StokService::terapkanStokKasir([$menu], 'menu', $kasirTerpilih->id);

        $riwayat = $menu->pergerakanStoks()->with('user')
            ->where('kasir_id', $kasirTerpilih->id)
            ->latest('tanggal')->latest('id')->take(20)->get();

        $batches = StokBatch::where('tipe', 'menu')->where('item_id', $menu->id)
            ->where('user_id', $kasirTerpilih->id)
            ->orderByDesc('id')->take(10)->get();

        return view('admin.manajemen-stok.riwayat', compact('menu', 'riwayat', 'batches', 'kasirTerpilih'));
    }
}
