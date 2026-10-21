<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function edit()
    {
        return view('kasir.profil.edit', ['user' => auth()->user()]);
    }

    /** Kasir mengganti foto profilnya sendiri */
    public function update(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'avatar.required' => 'Pilih foto dulu.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Foto harus berformat JPG atau PNG.',
            'avatar.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $request->file('avatar')->store('kasir', 'public')]);

        return redirect()->route('kasir.profil.edit')->with('sukses', 'Foto profil berhasil diperbarui.');
    }

    public function hapusFoto(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return redirect()->route('kasir.profil.edit')->with('sukses', 'Foto profil dihapus.');
    }
}
