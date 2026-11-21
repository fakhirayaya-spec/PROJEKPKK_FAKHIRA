@extends('layouts.admin')

@section('title', 'Riwayat Stok Operasional - Potato Curly Crunch')

@push('styles')
    @include('layouts.partials.stok-operasional-style')
@endpush

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 so-brown">Riwayat Stok Operasional</h4>
                <p class="text-muted small mb-0">Pemakaian, pengembalian (transaksi dibatalkan), dan isi ulang semua kasir.</p>
            </div>
            <a href="{{ route('admin.stok-operasional.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Stok
            </a>
        </div>

        <div class="so-card p-3 mb-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <select name="kasir_id" class="form-select form-select-sm">
                        <option value="">Semua kasir</option>
                        @foreach ($kasirs as $k)
                            <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select name="bahan" class="form-select form-select-sm">
                        <option value="">Semua bahan</option>
                        @foreach ($bahanList as $bh)
                            <option value="{{ $bh->kode }}" @selected(request('bahan') === $bh->kode)>{{ $bh->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select name="jenis" class="form-select form-select-sm">
                        <option value="">Semua jenis</option>
                        <option value="pakai" @selected(request('jenis') === 'pakai')>Terpakai</option>
                        <option value="kembali" @selected(request('jenis') === 'kembali')>Dikembalikan (batal)</option>
                        <option value="isi_ulang" @selected(request('jenis') === 'isi_ulang')>Isi ulang</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-sm so-btn w-100">Filter</button>
                </div>
            </form>
        </div>

        <div class="so-card p-3">
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
                    <thead class="text-muted border-bottom" style="font-size: 11px;">
                        <tr>
                            <th>WAKTU</th>
                            <th>KASIR</th>
                            <th>BAHAN</th>
                            <th>JENIS</th>
                            <th class="text-end">JUMLAH</th>
                            <th class="text-end">SISA</th>
                            <th>KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $r)
                            <tr>
                                <td class="text-nowrap">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $r->kasir->name ?? '-' }}</td>
                                <td class="fw-semibold so-brown">{{ $r->label_bahan }}</td>
                                <td>{{ $r->label_jenis }}</td>
                                <td class="text-end fw-bold text-nowrap {{ $r->mengurangi ? 'text-danger' : 'text-success' }}">
                                    {{ $r->mengurangi ? '-' : '+' }}{{ $r->jumlah }} <span class="fw-normal text-muted">{{ $r->satuan }}</span>
                                </td>
                                <td class="text-end text-nowrap">{{ $r->saldo }}</td>
                                <td class="text-muted">
                                    {{ $r->keterangan }}
                                    @if ($r->jenis === 'isi_ulang' && $r->pelaku)
                                        <span class="small">· oleh {{ $r->pelaku->name }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $riwayat->links() }}</div>
        </div>
    </div>
@endsection
