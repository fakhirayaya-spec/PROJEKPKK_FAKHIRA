@extends('layouts.admin')

@section('title', 'Riwayat Transaksi - Potato Curly Crunch')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Riwayat Transaksi</h4>
        <p class="text-muted small mb-0">Semua transaksi dari seluruh kasir. Gunakan filter untuk mempersempit data.</p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-label">Total Penjualan (sesuai filter)</div>
                    <div class="stat-value">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="stat-card">
                <div class="stat-icon brown"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="stat-label">Jumlah Transaksi Selesai</div>
                    <div class="stat-value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom bg-white p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Cari kode</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="TRX-...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Kasir</label>
                <select name="kasir_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($kasirs as $k)
                        <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Metode</label>
                <select name="metode_bayar" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="cash" @selected(request('metode_bayar') == 'cash')>Cash</option>
                    <option value="qris" @selected(request('metode_bayar') == 'qris')>QRIS</option>
                    <option value="paylater" @selected(request('metode_bayar') == 'paylater')>PayLater</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Dari</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-potato w-100" title="Terapkan filter"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('admin.riwayat.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>

    <div class="card-custom bg-white p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>KODE</th>
                        <th>WAKTU</th>
                        <th>KASIR</th>
                        <th class="text-center">ITEM</th>
                        <th>METODE</th>
                        <th>STATUS</th>
                        <th class="text-end">TOTAL</th>
                        <th class="text-center">DETAIL</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transaksis as $trx)
                    <tr>
                        <td class="fw-bold text-potato-brown">{{ $trx->kode_transaksi }}</td>
                        <td class="text-muted">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                        <td>{{ $trx->kasir->name ?? '-' }}</td>
                        <td class="text-center">{{ $trx->items_count }}</td>
                        <td>
                            @php $warna = ['cash' => 'green', 'qris' => 'blue', 'paylater' => 'amber'][$trx->metode_bayar] ?? 'gray'; @endphp
                            <span class="badge-soft {{ $warna }}">{{ strtoupper($trx->metode_bayar) }}</span>
                        </td>
                        <td>
                            <span class="badge-soft {{ $trx->status == 'selesai' ? 'green' : 'red' }}">{{ ucfirst($trx->status) }}</span>
                        </td>
                        <td class="text-end fw-bold text-potato-brown">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.riwayat.show', $trx) }}" class="btn btn-sm btn-outline-potato">
                                <i class="bi bi-eye"></i> Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Belum ada transaksi yang cocok.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $transaksis->links() }}
    </div>
</div>
@endsection
