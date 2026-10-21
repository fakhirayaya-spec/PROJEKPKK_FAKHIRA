@extends('layouts.admin')

@section('title', 'Pengeluaran - Potato Curly Crunch')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-potato-brown">Pengeluaran</h4>
            <p class="text-muted small mb-0">Catat pembelian bahan baku & biaya operasional lain di luar transaksi penjualan.</p>
        </div>
        <button type="button" class="btn btn-potato btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg"></i> Catat Pengeluaran
        </button>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-2">
        <div class="col-12 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-label">Total Pengeluaran {{ ($dari || $sampai) ? '(periode dipilih)' : '(semua periode)' }}</div>
                    <div class="stat-value">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted small mb-3">
        <i class="bi bi-info-circle"></i> Semua kategori dicatat di sini buat pelacakan kas keluar. Tapi khusus di
        halaman <a href="{{ route('admin.laporan.index') }}">Laporan</a>, yang dikurangkan dari laba cuma kategori
        <b>Operasional</b> & <b>Lainnya</b> — kategori <b>Bahan Baku</b> tidak ikut dikurangkan lagi karena biayanya
        sudah otomatis terhitung lewat Harga Modal (HPP) tiap menu yang terjual.
    </p>

    <div class="card-custom bg-white p-3 mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-6 col-md-3">
                <label class="small text-muted mb-1 d-block">Dari</label>
                <input type="date" name="dari" value="{{ $dari?->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="small text-muted mb-1 d-block">Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai?->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="small text-muted mb-1 d-block">Kategori</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($labelKategori as $key => $label)
                        <option value="{{ $key }}" {{ request('kategori') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2 align-self-end">
                <button type="submit" class="btn btn-sm btn-potato w-100">Cari</button>
                <a href="{{ route('admin.pengeluaran.index') }}" class="btn btn-sm btn-outline-secondary">Semua</a>
            </div>
        </form>
    </div>

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>TANGGAL</th>
                        <th>KATEGORI</th>
                        <th>KETERANGAN</th>
                        <th>DICATAT OLEH</th>
                        <th class="text-end">JUMLAH</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pengeluarans as $p)
                    <tr>
                        <td>{{ $p->tanggal->format('d M Y') }}</td>
                        <td>
                            @php
                                $warna = match($p->kategori) { 'bahan_baku' => 'amber', 'operasional' => 'blue', default => 'gray' };
                            @endphp
                            <span class="badge-soft {{ $warna }}">{{ $labelKategori[$p->kategori] }}</span>
                        </td>
                        <td>{{ $p->deskripsi }}</td>
                        <td>{{ $p->pencatat->name ?? '-' }}</td>
                        <td class="text-end fw-bold text-potato-brown">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <form action="{{ route('admin.pengeluaran.destroy', $p) }}" method="POST"
                                  onsubmit="return confirm('Hapus catatan pengeluaran ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengeluaran tercatat untuk filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $pengeluarans->links() }}</div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold text-potato-brown">Catat Pengeluaran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.pengeluaran.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            @foreach($labelKategori as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan</label>
                        <input type="text" name="deskripsi" class="form-control" placeholder="Contoh: Beli minyak goreng 5L" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Jumlah (Rp)</label>
                        <input type="number" name="jumlah" min="1" class="form-control" placeholder="50000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-potato btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
