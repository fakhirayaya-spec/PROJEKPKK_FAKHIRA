<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Pengeluaran;
use App\Models\RiwayatStokOperasional;
use App\Models\User;
use App\Services\StokOperasionalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StokOperasionalController extends Controller
{
    /** Stok operasional semua kasir + form isi ulang */
    public function index()
    {
        $kasirs = User::kasir()->orderBy('status')->orderBy('name')->get();
        $bahanList = StokOperasionalService::bahanAktif();

        // stok[user_id] = koleksi stok berurutan (kunci = kode bahan); kasir baru otomatis dapat stok awal
        $stok = [];
        $ringkas = ['habis' => 0, 'menipis' => 0];
        foreach ($kasirs as $kasir) {
            $stok[$kasir->id] = StokOperasionalService::stokKasir($kasir->id, $bahanList);

            if ($kasir->status === 'aktif') {
                foreach ($stok[$kasir->id] as $b) {
                    if ($b->status_stok !== 'aman') {
                        $ringkas[$b->status_stok]++;
                    }
                }
            }
        }

        return view('admin.stok-operasional.index', compact('kasirs', 'stok', 'bahanList', 'ringkas'));
    }

    /** Admin mengisi ulang satu bahan milik satu kasir */
    public function isiUlang(Request $request, User $user)
    {
        abort_unless($user->role === 'kasir', 404);

        $data = $request->validate([
            'bahan' => ['required', 'exists:bahan_operasionals,kode'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'biaya' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'keterangan' => ['nullable', 'string', 'max:150'],
        ], [
            'biaya.required' => 'Biaya pembelian wajib diisi supaya tercatat di Pengeluaran.',
            'biaya.min' => 'Biaya pembelian minimal Rp 1.',
            'biaya.integer' => 'Biaya pembelian harus berupa angka.',
            'jumlah.required' => 'Jumlah isi ulang wajib diisi.',
            'jumlah.min' => 'Jumlah isi ulang minimal 1.',
            'jumlah.max' => 'Jumlah isi ulang terlalu besar (maksimal 1000).',
        ]);

        $bahan = BahanOperasional::aktif()->where('kode', $data['bahan'])->firstOrFail();

        // Stok bertambah dan pengeluarannya tercatat dalam satu paket: kalau salah satu gagal, keduanya dibatalkan.
        $tambah = DB::transaction(function () use ($user, $bahan, $data) {
            $tambah = StokOperasionalService::isiUlang(
                $user,
                $bahan,
                (int) $data['jumlah'],
                auth()->user(),
                $data['keterangan'] ?? null,
            );

            // Kategori mengikuti pengaturan bahan di Kelola Bahan (Operasional atau Bahan Baku)
            Pengeluaran::create([
                'tanggal' => today(),
                'kategori' => $bahan->kategori_pengeluaran ?: 'operasional',
                'deskripsi' => Str::limit("Isi ulang {$bahan->nama}: {$data['jumlah']} {$bahan->unit_isi} untuk kasir {$user->name}", 150, ''),
                'jumlah' => (int) $data['biaya'],
                'user_id' => auth()->id(),
            ]);

            return $tambah;
        });

        return back()->with('sukses', "{$bahan->nama} milik {$user->name} ditambah {$tambah} {$bahan->satuan}. Pengeluaran Rp " . number_format((int) $data['biaya'], 0, ',', '.') . ' tercatat.');
    }

    /** Riwayat pemakaian semua kasir (bisa difilter per kasir / bahan / jenis) */
    public function riwayat(Request $request)
    {
        $riwayat = RiwayatStokOperasional::with(['kasir', 'pelaku', 'master'])
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('user_id', $request->kasir_id))
            ->when($request->filled('bahan'), fn ($q) => $q->where('bahan', $request->bahan))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.stok-operasional.riwayat', [
            'riwayat' => $riwayat,
            'kasirs' => User::kasir()->orderBy('name')->get(['id', 'name']),
            'bahanList' => BahanOperasional::orderBy('urutan')->orderBy('id')->get(['kode', 'nama']),
        ]);
    }
}
