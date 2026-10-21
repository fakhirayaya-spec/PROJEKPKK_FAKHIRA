@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.kasir')

@section('title', 'Pre-Order - Potato Curly Crunch')

@section('content')
@php $kasir = auth()->user()->role === 'kasir'; @endphp
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="page-head">
        <div>
            <h4 class="fw-bold mb-0 text-potato-brown">Pre-Order</h4>
            @if($kasir)
                <p>Catat pesanan jauh hari atau jumlah besar (misalnya dari WhatsApp), pantau statusnya sampai diambil.</p>
            @else
                <p>Pantau progres pre-order yang dikerjakan kasir. Admin hanya bisa melihat, perubahan status dilakukan oleh kasir.</p>
            @endif
        </div>
        @if($kasir)
            <a href="{{ route($rp . 'create') }}" class="btn btn-potato"><i class="bi bi-plus-lg me-1"></i> Buat Pre-Order</a>
        @endif
    </div>

    @if(session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-inbox"></i></div>
                <div><div class="stat-label">Pesanan Baru</div><div class="stat-value">{{ $hitung->get('baru', 0) }}</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-fire"></i></div>
                <div><div class="stat-label">Sedang Diproses</div><div class="stat-value">{{ $hitung->get('diproses', 0) }}</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-bag-check"></i></div>
                <div><div class="stat-label">Siap Diambil</div><div class="stat-value">{{ $hitung->get('siap', 0) }}</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon brown"><i class="bi bi-calendar-event"></i></div>
                <div><div class="stat-label">Diambil Hari Ini</div><div class="stat-value">{{ $ambilHariIni }}</div></div>
            </div>
        </div>
    </div>

    @if($terlambat > 0)
        <div class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Ada <b>{{ $terlambat }}</b> pre-order yang tanggal ambilnya sudah lewat tapi belum diselesaikan.
        </div>
    @endif

    <div class="card-custom bg-white p-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <ul class="nav nav-pills gap-1" style="font-size: .85rem;">
                @foreach(['aktif' => 'Aktif', 'baru' => 'Baru', 'diproses' => 'Diproses', 'siap' => 'Siap Diambil', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan', 'semua' => 'Semua'] as $k => $lbl)
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 {{ $status === $k ? 'active' : 'text-muted' }}"
                           style="{{ $status === $k ? 'background: #fcae1e; color: #3a1f0a; font-weight: 700;' : '' }}"
                           href="{{ route($rp . 'index', array_filter(['status' => $k, 'q' => $q])) }}">{{ $lbl }}</a>
                    </li>
                @endforeach
            </ul>
            <form method="GET" class="d-flex gap-2" style="min-width: 260px;">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Cari nama / kode / WA...">
                <button class="btn btn-sm btn-outline-potato"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>KODE</th>
                        <th>PELANGGAN</th>
                        <th>DIAMBIL</th>
                        <th class="text-center">PORSI</th>
                        <th class="text-end">TOTAL</th>
                        <th class="text-end">DP</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($preorders as $po)
                    <tr>
                        <td class="fw-bold">{{ $po->kode_preorder }}</td>
                        <td>
                            <div class="fw-semibold text-potato-brown">{{ $po->nama_pelanggan }}</div>
                            @if($po->no_whatsapp)
                                <div class="text-muted" style="font-size: 11px;"><i class="bi bi-whatsapp"></i> {{ $po->no_whatsapp }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $po->tanggal_ambil->translatedFormat('d M Y') }}{{ $po->jam_ambil ? ', ' . $po->jam_ambil : '' }}</div>
                            @if($po->aktif)
                                @if($po->tanggal_ambil->isToday())
                                    <span class="badge" style="background:#fef3c7;color:#b45309;font-size:10px;">Hari ini</span>
                                @elseif($po->tanggal_ambil->isTomorrow())
                                    <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px;">Besok</span>
                                @elseif($po->tanggal_ambil->isPast())
                                    <span class="badge" style="background:#fee2e2;color:#b91c1c;font-size:10px;">Terlambat</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-center">{{ $po->items->sum('qty') }}</td>
                        <td class="text-end fw-bold text-potato-brown">Rp {{ number_format($po->total, 0, ',', '.') }}</td>
                        <td class="text-end">
                            @if($po->dp > 0)
                                Rp {{ number_format($po->dp, 0, ',', '.') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">@include('preorder._badge', ['status' => $po->status])</td>
                        <td class="text-center">
                            <a href="{{ route($rp . 'show', $po) }}" class="btn btn-sm btn-outline-potato">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-3 d-block mb-1"></i>
                            Belum ada pre-order{{ $status === 'aktif' ? ' yang aktif' : '' }}.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $preorders->links() }}</div>
    </div>
</div>
@endsection
