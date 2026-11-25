@extends('layouts.kasir')

@section('title', 'Struk Transaksi')

@push('styles')
<style>
.struk-wrap { padding: 8px 0 4px; }
.struk-kertas {
    --tinta: #1f1f1f; --abu: #6f6f6f;
    position: relative; max-width: 340px; margin: 0 auto; background: #fff; color: var(--tinta);
    font-family: 'Courier New', Courier, monospace; font-size: 12.5px; line-height: 1.45;
    padding: 22px 20px 18px; filter: drop-shadow(0 6px 14px rgba(61,35,20,.18));
    background-image: repeating-linear-gradient(0deg, rgba(0,0,0,.012) 0 1px, transparent 1px 3px);
}
/* tepi kertas bergerigi */
.struk-kertas::before, .struk-kertas::after {
    content: ""; position: absolute; left: 0; right: 0; height: 9px; background-size: 14px 9px; background-repeat: repeat-x;
}
.struk-kertas::before { top: -8px; background-image: linear-gradient(135deg, transparent 75%, #fff 75%), linear-gradient(225deg, transparent 75%, #fff 75%); background-size: 12px 9px; }
.struk-kertas::after { bottom: -8px; background-image: linear-gradient(45deg, transparent 75%, #fff 75%), linear-gradient(315deg, transparent 75%, #fff 75%); background-size: 12px 9px; }

.sk-logo { width: 46px; height: 46px; margin: 0 auto 6px; border: 2px solid var(--tinta); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.sk-nama { font-size: 17px; font-weight: 700; letter-spacing: 1.5px; text-align: center; line-height: 1.2; }
.sk-tag { text-align: center; font-size: 10.5px; color: var(--abu); letter-spacing: .5px; }
.sk-garis { border: 0; border-top: 1px dashed #8a8a8a; margin: 10px 0; opacity: 1; }
.sk-ganda { border: 0; border-top: 3px double #8a8a8a; margin: 10px 0; opacity: 1; }
.sk-meta { display: grid; grid-template-columns: auto 1fr; column-gap: 8px; font-size: 11.5px; }
.sk-meta .k { color: var(--abu); }
.sk-meta .v { text-align: right; word-break: break-all; }
.sk-judul { text-align: center; font-weight: 700; letter-spacing: 3px; font-size: 11px; color: var(--abu); margin: 2px 0 6px; }
.sk-item { margin-bottom: 7px; }
.sk-item .nm { font-weight: 700; }
.sk-item .ex { font-size: 10.5px; color: var(--abu); padding-left: 10px; }
.sk-item .hg { display: flex; justify-content: space-between; gap: 8px; padding-left: 10px; }
.sk-row { display: flex; justify-content: space-between; gap: 10px; }
.sk-row.besar { font-size: 15px; font-weight: 700; }
.sk-row.abu { color: var(--abu); font-size: 11.5px; }
.sk-total { border-top: 1px solid var(--tinta); border-bottom: 1px solid var(--tinta); padding: 5px 0; margin: 8px 0; }
.sk-pill { display: inline-block; border: 1.5px solid var(--tinta); border-radius: 4px; padding: 0 7px; font-weight: 700; font-size: 11px; letter-spacing: 1px; }
.sk-stempel { text-align: center; margin: 8px 0 2px; }
.sk-batal { border: 2.5px solid #c0392b; color: #c0392b; text-align: center; font-weight: 800; letter-spacing: 3px; padding: 5px; margin: 10px 8px; transform: rotate(-3deg); border-radius: 4px; font-size: 15px; }
.sk-batal small { display: block; letter-spacing: 0; font-weight: 600; font-size: 10px; margin-top: 2px; }
.struk-kertas.dibatalkan .sk-item, .struk-kertas.dibatalkan .sk-total { text-decoration: line-through; color: #8a8a8a; }
.sk-barcode { display: flex; justify-content: center; align-items: stretch; height: 44px; margin: 12px auto 3px; padding: 0 4px; gap: 0; }
.sk-barcode i { display: block; background: var(--tinta); }
.sk-barcode i.s { background: transparent; }
.sk-kode { text-align: center; font-size: 10.5px; letter-spacing: 2px; }
.sk-terima { text-align: center; font-size: 11.5px; margin-top: 10px; }
.sk-terima b { display: block; font-size: 13px; letter-spacing: 1px; }
.sk-kecil { text-align: center; font-size: 10px; color: var(--abu); margin-top: 6px; }

@page { size: 80mm auto; margin: 3mm; }
@media print {
    html, body { background: #fff !important; }
    body * { visibility: hidden !important; }
    .struk-kertas, .struk-kertas * { visibility: visible !important; }
    .struk-kertas { position: absolute; left: 0; top: 0; width: 100%; max-width: none; margin: 0; padding: 0 1mm; filter: none; background-image: none; font-size: 11.5px; }
    .struk-kertas::before, .struk-kertas::after { display: none; }
    .no-print { display: none !important; }
}
</style>
@endpush

@section('content')
@php
    $jenisTrx = $transaksi->jenis ?? 'penjualan';
    $kode = (string) $transaksi->kode_transaksi;
    $rp = fn ($n) => number_format((int) $n, 0, ',', '.');
    // Barcode hiasan dari kode transaksi (batang tebal-tipis bergantian)
    $batang = [[1, true], [1, false], [1, true], [2, false]];
    foreach (str_split($kode) as $c) {
        $o = ord($c);
        for ($i = 0; $i < 4; $i++) {
            $batang[] = [(($o >> ($i * 2)) & 3) + 1, $i % 2 === 0];
        }
        $batang[] = [2, false];
    }
    array_push($batang, [1, true], [1, false], [1, true]);
    $totalQty = (int) $transaksi->items->sum('qty');
@endphp
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    @if(session('sukses'))
        <div class="alert alert-success no-print" style="max-width: 340px; margin: 0 auto 12px;">{{ session('sukses') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger no-print" style="max-width: 340px; margin: 0 auto 12px;">{{ $errors->first() }}</div>
    @endif

    <div class="struk-wrap">
    <div class="struk-kertas {{ $transaksi->dibatalkan ? 'dibatalkan' : '' }}" id="struk">
        <div class="sk-logo">🥔</div>
        <div class="sk-nama">POTATO<br>CURLY CRUNCH</div>
        <div class="sk-tag">Kentang spiral renyah · Dibuat segar</div>

        <hr class="sk-garis">
        <div class="sk-meta">
            <span class="k">No</span><span class="v">{{ $kode }}</span>
            <span class="k">Tanggal</span><span class="v">{{ $transaksi->created_at->format('d/m/Y H:i') }}</span>
            <span class="k">Kasir</span><span class="v">{{ $transaksi->kasir->name ?? '-' }}</span>
            @if($transaksi->preOrder)
                <span class="k">Pre-order</span><span class="v">{{ $transaksi->preOrder->kode_preorder }}</span>
            @endif
        </div>

        @if($transaksi->dibatalkan)
            <div class="sk-batal">
                DIBATALKAN
                <small>{{ $transaksi->dibatalkan_pada?->format('d/m/Y H:i') }} · {{ $transaksi->alasan_batal }}</small>
            </div>
        @endif

        <hr class="sk-garis">
        <div class="sk-judul">
            {{ $jenisTrx === 'dp' ? 'UANG MUKA (DP)' : ($jenisTrx === 'pelunasan' ? 'PELUNASAN' : 'PESANAN') }}
        </div>

        @foreach($transaksi->items as $item)
            <div class="sk-item">
                <div class="nm">{{ $item->menu->nama_menu ?? '(menu dihapus)' }}{{ $item->ukuran ? ' (' . $item->ukuran . ')' : '' }}</div>
                @if($item->saus || $item->bubuk_tabur)
                    <div class="ex">{{ collect([$item->saus, $item->bubuk_tabur])->filter()->implode(' · ') }}</div>
                @endif
                <div class="hg">
                    <span>{{ $item->qty }} x {{ $rp($item->harga_satuan) }}</span>
                    <span>{{ $rp($item->subtotal) }}</span>
                </div>
            </div>
        @endforeach

        <hr class="sk-garis">
        <div class="sk-row abu"><span>Jumlah item</span><span>{{ $totalQty }} pcs</span></div>

        @if($jenisTrx === 'pelunasan' && $transaksi->preOrder)
            <div class="sk-row abu"><span>Total pesanan</span><span>Rp {{ $rp($transaksi->items->sum('subtotal')) }}</span></div>
            <div class="sk-row abu"><span>DP sudah dibayar</span><span>- Rp {{ $rp($transaksi->preOrder->dp) }}</span></div>
            <div class="sk-total sk-row besar"><span>SISA BAYAR</span><span>Rp {{ $rp($transaksi->total) }}</span></div>
        @else
            <div class="sk-total sk-row besar"><span>{{ $jenisTrx === 'dp' ? 'DP DIBAYAR' : 'TOTAL' }}</span><span>Rp {{ $rp($transaksi->total) }}</span></div>
        @endif

        <div class="sk-row"><span>Bayar</span><span class="sk-pill">{{ strtoupper($transaksi->metode_bayar) }}</span></div>
        @if($transaksi->metode_bayar === 'cash' && $transaksi->uang_diterima !== null)
            <div class="sk-row"><span>Uang diterima</span><span>Rp {{ $rp($transaksi->uang_diterima) }}</span></div>
            <div class="sk-row"><span>Kembalian</span><span>Rp {{ $rp($transaksi->kembalian ?? 0) }}</span></div>
        @endif

        <hr class="sk-ganda">
        <div class="sk-barcode" aria-hidden="true">
            @foreach($batang as [$lebar, $hitam])<i class="{{ $hitam ? '' : 's' }}" style="width: {{ $lebar }}px"></i>@endforeach
        </div>
        <div class="sk-kode">{{ $kode }}</div>

        <div class="sk-terima"><b>TERIMA KASIH!</b>Ditunggu pesanan berikutnya</div>
        <div class="sk-kecil">Simpan struk ini sebagai bukti pembelian</div>
    </div>
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
