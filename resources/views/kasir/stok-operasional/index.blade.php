@extends('layouts.kasir')

@section('title', 'Stok Operasional Saya')

@push('styles')
    @include('layouts.partials.stok-operasional-style')
@endpush

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="mb-4">
            <h4 class="fw-bold mb-1 so-brown">Stok Operasional Saya</h4>
            <p class="text-muted small mb-0">Sisa bahan operasional milikmu. Berkurang otomatis tiap transaksi berhasil. Isi ulang dilakukan oleh Admin.</p>
        </div>

        <div class="so-grid mb-4">
            @foreach ($stok as $kode => $b)
                @php $m = $b->master; @endphp
                <div class="so-tile {{ $b->status_stok === 'aman' ? '' : $b->status_stok }}">
                    <div class="so-tile-head">
                        <div class="so-thumb">
                            @if ($m->gambar_url)
                                <img src="{{ $m->gambar_url }}" alt="{{ $m->nama }}" onerror="soGagal(this)">
                            @else
                                <i class="bi {{ $m->ikon }}"></i>
                            @endif
                        </div>
                        <div>
                            <div class="nm">{{ $m->nama }}</div>
                            <div class="rule">{{ $m->aturan }}</div>
                        </div>
                    </div>
                    <div class="so-num">
                        <div><span class="n">{{ $b->stok }}</span><span class="u">{{ $m->satuan }}</span></div>
                        <span class="so-badge so-badge-{{ $b->status_stok }}">{{ ucfirst($b->status_stok) }}</span>
                    </div>
                    <div class="so-bar"><span class="{{ $b->status_stok }}" style="width: {{ $b->persen }}%"></span></div>
                </div>
            @endforeach
        </div>

        <div class="so-card p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <b class="so-brown">Riwayat pemakaian saya</b>
                <form method="GET" class="d-flex gap-2">
                    <select name="bahan" class="form-select form-select-sm">
                        <option value="">Semua bahan</option>
                        @foreach ($bahanList as $bh)
                            <option value="{{ $bh->kode }}" @selected(request('bahan') === $bh->kode)>{{ $bh->nama }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm so-btn">Filter</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
                    <thead class="text-muted border-bottom" style="font-size: 11px;">
                        <tr>
                            <th>WAKTU</th>
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
                                <td class="fw-semibold so-brown">{{ $r->label_bahan }}</td>
                                <td>{{ $r->label_jenis }}</td>
                                <td class="text-end fw-bold text-nowrap {{ $r->mengurangi ? 'text-danger' : 'text-success' }}">
                                    {{ $r->mengurangi ? '-' : '+' }}{{ $r->jumlah }} <span class="fw-normal text-muted">{{ $r->satuan }}</span>
                                </td>
                                <td class="text-end text-nowrap">{{ $r->saldo }}</td>
                                <td class="text-muted">{{ $r->keterangan }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pemakaian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $riwayat->links() }}</div>
        </div>
    </div>
@endsection
