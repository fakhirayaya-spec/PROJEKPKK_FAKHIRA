@extends('layouts.kasir')

@section('title', 'Dashboard Kasir')

@push('styles')
<style>
    .hero-kasir { background: var(--espresso); color: #fff; border-radius: 22px; padding: 26px 28px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px; position: relative; overflow: hidden; }
    .hero-kasir::after { content: ''; position: absolute; right: -50px; top: -60px; width: 220px; height: 220px; border-radius: 50%; border: 34px solid rgba(252, 174, 30, .14); }
    .hero-kasir h4 { font-weight: 800; font-size: 1.6rem; margin: 0 0 4px; }
    .hero-kasir p { margin: 0; color: rgba(255, 226, 180, .7); font-size: .9rem; }
    .hero-kasir .btn { position: relative; z-index: 1; padding: 12px 22px; font-size: .95rem; }
</style>
@endpush

@section('content')
<div class="p-4">

    <div class="hero-kasir mb-4">
        <div>
            <h4>Halo, {{ auth()->user()->name }}</h4>
            <p>Ringkasan penjualan kamu hari ini.</p>
        </div>
        <a href="{{ route('kasir.transaksi.index') }}" class="btn btn-potato">
            <i class="bi bi-bag-plus-fill me-1"></i> Mulai transaksi baru
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-receipt"></i></div>
                <div><div class="stat-label">Transaksi hari ini</div><div class="stat-value">{{ $transaksiSayaHariIni }}</div></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
                <div><div class="stat-label">Penjualan hari ini</div><div class="stat-value">Rp {{ number_format($penjualanSayaHariIni, 0, ',', '.') }}</div></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <a href="{{ route('kasir.stok.index') }}" class="text-decoration-none d-block h-100">
                <div class="stat-card">
                    <div class="stat-icon {{ $stokMenipisHabis ? 'red' : 'brown' }}"><i class="bi bi-boxes"></i></div>
                    <div><div class="stat-label">Menu stok menipis atau habis</div><div class="stat-value">{{ $stokMenipisHabis }}</div></div>
                </div>
            </a>
        </div>
    </div>

    <div class="card-custom p-0 overflow-hidden">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <h6 class="fw-bold mb-0">Transaksi terakhir saya</h6>
            <a href="{{ route('kasir.riwayat.index') }}" class="small fw-bold text-decoration-none" style="color: #a86200;">Lihat semua</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Waktu</th>
                        <th>Metode</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksiTerakhirSaya as $trx)
                        <tr>
                            <td class="fw-bold">{{ $trx->kode_transaksi }}</td>
                            <td class="text-muted">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                            <td><span class="badge-soft gray">{{ strtoupper($trx->metode_bayar) }}</span></td>
                            <td class="text-end fw-bold {{ $trx->dibatalkan ? 'text-decoration-line-through text-muted' : '' }}">Rp {{ number_format($trx->total, 0, ',', '.') }}@if($trx->dibatalkan) <span class="badge bg-danger-subtle text-danger border border-danger ms-1" style="font-size: 10px;">Batal</span>@endif</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Belum ada transaksi. <a href="{{ route('kasir.transaksi.index') }}" class="fw-bold" style="color: #a86200;">Mulai transaksi pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
