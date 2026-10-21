<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bumbu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BumbuController extends Controller
{
    public function index()
    {
        $semua = Bumbu::orderBy('nama')->get();

        return view('admin.manajemen-bumbu.index', [
            'tabur' => $semua->where('jenis', 'tabur')->values(),
            'saus' => $semua->where('jenis', 'saus')->values(),
            'total' => $semua->count(),
            'menipis' => $semua->filter(fn ($b) => $b->status_stok === 'menipis')->count(),
            'habis' => $semua->filter(fn ($b) => $b->status_stok === 'habis')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(Bumbu::JENIS))],
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('bumbus', 'nama')->where('jenis', $request->jenis),
            ],
            'stok' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
        ], [
            'nama.unique' => 'Nama itu sudah dipakai di kelompok yang sama.',
        ]);

        Bumbu::create($data);

        return back()->with('sukses', '"' . $data['nama'] . '" berhasil ditambahkan.');
    }

    public function update(Request $request, Bumbu $bumbu)
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('bumbus', 'nama')->where('jenis', $bumbu->jenis)->ignore($bumbu->id),
            ],
            'stok' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
        ], [
            'nama.unique' => 'Nama itu sudah dipakai di kelompok yang sama.',
        ]);

        $bumbu->update($data);

        return back()->with('sukses', '"' . $bumbu->nama . '" berhasil diperbarui.');
    }

    public function tambahStok(Request $request, Bumbu $bumbu)
    {
        $data = $request->validate(['jumlah' => ['required', 'integer', 'min:1']]);

        $bumbu->increment('stok', $data['jumlah']);

        return back()->with('sukses', 'Stok "' . $bumbu->nama . '" bertambah ' . $data['jumlah'] . ' ' . $bumbu->satuan . '.');
    }

    public function toggle(Bumbu $bumbu)
    {
        $bumbu->update(['status' => $bumbu->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('sukses', '"' . $bumbu->nama . '" sekarang ' . $bumbu->status . '.');
    }

    public function destroy(Bumbu $bumbu)
    {
        $nama = $bumbu->nama;
        $bumbu->delete();

        return back()->with('sukses', '"' . $nama . '" dihapus.');
    }
}
