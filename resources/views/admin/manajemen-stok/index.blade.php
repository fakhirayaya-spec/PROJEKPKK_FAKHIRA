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

        .thumb-stok { flex-shrink: 0; }
        .exp-cell { white-space: nowrap; line-height: 1.25; }
        .exp-cell .exp-tgl { font-weight: 700; font-size: .84rem; color: #3d2314; }
        .exp-none { font-size: .76rem; color: #9a8c7e; font-style: italic; }
    </style>
@endpush

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="mb-4 d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1 text-potato-brown">Manajemen Stok</h4>
                <p class="text-muted small mb-0">Pantau stok tiap menu milik setiap kasir dan tambahkan stok masuk (restock) untuk kasir yang dipilih.</p>
            </div>
            @if ($kasirTerpilih)
                <button type="button" class="btn btn-potato text-nowrap" data-bs-toggle="modal" data-bs-target="#modalIsiSemua">
                    <i class="bi bi-box-seam me-1"></i> Isi Semua Stok
                </button>
            @endif
        </div>

        @if (session('sukses'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <b>Belum bisa disimpan:</b> {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @include('layouts.partials.pilih-kasir')

        <div class="card-custom bg-white p-3 mb-3">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="kasir" value="{{ optional($kasirTerpilih)->id }}">
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
                            <th class="text-center">EXP TERDEKAT</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            @php $pakaiExp = \App\Services\StokService::pakaiExp($menu); @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/spark-admin-free.png') }}"
                                            class="thumb-stok">
                                        <span class="fw-semibold text-potato-brown">{{ $menu->nama_menu }}</span>
                                    </div>
                                </td>
                                <td class="text-nowrap">{{ $menu->kategori->nama_kategori ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ $menu->stok }}</td>
                                <td class="text-end text-muted">{{ $menu->stok_minimum }}</td>
                                <td class="text-center exp-cell">
                                    @php
                                        $expTgl = isset($expMenu[$menu->id]) ? \Carbon\Carbon::parse($expMenu[$menu->id]) : null;
                                    @endphp
                                    @if(! $pakaiExp)
                                        <span class="exp-none">Tanpa exp</span>
                                    @elseif($expTgl)
                                        <span class="exp-tgl">{{ $expTgl->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $menu->status_stok }} px-2 py-1">
                                        {{ ucfirst($menu->status_stok) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-potato text-nowrap" data-bs-toggle="modal"
                                            data-bs-target="#modalTambah{{ $menu->id }}">
                                            <i class="bi bi-plus-lg"></i> Tambah Stok
                                        </button>
                                        <a href="{{ route('admin.stok.riwayat', ['menu' => $menu, 'kasir' => optional($kasirTerpilih)->id]) }}"
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
                                                        {{ $menu->nama_menu }}
                                                        <span class="text-muted fw-normal">untuk {{ optional($kasirTerpilih)->name }}</span></h6>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.stok.tambah', $menu) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="kasir_id" value="{{ optional($kasirTerpilih)->id }}">
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
                                                        @if($pakaiExp)
                                                        <div class="mb-2 mt-3">
                                                            <label class="form-label small fw-semibold">Tanggal Exp</label>
                                                            <input type="date" name="tanggal_exp" class="form-control"
                                                                value="{{ \App\Services\StokService::expDefault('menu') }}" min="{{ now()->format('Y-m-d') }}">
                                                            <div class="form-text">Otomatis 14 hari (2 minggu) dari hari ini, bisa diubah. Sisa stok yang lewat tanggal ini otomatis dibuang.</div>
                                                        </div>
                                                        @else
                                                        <div class="form-text mb-2">Minuman dibuat dadakan, jadi tidak pakai tanggal exp.</div>
                                                        @endif
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
                                <td colspan="7" class="text-center text-muted py-4">{{ $kasirTerpilih ? 'Belum ada menu. Tambahkan menu dulu di Master Menu.' : 'Belum ada kasir aktif.' }}</td>
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

    @if ($kasirTerpilih)
        {{-- Modal isi semua stok untuk kasir terpilih: semua menu jadi 50 --}}
        <div class="modal fade" id="modalIsiSemua" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('admin.stok.isi-semua') }}">
                    @csrf
                    <input type="hidden" name="kasir_id" value="{{ $kasirTerpilih->id }}">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold text-potato-brown">Isi Semua Stok
                            <span class="text-muted fw-normal">untuk {{ $kasirTerpilih->name }}</span></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small mb-3">Semua menu di bawah dibuat jadi <b>{{ \App\Services\StokService::STOK_STANDAR }}</b> (sama seperti Master Menu). Menu yang sudah {{ \App\Services\StokService::STOK_STANDAR }} atau lebih tidak diubah. Mau isi jumlah lain untuk satu menu saja? Pakai tombol <b>Tambah Stok</b> di barisnya.</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Tanggal Exp</label>
                            <input type="date" name="tanggal_exp" class="form-control form-control-sm"
                                value="{{ \App\Services\StokService::expDefault('menu') }}" min="{{ now()->format('Y-m-d') }}">
                            <div class="form-text">Otomatis 14 hari dari hari ini, bisa diubah. Minuman tidak pakai exp.</div>
                        </div>
                        <table class="table table-sm align-middle mb-0" style="font-size: 13px;">
                            <thead class="text-muted border-bottom" style="font-size: 11px;">
                                <tr><th>MENU</th><th class="text-end">SEKARANG</th><th class="text-end">JADI</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($daftarIsi as $m)
                                    <tr>
                                        <td class="fw-semibold text-potato-brown">{{ $m->nama_menu }}</td>
                                        <td class="text-end">{{ $m->stok }}</td>
                                        <td class="text-end fw-bold">{{ max($m->stok, \App\Services\StokService::STOK_STANDAR) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-potato btn-sm">Isi Semua jadi {{ \App\Services\StokService::STOK_STANDAR }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
