@extends('layouts.admin')

@section('title', 'Riwayat Transaksi - Potato Curly Crunch')

@push('styles')
<style>
    .history-page { background:#fcf8f2; min-height:calc(100vh - 68px); }
    .history-heading { color:#4b2b19; }
    .history-panel { background:#fff; border:1px solid #eee5da; border-radius:12px; }
    .history-summary { display:flex; align-items:center; gap:10px; min-width:0; padding:10px 14px; }
    .history-summary-icon { width:34px; height:34px; flex:0 0 34px; display:grid; place-items:center; border-radius:9px; background:#fff2d8; color:#bd7910; }
    .history-summary-icon.brown { background:#f2e9df; color:#68442d; }
    .history-summary-label { color:#897c70; font-size:11px; line-height:1.2; }
    .history-summary-value { color:#4b2b19; font-size:17px; line-height:1.25; font-weight:750; white-space:nowrap; }
    .history-filter-title { color:#4b2b19; font-weight:700; font-size:13px; white-space:nowrap; }
    .history-filter .form-label { color:#75685d; font-size:11px; font-weight:650; margin-bottom:4px; }
    .history-filter .form-control, .history-filter .form-select { height:34px; border-color:#e8dfd5; border-radius:7px; font-size:12px; padding-top:5px; padding-bottom:5px; }
    .history-muted { color:#95887d; font-size:11px; }
    .history-table { min-width:1050px; font-size:12px; }
    .history-table thead th { background:#faf6f0; color:#88796c; font-size:10px; letter-spacing:.035em; font-weight:750; padding:10px 11px; border-bottom:1px solid #eee5da; white-space:nowrap; }
    .history-table tbody td { padding:10px 11px; border-color:#f1ece6; color:#51463d; vertical-align:middle; white-space:nowrap; }
    .history-table tbody tr:hover { background:#fffcf8; }
    .history-code { color:#4b2b19; font-weight:750; }
    .history-badge { display:inline-flex; align-items:center; padding:4px 7px; border-radius:20px; font-size:9px; font-weight:750; white-space:nowrap; }
    .history-badge.green { background:#e6f6eb; color:#247443; }
    .history-badge.amber { background:#fff2d8; color:#94600d; }
    .history-badge.blue { background:#e8f1ff; color:#3565a5; }
    .history-badge.red { background:#fde9e7; color:#b33b32; }
    .history-badge.gray { background:#f0eeeb; color:#71675e; }
    .history-detail-btn { display:inline-block; border:1px solid #d8c3ae; color:#68442d; border-radius:7px; padding:4px 9px; font-size:11px; font-weight:650; text-decoration:none; white-space:nowrap; }
    .history-detail-btn:hover { color:#4b2b19; background:#fbf3e9; border-color:#c5a98c; }
    .history-actions { display:flex; gap:5px; }
    .history-actions .btn { height:34px; min-width:35px; }
    @media (max-width:767.98px) {
        .history-page { padding:14px!important; }
        .history-summary-value { font-size:15px; }
        .history-filter .filter-field { min-width:calc(50% - 8px); flex:1 1 calc(50% - 8px); }
    }
</style>
@endpush

@section('content')
<div class="history-page p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold history-heading mb-1">Riwayat Transaksi</h4>
            <p class="text-muted small mb-0">Rekap transaksi dari seluruh kasir.</p>
        </div>
        <div class="history-muted"><i class="bi bi-receipt me-1"></i> Data transaksi tersimpan</div>
    </div>

    {{-- Ringkasan dibuat sejajar agar halaman tidak memanjang ke bawah --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="history-panel history-summary h-100">
                <div class="history-summary-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="min-w-0">
                    <div class="history-summary-label">Total Penjualan</div>
                    <div class="history-summary-value">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="history-panel history-summary h-100">
                <div class="history-summary-icon brown"><i class="bi bi-receipt"></i></div>
                <div class="min-w-0">
                    <div class="history-summary-label">Transaksi Selesai</div>
                    <div class="history-summary-value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter satu baris seperti area riwayat Kasir --}}
    <div class="history-panel p-3 mb-3">
        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 history-filter">
            <div class="me-auto pb-1 history-filter-title"><i class="bi bi-funnel me-1"></i> Filter</div>
            <div class="filter-field" style="flex:1 1 150px; min-width:130px;">
                <label class="form-label">Kode transaksi</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="TRX-...">
            </div>
            <div class="filter-field" style="flex:1 1 130px; min-width:115px;">
                <label class="form-label">Kasir</label>
                <select name="kasir_id" class="form-select form-select-sm">
                    <option value="">Semua kasir</option>
                    @foreach($kasirs as $k)
                        <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field" style="flex:1 1 125px; min-width:110px;">
                <label class="form-label">Metode</label>
                <select name="metode_bayar" class="form-select form-select-sm">
                    <option value="">Semua metode</option>
                    <option value="cash" @selected(request('metode_bayar') == 'cash')>Cash</option>
                    <option value="qris" @selected(request('metode_bayar') == 'qris')>QRIS</option>
                </select>
            </div>
            <div class="filter-field" style="flex:1 1 125px; min-width:110px;">
                <label class="form-label">Dari tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm">
            </div>
            <div class="filter-field" style="flex:1 1 125px; min-width:110px;">
                <label class="form-label">Sampai tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm">
            </div>
            <div class="history-actions">
                <button type="submit" class="btn btn-sm btn-potato" title="Terapkan filter"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.riwayat.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <div class="history-panel overflow-hidden">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="border-color:#f1ece6!important;">
            <div class="history-filter-title">Daftar Transaksi</div>
            <span class="history-muted">{{ $transaksis->total() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle history-table mb-0">
                <thead>
                    <tr>
                        <th>KODE TRANSAKSI</th>
                        <th>WAKTU</th>
                        <th>KASIR</th>
                        <th class="text-center">ITEM</th>
                        <th>METODE</th>
                        <th>STATUS PEMBAYARAN</th>
                        <th class="text-end">NOMINAL</th>
                        <th class="text-center">DETAIL</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transaksis as $trx)
                    <tr>
                        <td>
                            <div class="history-code">{{ $trx->kode_transaksi }}</div>
                            @if(($trx->jenis ?? 'penjualan') === 'dp')
                                <span class="history-badge amber mt-1">DP PRE-ORDER</span>
                            @elseif(($trx->jenis ?? 'penjualan') === 'pelunasan')
                                <span class="history-badge blue mt-1">PELUNASAN PO</span>
                            @endif
                        </td>
                        <td>{{ $trx->created_at->format('d M Y, H:i') }}</td>
                        <td>{{ $trx->kasir->name ?? '-' }}</td>
                        <td class="text-center">{{ $trx->items_count }}</td>
                        <td>
                            @php $metodeWarna = ['cash' => 'green', 'qris' => 'blue', 'paylater' => 'amber'][$trx->metode_bayar] ?? 'gray'; @endphp
                            <span class="history-badge {{ $metodeWarna }}">{{ strtoupper($trx->metode_bayar) }}</span>
                        </td>
                        <td>
                            @php $st = \App\Support\StatusTransaksi::untuk($trx); @endphp
                            <span class="history-badge {{ $st['warna'] }}">{{ $st['label'] }}</span>
                        </td>
                        <td class="text-end fw-bold" style="color:#4b2b19;">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                        <td class="text-center"><a href="{{ route('admin.riwayat.show', $trx) }}" class="history-detail-btn"><i class="bi bi-eye me-1"></i>Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5"><i class="bi bi-inbox fs-2 d-block mb-2" style="color:#c8b9a9;"></i><span class="text-muted small">Belum ada transaksi yang cocok dengan filter.</span></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transaksis->links() }}</div>
</div>
@endsection
