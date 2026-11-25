@extends('layouts.base')

@section('title', 'Potato Curly Crunch - Admin Panel')
@section('profil_url', route('admin.profil.edit'))
@section('stok_url', route('admin.stok.index'))

@php
    try { $jmlPermintaan = \App\Models\PermintaanStok::menunggu()->count(); } catch (\Throwable $e) { $jmlPermintaan = 0; }
@endphp

@section('sidebar')
    @include('layouts.partials.sidebar', [
        'home' => route('dashboard'),
        'roleLabel' => 'Potato Curly Crunch',
        'fotoUser' => auth()->user()->foto_url,
        'groups' => [
            'Ringkasan' => [
                ['dashboard', 'bi-grid-1x2-fill', 'Dashboard', 'dashboard'],
            ],
            'Data master' => [
                ['admin.menu.index', 'bi-cup-straw', 'Master Menu', 'admin.menu.*'],
                ['admin.kasir.index', 'bi-person-badge', 'Data Kasir', 'admin.kasir.*'],
            ],
            'Operasional' => [
                ['admin.stok.index', 'bi-boxes', 'Manajemen Stok', 'admin.stok.*'],
                ['admin.bumbu.index', 'bi-droplet-half', 'Manajemen Bumbu', 'admin.bumbu.*'],
                ['admin.stok-operasional.index', 'bi-fuel-pump', 'Stok Operasional', 'admin.stok-operasional.*'],
                ['admin.permintaan-stok.index', 'bi-inbox', 'Permintaan Stok', 'admin.permintaan-stok.*', $jmlPermintaan ?: null],
                ['admin.preorder.index', 'bi-calendar-check', 'Pre-Order', 'admin.preorder.*'],
                ['admin.riwayat.index', 'bi-receipt', 'Riwayat Transaksi', 'admin.riwayat.*'],
            ],
            'Analisis' => [
                ['admin.pengeluaran.index', 'bi-wallet2', 'Pengeluaran', 'admin.pengeluaran.*'],
                ['admin.laporan.index', 'bi-graph-up-arrow', 'Laporan', 'admin.laporan.*'],
            ],
            'Akun' => [
                ['admin.profil.edit', 'bi-person-circle', 'Profil Saya', 'admin.profil.*'],
            ],
        ],
    ])
@endsection
