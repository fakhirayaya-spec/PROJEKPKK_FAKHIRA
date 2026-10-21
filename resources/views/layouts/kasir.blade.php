@extends('layouts.base')

@section('title', 'Potato Curly Crunch - Kasir')
@section('profil_url', route('kasir.profil.edit'))
@section('stok_url', route('kasir.stok.index'))
@section('topbar_sub')
    <small><i class="bi bi-clock"></i> <span id="jamKasir">{{ now()->format('H:i:s') }}</span> WIB</small>
@endsection

@section('sidebar')
    @include('layouts.partials.sidebar', [
        'home' => route('kasir.dashboard'),
        'roleLabel' => 'Kasir',
        'fotoUser' => auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('assets_dashboard/images/SEBIN.jpg'),
        'groups' => [
            'Kasir' => [
                ['kasir.dashboard', 'bi-grid-1x2-fill', 'Dashboard', 'kasir.dashboard'],
                ['kasir.transaksi.index', 'bi-bag-plus-fill', 'Transaksi Baru', 'kasir.transaksi.*'],
                ['kasir.preorder.index', 'bi-calendar-check', 'Pre-Order', 'kasir.preorder.*'],
                ['kasir.riwayat.index', 'bi-receipt', 'Riwayat Transaksi', 'kasir.riwayat.*'],
            ],
            'Info' => [
                ['kasir.stok.index', 'bi-boxes', 'Cek Stok', 'kasir.stok.*'],
                ['kasir.profil.edit', 'bi-person-circle', 'Profil', 'kasir.profil.*'],
            ],
        ],
    ])
@endsection

@push('scripts')
    <script>
        (function () {
            const el = document.getElementById('jamKasir');
            if (!el) return;
            setInterval(() => { el.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false }).replace(/\./g, ':'); }, 1000);
        })();
    </script>
@endpush
