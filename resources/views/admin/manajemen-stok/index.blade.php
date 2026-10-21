@extends('layouts.admin')

@section('title', 'Manajemen Stok - Potato Curly Crunch')

@push('styles')
    <style>
        .text-potato-brown {
            color: #3d2314;
        }

        .card-custom {
            border-radius: 16px;
            border: 1px solid #efe8e0;
            box-shadow: 0 2px 8px rgba(61, 35, 20, 0.04);
        }

        .badge-aman {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .badge-menipis {
            background-color: #fef3c7;
            color: #b45309;
        }

        .thumb-stok {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #efe8e0;
        }

        .btn-potato {
            background: linear-gradient(90deg, #fcae1e 0%, #f7931e 100%);
            color: #fff;
            border: none;
            font-weight: 700;
        }

        .btn-potato:hover {
            color: #fff;
            opacity: .9;
        }
    </style>
@endpush

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="mb-4">
            <h4 class="fw-bold mb-1 text-potato-brown">Manajemen Stok</h4>
            <p class="text-muted small mb-0">Pantau stok tiap menu dan tambahkan stok masuk (restock).</p>
        </div>

        @if (session('sukses'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card-custom bg-white p-3 mb-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm"
                        placeholder="Cari nama menu...">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-sm btn-potato w-100">Cari</button>
                </div>
            </form>
        </div>

        <div class="card-custom bg-white p-3">
            <div class="table-responsive">
                <table class="table align-middle table-hover" style="font-size: 13px;">
                    <thead class="text-muted border-bottom" style="font-size: 11px;">
                        <tr>
                            <th>MENU</th>
                            <th>KATEGORI</th>
                            <th class="text-end">STOK SAAT INI</th>
                            <th class="text-end">STOK MINIMUM</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/spark-admin-free.png') }}"
                                            class="thumb-stok">
                                        <span class="fw-semibold text-potato-brown">{{ $menu->nama_menu }}</span>
                                    </div>
                                </td>
                                <td>{{ $menu->kategori->nama_kategori ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ $menu->stok }}</td>
                                <td class="text-end text-muted">{{ $menu->stok_minimum }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $menu->status_stok }} px-2 py-1">
                                        {{ ucfirst($menu->status_stok) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-potato" data-bs-toggle="modal"
                                            data-bs-target="#modalTambah{{ $menu->id }}">
                                            <i class="bi bi-plus-lg"></i> Tambah Stok
                                        </button>
                                        <a href="{{ route('admin.stok.riwayat', $menu) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                    </div>

                                    <!-- Modal Tambah Stok -->
                                    <div class="modal fade" id="modalTambah{{ $menu->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-potato-brown">Tambah Stok —
                                                        {{ $menu->nama_menu }}</h6>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.stok.tambah', $menu) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Jumlah Masuk</label>
                                                            <input type="number" name="jumlah" min="1"
                                                                class="form-control" placeholder="Contoh: 20" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Keterangan
                                                                (opsional)</label>
                                                            <input type="text" name="keterangan" class="form-control"
                                                                placeholder="Contoh: Restock dari supplier">
                                                        </div>
                                                        <p class="text-muted small mb-0">Stok saat ini:
                                                            <strong>{{ $menu->stok }}</strong></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                            data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-potato btn-sm">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada menu. Tambahkan menu dulu
                                    di Master Menu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $menus->links() }}
            </div>
        </div>
    </div>
@endsection
