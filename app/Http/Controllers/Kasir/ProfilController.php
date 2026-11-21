<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Support\FotoStorage;
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
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'avatar.required' => 'Pilih foto dulu.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Foto harus berformat JPG atau PNG.',
            'avatar.max' => 'Ukuran foto maksimal 5 MB.',
            'avatar.uploaded' => 'Foto gagal diunggah. Ukuran maksimal 5 MB (dan batas upload di php.ini minimal 5M).',
        ]);

        $user = $request->user();

        try {
            $path = $request->file('avatar')->store('kasir', 'public');

            // Pastikan file benar-benar ada di tempat browser membacanya (public/storage), kalau tidak foto tampil "Not Found"
            if (! $path || ! FotoStorage::pastikanTersaji($path, public_path('storage'), storage_path('app/public'))) {
                return back()->withErrors(['avatar' => 'Foto gagal disimpan di server. Coba lagi.']);
            }

            // Foto lama baru dihapus setelah foto baru pasti tersimpan
            if ($user->avatar && $user->avatar !== $path) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->update(['avatar' => $path]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['avatar' => 'Foto gagal disimpan. Coba lagi dengan foto JPG/PNG di bawah 5 MB.']);
        }

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
