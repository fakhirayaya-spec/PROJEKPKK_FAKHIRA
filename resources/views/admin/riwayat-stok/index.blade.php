@extends('layouts.admin')

@section('title', 'Riwayat ' . ($tipe === 'menu' ? 'Stok Menu' : 'Stok Bumbu') . ' - Potato Curly Crunch')

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:#3d2314;">Riwayat {{ $tipe === 'menu' ? 'Stok Menu' : 'Stok Bumbu' }}</h4>
                <p class="text-muted small mb-0">
                    Stok masuk (restock, pengajuan disetujui, pembatalan) dan keluar (transaksi, pre-order, kadaluarsa) semua kasir.
                </p>
            </div>
            <a href="{{ $tipe === 'menu' ? route('admin.stok.index') : route('admin.bumbu.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke {{ $tipe === 'menu' ? 'Manajemen Stok' : 'Manajemen Bumbu' }}
            </a>
        </div>

        <div class="bg-white p-3 mb-3" style="border-radius:16px;border:1px solid #efe8e0;">
            @include('layouts.partials.riwayat-stok-filter')
        </div>

        <div class="bg-white p-3" style="border-radius:16px;border:1px solid #efe8e0;">
            @include('layouts.partials.riwayat-stok-tabel', ['tampilKasir' => true])
        </div>
    </div>
@endsection
