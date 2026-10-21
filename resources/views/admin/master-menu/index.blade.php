@extends('layouts.admin')

@section('title', 'Master Menu - Potato Curly Crunch')

@push('styles')
<style>
    .text-potato-brown { color: #3d2314; }
    .card-custom { border-radius: 16px; border: 1px solid #efe8e0; box-shadow: 0 2px 8px rgba(61,35,20,0.04); }
    .badge-status-aktif { background-color: #dcfce7; color: #16a34a; }
    .badge-status-nonaktif { background-color: #fee2e2; color: #dc2626; }
    .thumb-menu { width: 44px; height: 44px; object-fit: cover; border-radius: 10px; border: 1px solid #efe8e0; }
    .btn-potato { background: linear-gradient(90deg, #fcae1e 0%, #f7931e 100%); color: #fff; border: none; font-weight: 700; }
    .btn-potato:hover { color: #fff; opacity: .9; }
</style>
@endpush

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-potato-brown">Master Menu</h4>
            <p class="text-muted small mb-0">Kelola daftar menu, kategori, harga, dan status jual.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKategori">
                <i class="bi bi-tags"></i> Kelola Kategori
            </button>
            <a href="{{ route('admin.menu.create') }}" class="btn btn-potato btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Menu
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('gagal'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('gagal') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card-custom bg-white p-3 mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm" placeholder="Cari nama menu...">
            </div>
            <div class="col-6 col-md-3">
                <select name="kategori_menu_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoris as $k)
                        <option value="{{ $k->id }}" {{ request('kategori_menu_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-potato w-100">Cari</button>
                <a href="{{ route('admin.menu.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>KODE</th>
                        <th>MENU</th>
                        <th>KATEGORI</th>
                        <th class="text-end">HARGA JUAL</th>
                        <th class="text-end">MODAL</th>
                        <th class="text-center">STOK</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-end">TERJUAL</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($menus as $menu)
                    <tr>
                        <td class="fw-bold text-potato-brown">{{ $menu->kode_menu }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $menu->gambar ? asset('storage/'.$menu->gambar) : asset('assets_dashboard/images/spark-admin-free.png') }}" class="thumb-menu">
                                <div>
                                    <div class="fw-semibold text-potato-brown">{{ $menu->nama_menu }}</div>
                                    @if($menu->sausBawaan || $menu->bubukBawaan)
                                        <div class="text-muted" style="font-size: 11px;">
                                            <i class="bi bi-droplet-half"></i>
                                            {{ $menu->sausBawaan->nama ?? $menu->bubukBawaan->nama }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $menu->kategori->nama_kategori ?? '-' }}</td>
                        <td class="text-end fw-bold text-potato-brown">Rp {{ number_format($menu->harga, 0, ',', '.') }}</td>
                        <td class="text-end">
                            @if($menu->harga_modal > 0)
                                Rp {{ number_format($menu->harga_modal, 0, ',', '.') }}
                                <div class="{{ $menu->laba_per_porsi >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 11px;">
                                    laba Rp {{ number_format($menu->laba_per_porsi, 0, ',', '.') }}
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="fw-bold text-potato-brown">{{ $menu->stok }}</div>
                            @if($menu->status_stok === 'habis')
                                <span class="badge badge-status-nonaktif" style="font-size: 10px;">Habis</span>
                            @elseif($menu->status_stok === 'menipis')
                                <span class="badge" style="font-size: 10px; background:#fef3c7; color:#b45309;">Menipis</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $menu->status == 'aktif' ? 'badge-status-aktif' : 'badge-status-nonaktif' }} px-2 py-1">
                                {{ ucfirst($menu->status) }}
                            </span>
                        </td>
                        <td class="text-end">{{ (int) $menu->terjual }} pcs</td>
                        <td class="text-center">
                            <a href="{{ route('admin.menu.edit', $menu) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#modalHapus{{ $menu->id }}">
                                <i class="bi bi-trash"></i>
                            </button>

                            <div class="modal fade" id="modalHapus{{ $menu->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body p-4 text-center">
                                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                                            <p class="mt-3 mb-1 fw-semibold">Hapus menu "{{ $menu->nama_menu }}"?</p>
                                            <p class="text-muted small">Tindakan ini tidak bisa dibatalkan.</p>
                                            <div class="d-flex justify-content-center gap-2 mt-3">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <form action="{{ route('admin.menu.destroy', $menu) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Ya, Hapus</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada menu. Klik "Tambah Menu" untuk mulai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $menus->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="modalKategori" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold text-potato-brown">Kelola Kategori Menu</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.kategori-menu.store') }}" method="POST" class="d-flex gap-2 mb-3">
                    @csrf
                    <input type="text" name="nama_kategori" class="form-control form-control-sm" placeholder="Nama kategori baru..." required>
                    <button type="submit" class="btn btn-sm btn-potato">Tambah</button>
                </form>
                <ul class="list-group">
                    @forelse($kategoris as $k)
                        <li class="list-group-item d-flex justify-content-between align-items-center" style="font-size: 13px;">
                            {{ $k->nama_kategori }}
                            <form action="{{ route('admin.kategori-menu.destroy', $k) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm text-danger border-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center">Belum ada kategori.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
