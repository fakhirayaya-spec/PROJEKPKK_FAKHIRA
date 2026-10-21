@extends('layouts.admin')

@section('title', 'Detail Transaksi - Potato Curly Crunch')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.riwayat.index') }}" class="text-decoration-none small text-muted no-print">
                <i class="bi bi-arrow-left"></i> Kembali ke Riwayat Transaksi
            </a>
            <h4 class="fw-bold mb-1 text-potato-brown mt-2">{{ $transaksi->kode_transaksi }}</h4>
            <p class="text-muted small mb-0">
                {{ $transaksi->created_at->format('d M Y, H:i') }} · Kasir: {{ $transaksi->kasir->name ?? '-' }}
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-potato btn-sm">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </div>
    </div>

    @if($transaksi->status === 'selesai')
        <div class="alert alert-info py-2 small no-print"><i class="bi bi-eye me-1"></i> Admin hanya bisa melihat. Pembatalan transaksi dilakukan oleh kasir dengan alasan yang jelas.</div>
    @endif

    @if(session('sukses'))
        <div class="alert alert-success no-print">{{ session('sukses') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger no-print">{{ $errors->first() }}</div>
    @endif
    @if($transaksi->dibatalkan)
        <div class="alert alert-danger">
            <b><i class="bi bi-x-octagon"></i> Transaksi ini DIBATALKAN</b>
            oleh {{ $transaksi->pembatal->name ?? '-' }} pada {{ $transaksi->dibatalkan_pada?->format('d M Y, H:i') }}.
            <div class="small mt-1">Alasan: {{ $transaksi->alasan_batal ?? '-' }}. Tidak dihitung di dashboard dan laporan.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card-custom bg-white p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>MENU</th>
                                <th>UKURAN</th>
                                <th>SAUS</th>
                                <th>BUBUK TABUR</th>
                                <th class="text-center">QTY</th>
                                <th class="text-end">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksi->items as $item)
                                <tr>
                                    <td class="fw-semibold text-potato-brown">{{ $item->menu->nama_menu ?? '(menu dihapus)' }}</td>
                                    <td>{{ $item->ukuran ?? '-' }}</td>
                                    <td>{{ $item->saus ?? '-' }}</td>
                                    <td>{{ $item->bubuk_tabur ?? '-' }}</td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card-custom bg-white p-4">
                <h6 class="fw-bold text-potato-brown mb-3">Ringkasan</h6>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Status</span>
                    <span class="badge-soft {{ $transaksi->status == 'selesai' ? 'green' : 'red' }}">{{ ucfirst($transaksi->status) }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Metode Bayar</span>
                    <span class="fw-bold">{{ strtoupper($transaksi->metode_bayar) }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Jumlah Item</span>
                    <span class="fw-bold">{{ $transaksi->items->sum('qty') }}</span>
                </div>
                @if($transaksi->metode_bayar === 'cash' && $transaksi->uang_diterima !== null)
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Uang diterima</span>
                        <span class="fw-bold">Rp {{ number_format($transaksi->uang_diterima, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Kembalian</span>
                        <span class="fw-bold">Rp {{ number_format($transaksi->kembalian ?? 0, 0, ',', '.') }}</span>
                    </div>
                @endif
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-potato-brown">Total</span>
                    <span class="fs-5 fw-bold text-potato-brown">Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
