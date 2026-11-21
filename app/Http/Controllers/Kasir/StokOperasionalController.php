<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\RiwayatStokOperasional;
use App\Services\StokOperasionalService;
use Illuminate\Http\Request;

class StokOperasionalController extends Controller
{
    /** Kasir hanya melihat stok & riwayat miliknya sendiri (tidak bisa menambah) */
    public function index(Request $request)
    {
        $userId = auth()->id();

        $stok = StokOperasionalService::stokKasir($userId);

        $riwayat = RiwayatStokOperasional::with(['pelaku', 'master'])
            ->where('user_id', $userId)
            ->when($request->filled('bahan'), fn ($q) => $q->where('bahan', $request->bahan))
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('kasir.stok-operasional.index', [
            'stok' => $stok,
            'riwayat' => $riwayat,
            'bahanList' => BahanOperasional::orderBy('urutan')->orderBy('id')->get(['kode', 'nama']),
        ]);
    }
}
