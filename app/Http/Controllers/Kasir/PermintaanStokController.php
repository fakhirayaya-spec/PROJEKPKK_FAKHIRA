<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\PermintaanStok;
use Illuminate\Http\Request;

/** Kasir mengajukan stok (operasional / menu / bumbu) ke admin, dan melihat status pengajuannya. */
class PermintaanStokController extends Controller
{
    public function index()
    {
        $riwayat = PermintaanStok::where('user_id', auth()->id())->latest()->latest('id')->paginate(10);

        return view('kasir.permintaan-stok.index', [
            'riwayat' => $riwayat,
            'bahanList' => BahanOperasional::aktif()->orderBy('urutan')->orderBy('id')->get(),
            'menuList' => Menu::aktif()->orderBy('nama_menu')->get(['id', 'nama_menu', 'stok']),
            'bumbuList' => Bumbu::aktif()->orderBy('jenis')->orderBy('nama')->get(['id', 'nama', 'jenis', 'stok', 'satuan']),
            'jenisList' => PermintaanStok::JENIS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:operasional,menu,bumbu'],
            'bahan_kode' => ['required_if:jenis,operasional', 'nullable', 'string'],
            'menu_id' => ['required_if:jenis,menu', 'nullable', 'integer'],
            'bumbu_id' => ['required_if:jenis,bumbu', 'nullable', 'integer'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:200'],
        ], [
            'jenis.required' => 'Pilih jenis stok yang diajukan.',
            'bahan_kode.required_if' => 'Pilih bahan operasional yang diajukan.',
            'menu_id.required_if' => 'Pilih menu yang diajukan.',
            'bumbu_id.required_if' => 'Pilih bumbu yang diajukan.',
            'jumlah.required' => 'Jumlah yang diajukan wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => 'Jumlah terlalu besar (maksimal 1000).',
        ]);

        $itemId = null;
        $kode = null;

        if ($data['jenis'] === 'operasional') {
            $bahan = BahanOperasional::aktif()->where('kode', $data['bahan_kode'])->first();
            abort_if(! $bahan, 422, 'Bahan operasional tidak ditemukan atau sudah nonaktif.');
            $nama = $bahan->nama;
            $satuan = $bahan->unit_isi ?: $bahan->satuan;
            $kode = $bahan->kode;
        } elseif ($data['jenis'] === 'menu') {
            $menu = Menu::aktif()->find($data['menu_id']);
            abort_if(! $menu, 422, 'Menu tidak ditemukan atau sudah nonaktif.');
            $nama = $menu->nama_menu;
            $satuan = 'porsi';
            $itemId = $menu->id;
        } else {
            $bumbu = Bumbu::aktif()->find($data['bumbu_id']);
            abort_if(! $bumbu, 422, 'Bumbu tidak ditemukan atau sudah nonaktif.');
            $nama = $bumbu->nama;
            $satuan = $bumbu->satuan;
            $itemId = $bumbu->id;
        }

        // Satu barang cukup satu pengajuan yang masih menunggu, supaya admin tidak dibanjiri pengajuan ganda
        $sudahAda = PermintaanStok::menunggu()
            ->where('user_id', auth()->id())
            ->where('jenis', $data['jenis'])
            ->where('nama_item', $nama)
            ->exists();

        if ($sudahAda) {
            return back()->withInput()->withErrors(['jumlah' => "Pengajuan {$nama} milikmu masih menunggu admin. Batalkan dulu kalau mau mengajukan ulang."]);
        }

        PermintaanStok::create([
            'user_id' => auth()->id(),
            'jenis' => $data['jenis'],
            'item_id' => $itemId,
            'bahan_kode' => $kode,
            'nama_item' => $nama,
            'satuan' => $satuan,
            'jumlah' => (int) $data['jumlah'],
            'catatan' => $data['catatan'] ?? null,
            'status' => 'menunggu',
        ]);

        return redirect()->route('kasir.permintaan-stok.index')
            ->with('sukses', "Pengajuan {$nama} ({$data['jumlah']} {$satuan}) terkirim ke admin.");
    }

    /** Kasir membatalkan pengajuannya sendiri selama belum ditangani admin */
    public function batal(PermintaanStok $permintaan)
    {
        abort_unless($permintaan->user_id === auth()->id(), 403);
        abort_unless($permintaan->status === 'menunggu', 403, 'Pengajuan ini sudah ditangani admin.');

        $permintaan->update(['status' => 'dibatalkan']);

        return back()->with('sukses', 'Pengajuan dibatalkan.');
    }
}
