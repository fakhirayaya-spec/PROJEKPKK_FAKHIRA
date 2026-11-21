@extends('layouts.admin')

@section('title', 'Riwayat Stok - Potato Curly Crunch')

@push('styles')
<style>
    .text-potato-brown { color: #3d2314; }
    .card-custom { border-radius: 16px; border: 1px solid #efe8e0; box-shadow: 0 2px 8px rgba(61,35,20,0.04); }
    .badge-masuk { background-color: #dcfce7; color: #16a34a; }
    .badge-keluar { background-color: #fee2e2; color: #dc2626; }
</style>
@endpush

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">
    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Riwayat Stok — {{ $menu->nama_menu }}</h4>
        <p class="text-muted small mb-0">Stok saat ini: <strong>{{ $menu->stok }}</strong> | 20 pergerakan terakhir.</p>
        <a href="{{ route('admin.stok.index') }}" class="small text-decoration-none">&larr; Kembali ke Manajemen Stok</a>
    </div>

    @if(\App\Services\StokService::pakaiExp($menu))
    <div class="card-custom bg-white p-3 mb-3">
        <h6 class="fw-bold text-potato-brown mb-3"><i class="bi bi-calendar-x"></i> Batch Stok &amp; Tanggal Exp</h6>
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>MASUK</th>
                        <th>EXP</th>
                        <th class="text-end">JUMLAH AWAL</th>
                        <th class="text-end">SISA</th>
                        <th class="text-end">DIBUANG (EXP)</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batches as $b)
                    <tr>
                        <td>{{ $b->tanggal_masuk->format('d M Y') }}</td>
                        <td>
                            {{ $b->tanggal_exp->format('d M Y') }}
                            @if($b->sisa > 0)
                                @php $h = $b->sisa_hari; @endphp
                                <span class="badge-soft {{ $h <= 0 ? 'red' : ($h <= 3 ? 'amber' : 'green') }} ms-1">{{ $h < 0 ? 'sudah exp' : ($h === 0 ? 'hari ini' : $h . ' hari lagi') }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $b->jumlah_awal }}</td>
                        <td class="text-end fw-bold">{{ $b->sisa }}</td>
                        <td class="text-end {{ $b->dibuang > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $b->dibuang }}</td>
                        <td class="text-muted">{{ $b->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada batch stok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>TANGGAL</th>
                        <th>JENIS</th>
                        <th class="text-end">JUMLAH</th>
                        <th>KETERANGAN</th>
                        <th>OLEH</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($riwayat as $item)
                    <tr>
                        <td>{{ $item->tanggal->format('d M Y') }}</td>
                        <td>
                            <span class="badge {{ $item->jenis == 'masuk' ? 'badge-masuk' : 'badge-keluar' }} px-2 py-1">
                                {{ ucfirst($item->jenis) }}
                            </span>
                        </td>
                        <td class="text-end fw-bold">{{ $item->jenis == 'masuk' ? '+' : '-' }}{{ $item->jumlah }}</td>
                        <td>{{ $item->keterangan ?? '-' }}</td>
                        <td>{{ $item->user->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat stok untuk menu ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
