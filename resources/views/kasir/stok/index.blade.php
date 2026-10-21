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

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Cek Stok</h4>
        <p class="text-muted small mb-0">Lihat ketersediaan stok menu (read-only, tidak bisa diubah dari sini).</p>
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
                                    <img src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/spark-admin-free.png') }}"
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
</div>
@endsection
