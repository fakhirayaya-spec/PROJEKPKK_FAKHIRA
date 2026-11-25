@extends('layouts.kasir')

@section('title', 'Riwayat Stok')

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:#3d2314;">Riwayat Stok Saya</h4>
                <p class="text-muted small mb-0">Pemakaian dan penambahan stok menu &amp; bumbu milikmu sendiri.</p>
            </div>
            <a href="{{ route('kasir.stok.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Cek Stok
            </a>
        </div>

        <ul class="nav nav-pills mb-3 gap-2">
            <li class="nav-item">
                <a class="nav-link py-1 {{ $tipe === 'menu' ? 'active' : 'text-dark bg-white border' }}" @if($tipe === 'menu') style="background:#3d2314;" @endif
                    href="{{ route('kasir.riwayat-stok.index', ['tipe' => 'menu']) }}"><i class="bi bi-cup-straw"></i> Menu</a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 {{ $tipe === 'bumbu' ? 'active' : 'text-dark bg-white border' }}" @if($tipe === 'bumbu') style="background:#3d2314;" @endif
                    href="{{ route('kasir.riwayat-stok.index', ['tipe' => 'bumbu']) }}"><i class="bi bi-droplet-half"></i> Bumbu</a>
            </li>
        </ul>

        <div class="bg-white p-3 mb-3" style="border-radius:16px;border:1px solid #efe8e0;">
            @include('layouts.partials.riwayat-stok-filter', ['tabs' => true])
        </div>

        <div class="bg-white p-3" style="border-radius:16px;border:1px solid #efe8e0;">
            @include('layouts.partials.riwayat-stok-tabel', ['tampilKasir' => false])
        </div>
    </div>
@endsection
