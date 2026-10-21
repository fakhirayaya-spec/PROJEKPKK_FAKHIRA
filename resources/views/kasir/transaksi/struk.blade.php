@extends('layouts.kasir')

@section('title', 'Struk Transaksi')

@push('styles')
<style>
    .struk-box {
        max-width: 380px;
        margin: 0 auto;
        background: #fff;
        border: 1px dashed #d8cfc4;
        border-radius: 12px;
        padding: 20px;
        font-family: 'Courier New', monospace;
        color: #000;
        position: relative;
    }
    .struk-box hr { border: 0; border-top: 1px dashed #999; margin: 10px 0; opacity: 1; }
    .struk-batal { border: 2px solid #c0392b; color: #c0392b; text-align: center; font-weight: 800; letter-spacing: 2px; padding: 4px; margin-bottom: 10px; transform: rotate(-2deg); }
    .struk-batal small { display: block; letter-spacing: 0; font-weight: 600; font-size: 10px; }
    .struk-box.dibatalkan .baris-item { text-decoration: line-through; color: #888; }

    /* Cetak: hanya struk yang keluar, pas untuk kertas struk 80mm */
    @page { size: 80mm auto; margin: 4mm; }
    @media print {
        html, body { background: #fff !important; }
        body * { visibility: hidden !important; }
        .struk-box, .struk-box * { visibility: visible !important; }
        .struk-box {
            position: absolute; left: 0; top: 0;
            width: 100%; max-width: none;
            border: 0; border-radius: 0; padding: 0; margin: 0;
            box-shadow: none;
        }
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    @if(session('sukses'))
        <div class="alert alert-success no-print" style="max-width: 380px; margin: 0 auto 12px;">{{ session('sukses') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger no-print" style="max-width: 380px; margin: 0 auto 12px;">{{ $errors->first() }}</div>
    @endif

    <div class="struk-box {{ $transaksi->dibatalkan ? 'dibatalkan' : '' }}" id="struk">
        <div class="text-center mb-2">
            <div class="fw-bold" style="font-size: 15px;">POTATO CURLY CRUNCH</div>
            <div style="font-size: 11px;">{{ $transaksi->kode_transaksi }}</div>
            <div style="font-size: 11px;">{{ $transaksi->created_at->format('d M Y, H:i') }}</div>
            <div style="font-size: 11px;">Kasir: {{ $transaksi->kasir->name ?? '-' }}</div>
        </div>

        @if($transaksi->dibatalkan)
            <div class="struk-batal">
                DIBATALKAN
                <small>{{ $transaksi->dibatalkan_pada?->format('d M Y, H:i') }} · {{ $transaksi->alasan_batal }}</small>
            </div>
        @endif

        <hr>
        @foreach($transaksi->items as $item)
            <div class="baris-item">
                <div class="d-flex justify-content-between" style="font-size: 12px;">
                    <span>{{ $item->menu->nama_menu ?? '(menu dihapus)' }} ({{ $item->ukuran }}) x{{ $item->qty }}</span>
                    <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($item->saus || $item->bubuk_tabur)
                    <div style="font-size: 10px; color: #555;">{{ collect([$item->saus, $item->bubuk_tabur])->filter()->implode(' · ') }}</div>
                @endif
            </div>
        @endforeach
        <hr>

        <div class="d-flex justify-content-between fw-bold" style="font-size: 14px;">
            <span>TOTAL</span>
            <span>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span>
        </div>
        <div class="d-flex justify-content-between" style="font-size: 12px;">
            <span>Metode Bayar</span>
            <span>{{ strtoupper($transaksi->metode_bayar) }}</span>
        </div>
        @if($transaksi->preOrder)
            <div class="d-flex justify-content-between" style="font-size: 12px;">
                <span>Pre-order</span>
                <span>{{ $transaksi->preOrder->kode_preorder }}</span>
            </div>
            @if($transaksi->preOrder->dp > 0)
                <div class="d-flex justify-content-between" style="font-size: 12px;">
                    <span>DP sebelumnya</span>
                    <span>Rp {{ number_format($transaksi->preOrder->dp, 0, ',', '.') }}</span>
                </div>
            @endif
        @endif
        @if($transaksi->metode_bayar === 'cash' && $transaksi->uang_diterima !== null)
            <div class="d-flex justify-content-between" style="font-size: 12px;">
                <span>Uang diterima</span>
                <span>Rp {{ number_format($transaksi->uang_diterima, 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold" style="font-size: 12px;">
                <span>Kembalian</span>
                <span>Rp {{ number_format($transaksi->kembalian ?? 0, 0, ',', '.') }}</span>
            </div>
        @endif
        <hr>
        <div class="text-center" style="font-size: 11px;">Terima kasih sudah beli Potato Curly Crunch!</div>
    </div>

    <div class="text-center mt-3 no-print d-flex flex-wrap justify-content-center gap-2">
        <button onclick="window.print()" class="btn btn-potato"><i class="bi bi-printer"></i> Cetak Struk</button>
        <a href="{{ route('kasir.transaksi.index') }}" class="btn btn-outline-secondary">Transaksi Baru</a>
        @if($bisaBatal)
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBatal">
                <i class="bi bi-x-octagon"></i> Batalkan Transaksi
            </button>
        @endif
    </div>
</div>

@if($bisaBatal)
<div class="modal fade no-print" id="modalBatal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('kasir.transaksi.batal', $transaksi) }}">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Batalkan {{ $transaksi->kode_transaksi }}?</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Stok menu, saus, dan bubuk tabur akan dikembalikan, dan transaksi ini tidak dihitung di laporan. Tindakan ini tidak bisa dibatalkan lagi.</p>
                <label class="form-label fw-semibold small">Alasan pembatalan <span class="text-danger">*</span></label>
                <textarea name="alasan" rows="3" minlength="10" maxlength="150" required class="form-control" placeholder="Tulis alasan yang jelas, min. 10 karakter. Contoh: salah pilih menu, pelanggan tidak jadi membeli"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Kembali</button>
                <button type="submit" class="btn btn-danger btn-sm">Ya, batalkan transaksi</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
