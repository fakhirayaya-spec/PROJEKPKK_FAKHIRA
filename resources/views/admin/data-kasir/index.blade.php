@extends('layouts.admin')

@section('title', 'Data Kasir - Potato Curly Crunch')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-potato-brown">Data Kasir</h4>
            <p class="text-muted small mb-0">Kelola akun login, biodata, dan status kasir yang bertugas.</p>
        </div>
        <a href="{{ route('admin.kasir.create') }}" class="btn btn-potato btn-sm">
            <i class="bi bi-person-plus"></i> Tambah Kasir
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon brown"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-label">Total Kasir</div>
                    <div class="stat-value">{{ \App\Models\User::kasir()->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-label">Aktif</div>
                    <div class="stat-value">{{ \App\Models\User::kasir()->aktif()->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom bg-white p-3 mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm" placeholder="Cari nama kasir...">
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
                <a href="{{ route('admin.kasir.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr>
                        <th>KASIR</th>
                        <th>EMAIL</th>
                        <th>JENIS KELAMIN</th>
                        <th>TANGGAL LAHIR</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($kasirs as $kasir)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($kasir->avatar)
                                    <img src="{{ asset('storage/'.$kasir->avatar) }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                         style="width: 40px; height: 40px; background: #fceecb; color: #3d2314;">
                                        {{ strtoupper(substr($kasir->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-potato-brown">{{ $kasir->name }}</div>
                                    @if($kasir->alamat)
                                        <div class="text-muted" style="font-size: 11px;" title="{{ $kasir->alamat }}">
                                            <i class="bi bi-geo-alt"></i> {{ \Illuminate\Support\Str::limit($kasir->alamat, 30) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $kasir->email }}</td>
                        <td>
                            @if($kasir->jenis_kelamin == 'L')
                                <span class="badge-soft blue">Laki-laki</span>
                            @elseif($kasir->jenis_kelamin == 'P')
                                <span class="badge-soft red">Perempuan</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $kasir->tanggal_lahir ? $kasir->tanggal_lahir->format('d M Y') : '-' }}</td>
                        <td class="text-center">
                            <span class="badge-soft {{ $kasir->status == 'aktif' ? 'green' : 'red' }}">
                                {{ ucfirst($kasir->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                <a href="{{ route('admin.kasir.edit', $kasir) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('admin.kasir.toggle-status', $kasir) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $kasir->status == 'aktif' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            title="{{ $kasir->status == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>

                                <form action="{{ route('admin.kasir.reset-password', $kasir) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Reset password {{ $kasir->name }} ke: password123 ?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset Password">
                                        <i class="bi bi-key"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada akun kasir. Klik "Tambah Kasir" untuk mulai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $kasirs->links() }}
        </div>
    </div>
</div>
@endsection
