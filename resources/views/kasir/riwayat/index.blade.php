@extends('layouts.kasir')

@section('title', 'Riwayat Transaksi Saya')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Riwayat Transaksi Saya</h4>
        <p class="text-muted small mb-0">Hanya menampilkan transaksi yang kamu proses sendiri.</p>
    </div>

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>KODE</th>
                        <th>WAKTU</th>
                        <th>METODE</th>
                        <th class="text-end">TOTAL</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">STRUK</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksis as $trx)
                        <tr>
                            <td class="fw-bold text-potato-brown">{{ $trx->kode_transaksi }}
                            @if(($trx->jenis ?? 'penjualan') === 'dp')<span class="badge-soft amber ms-1">DP PO</span>@elseif(($trx->jenis ?? 'penjualan') === 'pelunasan')<span class="badge-soft blue ms-1">Pelunasan PO</span>@endif</td>
                            <td class="text-muted">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ ucfirst($trx->metode_bayar) }}</span></td>
                            <td class="text-end fw-bold text-potato-brown {{ $trx->dibatalkan ? 'text-decoration-line-through text-muted' : '' }}">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @php $st = \App\Support\StatusTransaksi::untuk($trx); @endphp<span class="badge-soft {{ $st['warna'] }}">{{ $st['label'] }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('kasir.transaksi.struk', $trx) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-receipt"></i> Lihat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Kamu belum punya riwayat transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $transaksis->links() }}
        </div>
    </div>
</div>
@endsection
