@extends('layouts.admin')

@section('title', 'Stok Operasional - Potato Curly Crunch')

@push('styles')
    @include('layouts.partials.stok-operasional-style')
@endpush

@section('content')
    <div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 so-brown">Stok Operasional</h4>
                <p class="text-muted small mb-0">Gas, minyak, dus, cup, es batu, dan bahan lain milik tiap kasir. Berkurang otomatis setiap transaksi berhasil.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stok-operasional.riwayat') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-clock-history"></i> Riwayat
                </a>
                <a href="{{ route('admin.stok-operasional.bahan.index') }}" class="btn btn-sm so-btn">
                    <i class="bi bi-sliders"></i> Kelola Bahan
                </a>
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

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="so-card so-stat">
                    <div class="ic brown"><i class="bi bi-boxes"></i></div>
                    <div><div class="lbl">Bahan aktif</div><div class="val">{{ $bahanList->count() }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="so-card so-stat">
                    <div class="ic amber"><i class="bi bi-exclamation-triangle"></i></div>
                    <div><div class="lbl">Stok menipis (kasir aktif)</div><div class="val">{{ $ringkas['menipis'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="so-card so-stat">
                    <div class="ic red"><i class="bi bi-x-octagon"></i></div>
                    <div><div class="lbl">Stok habis (kasir aktif)</div><div class="val">{{ $ringkas['habis'] }}</div></div>
                </div>
            </div>
        </div>

        @forelse ($kasirs as $kasir)
            <div class="so-card p-3 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <b class="so-brown fs-6">{{ $kasir->name }}</b>
                        <span class="text-muted small ms-1">{{ $kasir->email }}</span>
                    </div>
                    @if ($kasir->status !== 'aktif')
                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                    @endif
                </div>

                <div class="so-grid">
                    @foreach ($stok[$kasir->id] as $kode => $b)
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
                                <div class="min-w-0">
                                    <div class="nm">{{ $m->nama }}</div>
                                    <div class="rule">{{ $m->aturan }}</div>
                                </div>
                            </div>

                            <div class="so-num">
                                <div><span class="n">{{ $b->stok }}</span><span class="u">{{ $m->satuan }}</span></div>
                                <span class="so-badge so-badge-{{ $b->status_stok }}">{{ ucfirst($b->status_stok) }}</span>
                            </div>
                            <div class="so-bar"><span class="{{ $b->status_stok }}" style="width: {{ $b->persen }}%"></span></div>

                            <form action="{{ route('admin.stok-operasional.isi-ulang', $kasir) }}" method="POST" class="so-refill"
                                style="flex-wrap: wrap;" data-harga="{{ (int) $m->harga_per_unit }}">
                                @csrf
                                <input type="hidden" name="bahan" value="{{ $kode }}">
                                <input type="number" name="jumlah" min="1" max="1000" value="{{ $m->default_isi }}"
                                    class="form-control form-control-sm so-jumlah" required>
                                <span class="unit">{{ $m->unit_isi }}</span>
                                <div class="input-group input-group-sm" style="flex: 1 1 100%;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="biaya" min="1" class="form-control so-biaya" style="width: auto;"
                                        value="{{ (int) $m->harga_per_unit > 0 ? $m->default_isi * (int) $m->harga_per_unit : '' }}"
                                        placeholder="Biaya beli (masuk Pengeluaran)" required>
                                </div>
                                <button type="submit" class="btn btn-sm so-btn w-100">Isi stok &amp; catat pengeluaran</button>
                            </form>
                            @if ($m->isi_per_unit > 1)
                                <div class="so-hint" style="margin-top:-4px;">{{ $m->info_isi }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="so-card p-4 text-center text-muted">Belum ada kasir. Tambahkan dulu di menu Data Kasir.</div>
        @endforelse
    </div>
@endsection

@push('scripts')
<script>
    // Biaya otomatis = jumlah x harga beli per satuan (kalau harga sudah diisi di Kelola Bahan). Tetap bisa diubah manual.
    document.querySelectorAll('.so-refill').forEach(function (f) {
        const harga = parseInt(f.dataset.harga) || 0;
        if (!harga) return;
        const jumlah = f.querySelector('.so-jumlah');
        const biaya = f.querySelector('.so-biaya');
        jumlah.addEventListener('input', function () {
            const n = parseInt(jumlah.value) || 0;
            biaya.value = n > 0 ? n * harga : '';
        });
    });
</script>
@endpush
