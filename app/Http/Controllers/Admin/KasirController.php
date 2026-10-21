<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $query = User::kasir();

        if ($request->filled('cari')) {
            $query->where('name', 'like', '%' . $request->cari . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $kasirs = $query->latest()->paginate(10)->withQueryString();

        return view('admin.data-kasir.index', compact('kasirs'));
    }

    public function create()
    {
        return view('admin.data-kasir.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'status' => 'required|in:aktif,nonaktif',
            'alamat' => 'nullable|string|max:255',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date|before:today',
        ]);

        // Foto TIDAK diisi admin. Kasir mengunggah fotonya sendiri lewat menu Profil kasir.
        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'kasir',
            'status' => $data['status'],
            'alamat' => $data['alamat'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
        ]);

        return redirect()->route('admin.kasir.index')->with('sukses', 'Akun kasir baru berhasil ditambahkan.');
    }

    public function edit(User $kasir)
    {
        return view('admin.data-kasir.edit', compact('kasir'));
    }

    public function update(Request $request, User $kasir)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $kasir->id,
            'status' => 'required|in:aktif,nonaktif',
            'alamat' => 'nullable|string|max:255',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date|before:today',
        ]);

        // Foto tidak bisa diubah dari sini (hanya kasir yang bersangkutan, di menu Profil).
        $kasir->update($data);

        return redirect()->route('admin.kasir.index')->with('sukses', 'Data kasir berhasil diperbarui.');
    }

    public function destroy(User $kasir)
    {
        if ($kasir->avatar) {
            Storage::disk('public')->delete($kasir->avatar);
        }

        $kasir->delete();

        return redirect()->route('admin.kasir.index')->with('sukses', 'Akun kasir berhasil dihapus.');
    }

    public function toggleStatus(User $kasir)
    {
        $kasir->update([
            'status' => $kasir->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return back()->with('sukses', 'Status kasir berhasil diubah.');
    }

    public function resetPassword(User $kasir)
    {
        $kasir->update([
            'password' => Hash::make('password123'),
        ]);

        return back()->with('sukses', 'Password ' . $kasir->name . ' berhasil direset ke: password123');
    }
}
