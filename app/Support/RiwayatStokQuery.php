<?php

namespace App\Support;

use App\Models\PergerakanBumbu;
use App\Models\PergerakanStok;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/** Query riwayat pergerakan stok menu / bumbu dengan pencarian & filter (dipakai halaman admin dan kasir). */
class RiwayatStokQuery
{
    public static function jalankan(string $tipe, Request $request, ?int $kasirId = null): LengthAwarePaginator
    {
        $cari = trim((string) $request->query('q', ''));
        $adaTabel = $tipe === 'menu' || Schema::hasTable('pergerakan_bumbus');

        if (! $adaTabel) {
            return new LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url()]);
        }

        $q = $tipe === 'menu'
            ? PergerakanStok::with(['menu', 'kasir', 'user'])
            : PergerakanBumbu::with(['bumbu', 'kasir', 'user']);

        $kolomNama = $tipe === 'menu' ? 'nama_menu' : 'nama';
        $relasi = $tipe === 'menu' ? 'menu' : 'bumbu';

        return $q
            ->when($kasirId, fn ($w) => $w->where('kasir_id', $kasirId))
            ->when(! $kasirId && $request->filled('kasir_id'), fn ($w) => $w->where('kasir_id', $request->query('kasir_id')))
            ->when(in_array($request->query('jenis'), ['masuk', 'keluar'], true), fn ($w) => $w->where('jenis', $request->query('jenis')))
            ->when($request->filled('dari'), fn ($w) => $w->whereDate('tanggal', '>=', $request->query('dari')))
            ->when($request->filled('sampai'), fn ($w) => $w->whereDate('tanggal', '<=', $request->query('sampai')))
            ->when($cari !== '', function ($w) use ($cari, $kolomNama, $relasi, $tipe, $kasirId) {
                $like = '%' . addcslashes($cari, '%_\\') . '%';
                $w->where(function ($x) use ($like, $kolomNama, $relasi, $tipe, $kasirId) {
                    $x->where('keterangan', 'like', $like)
                        ->orWhereHas($relasi, fn ($i) => $i->where($kolomNama, 'like', $like));
                    if ($tipe === 'bumbu') {
                        $x->orWhere('nama', 'like', $like);
                    }
                    if (! $kasirId) {
                        $x->orWhereHas('kasir', fn ($k) => $k->where('name', 'like', $like));
                    }
                });
            })
            ->latest('tanggal')->latest('id')
            ->paginate(20)
            ->withQueryString();
    }
}
