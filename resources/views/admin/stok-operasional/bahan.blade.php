@extends('layouts.admin')

@section('title', 'Kelola Bahan Operasional - Potato Curly Crunch')

@push('styles')
    @include('layouts.partials.stok-operasional-style')
@endpush

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 so-brown">Kelola Bahan Operasional</h4>
                <p class="text-muted small mb-0">Tambah bahan baru, atur aturan berkurangnya, dan pasang gambar. Bahan baru otomatis dapat stok awal di semua kasir.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stok-operasional.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Stok
                </a>
                <button type="button" class="btn btn-sm so-btn" data-bs-toggle="modal" data-bs-target="#modalTambahBahan">
                    <i class="bi bi-plus-lg"></i> Tambah Bahan
                </button>
            </div>
        </div>

        @if (session('sukses'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="so-card p-3">
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
                    <thead class="text-muted border-bottom" style="font-size: 11px;">
                        <tr>
                            <th>BAHAN</th>
                            <th>ATURAN BERKURANG</th>
                            <th class="text-end">STOK AWAL</th>
                            <th class="text-end">BATAS MENIPIS</th>
                            <th>ISI ULANG</th>
                            <th>PENGELUARAN</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bahans as $b)
                            <tr class="{{ $b->status === 'aktif' ? '' : 'text-muted' }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="so-thumb sm">
                                            @if ($b->gambar_url)
                                                <img src="{{ $b->gambar_url }}" alt="{{ $b->nama }}" onerror="soGagal(this)">
                                            @else
                                                <i class="bi {{ $b->ikon }}"></i>
                                            @endif
                                        </div>
                                        <span class="fw-semibold so-brown">{{ $b->nama }}</span>
                                    </div>
                                </td>
                                <td>{{ $b->aturan }}</td>
                                <td class="text-end text-nowrap">{{ $b->stok_awal }} {{ $b->satuan }}</td>
                                <td class="text-end text-nowrap">{{ $b->stok_menipis }}</td>
                                <td class="text-nowrap">
                                    {{ $b->isi_per_unit > 1 ? $b->info_isi : $b->unit_isi }}
                                    @if ($b->harga_per_unit > 0)
                                        <div class="text-muted" style="font-size: 11px;">Rp {{ number_format($b->harga_per_unit, 0, ',', '.') }} / {{ $b->unit_isi }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @php $kat = $b->kategori_pengeluaran ?? 'operasional'; @endphp
                                    <span class="badge-soft {{ $kat === 'bahan_baku' ? 'amber' : 'blue' }}">{{ \App\Models\BahanOperasional::KATEGORI_PENGELUARAN[$kat] ?? 'Operasional' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="so-badge {{ $b->status === 'aktif' ? 'so-badge-aman' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $b->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Ubah"
                                            data-bs-toggle="modal" data-bs-target="#modalUbahBahan{{ $b->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.stok-operasional.bahan.toggle', $b) }}" method="POST" class="m-0">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                title="{{ $b->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="bi {{ $b->status === 'aktif' ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                            </button>
                                        </form>
                                        @php
                                            $jmlRiwayat = (int) ($punyaRiwayat[$b->kode] ?? 0);
                                            $pesanHapus = $jmlRiwayat > 0
                                                ? "Hapus bahan {$b->nama}? Stok di semua kasir dan {$jmlRiwayat} catatan riwayat pemakaiannya ikut terhapus PERMANEN. Kalau hanya tidak dipakai lagi, lebih aman dinonaktifkan."
                                                : "Hapus bahan {$b->nama}? Tindakan ini tidak bisa dibatalkan.";
                                        @endphp
                                        <form action="{{ route('admin.stok-operasional.bahan.destroy', $b) }}" method="POST" class="m-0"
                                            data-konfirmasi="{{ $pesanHapus }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="so-hint mt-3">
                Mengubah "stok awal" hanya berlaku untuk kasir baru dan untuk ukuran bar stok. Stok kasir yang sudah ada tidak berubah, gunakan Isi Ulang di halaman stok.
                Menghapus bahan yang sudah punya riwayat ikut menghapus riwayatnya. Kalau hanya tidak dipakai lagi, nonaktifkan saja.
            </div>
        </div>
    </div>

    {{-- Modal tambah --}}
    <div class="modal fade" id="modalTambahBahan" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold so-brown">Tambah Bahan Operasional</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.stok-operasional.bahan.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('admin.stok-operasional._form-bahan', ['bahan' => null, 'dasarOptions' => $dasarOptions])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm so-btn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal ubah (satu per bahan) --}}
    @foreach ($bahans as $b)
        <div class="modal fade" id="modalUbahBahan{{ $b->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold so-brown">Ubah Bahan: {{ $b->nama }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.stok-operasional.bahan.update', $b) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            @include('admin.stok-operasional._form-bahan', ['bahan' => $b, 'dasarOptions' => $dasarOptions])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm so-btn">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
