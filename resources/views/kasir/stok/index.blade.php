@extends('layouts.kasir')

@section('title', 'Cek Stok')

@push('styles')
<style>
    .badge-aman { background-color: #dcfce7; color: #16a34a; }
    .badge-menipis { background-color: #fef3c7; color: #b45309; }
    .thumb-stok { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #efe8e0; }
</style>
@endpush

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4 d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-potato-brown">Cek Stok</h4>
            <p class="text-muted small mb-0">Lihat ketersediaan stok menu dan bumbu milikmu (read-only, tidak bisa diubah dari sini).</p>
        </div>
        <a href="{{ route('kasir.riwayat-stok.index') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
            <i class="bi bi-clock-history"></i> Riwayat Stok
        </a>
    </div>

    <div class="card-custom bg-white p-3 mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm"
                    placeholder="Cari nama menu...">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-potato w-100">Cari</button>
            </div>
        </form>
    </div>

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>MENU</th>
                        <th>KATEGORI</th>
                        <th class="text-end">STOK SAAT INI</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $menu->foto_url }}"
                                        class="thumb-stok">
                                    <span class="fw-semibold text-potato-brown">{{ $menu->nama_menu }}</span>
                                </div>
                            </td>
                            <td>{{ $menu->kategori->nama_kategori ?? '-' }}</td>
                            <td class="text-end fw-bold">{{ $menu->stok }}</td>
                            <td class="text-center">
                                @if($menu->status_stok == 'habis')
                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Habis</span>
                                @elseif($menu->status_stok == 'menipis')
                                    <span class="badge badge-menipis px-2 py-1">Menipis</span>
                                @else
                                    <span class="badge badge-aman px-2 py-1">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada menu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $menus->links() }}
        </div>
    </div>

    <div class="card-custom bg-white p-3 mt-3">
        <h6 class="fw-bold text-potato-brown mb-1">Stok Bumbu</h6>
        <p class="text-muted small">Saus dan bubuk tabur yang terpakai tiap pesanan. Kalau stok bumbu kurang, transaksi atau pre-order tidak bisa dibuat.</p>
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>BUMBU</th>
                        <th>JENIS</th>
                        <th class="text-end">STOK SAAT INI</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bumbus as $b)
                        <tr>
                            <td class="fw-semibold text-potato-brown">{{ $b->nama }}</td>
                            <td>{{ $b->jenis === 'saus' ? 'Saus' : 'Bubuk tabur' }}</td>
                            <td class="text-end fw-bold">{{ $b->stok }} <span class="text-muted fw-normal">{{ $b->satuan }}</span></td>
                            <td class="text-center">
                                @if($b->status_stok == 'habis')
                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Habis</span>
                                @elseif($b->status_stok == 'menipis')
                                    <span class="badge badge-menipis px-2 py-1">Menipis</span>
                                @else
                                    <span class="badge badge-aman px-2 py-1">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada bumbu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
