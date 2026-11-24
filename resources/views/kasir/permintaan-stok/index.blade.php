@extends('layouts.kasir')

@section('title', 'Ajukan Stok')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Ajukan Stok</h4>
        <p class="text-muted small mb-0">Stok hampir habis atau butuh tambahan? Ajukan ke admin: bahan operasional, menu, atau bumbu. Stok bertambah setelah admin menyetujui.</p>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card-custom bg-white p-3">
                <b class="text-potato-brown d-block mb-3"><i class="bi bi-send-plus"></i> Buat pengajuan</b>

                <form method="POST" action="{{ route('kasir.permintaan-stok.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Jenis stok</label>
                        <select name="jenis" id="jenis" class="form-select @error('jenis') is-invalid @enderror" required>
                            @foreach($jenisList as $kunci => $label)
                                <option value="{{ $kunci }}" @selected(old('jenis', 'operasional') === $kunci)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 pilih-item" data-jenis="operasional">
                        <label class="form-label small fw-semibold">Bahan operasional</label>
                        <select name="bahan_kode" class="form-select @error('bahan_kode') is-invalid @enderror">
                            <option value="">-- Pilih bahan --</option>
                            @foreach($bahanList as $b)
                                <option value="{{ $b->kode }}" data-satuan="{{ $b->unit_isi ?: $b->satuan }}" @selected(old('bahan_kode') === $b->kode)>{{ $b->nama }}</option>
                            @endforeach
                        </select>
                        @error('bahan_kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 pilih-item" data-jenis="menu">
                        <label class="form-label small fw-semibold">Menu / produk</label>
                        <select name="menu_id" class="form-select @error('menu_id') is-invalid @enderror">
                            <option value="">-- Pilih menu --</option>
                            @foreach($menuList as $m)
                                <option value="{{ $m->id }}" data-satuan="porsi" @selected((string) old('menu_id') === (string) $m->id)>{{ $m->nama_menu }} (sisa {{ $m->stok }})</option>
                            @endforeach
                        </select>
                        @error('menu_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 pilih-item" data-jenis="bumbu">
                        <label class="form-label small fw-semibold">Bumbu</label>
                        <select name="bumbu_id" class="form-select @error('bumbu_id') is-invalid @enderror">
                            <option value="">-- Pilih bumbu --</option>
                            @foreach($bumbuList as $bm)
                                <option value="{{ $bm->id }}" data-satuan="{{ $bm->satuan }}" @selected((string) old('bumbu_id') === (string) $bm->id)>{{ $bm->nama }} ({{ $bm->jenis === 'saus' ? 'saus' : 'tabur' }}, sisa {{ $bm->stok }})</option>
                            @endforeach
                        </select>
                        @error('bumbu_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Jumlah yang diajukan <span class="text-muted fw-normal" id="satuanTeks"></span></label>
                        <input type="number" name="jumlah" min="1" max="1000" value="{{ old('jumlah') }}" required class="form-control @error('jumlah') is-invalid @enderror" placeholder="contoh: 20">
                        @error('jumlah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="catatan" maxlength="200" value="{{ old('catatan') }}" class="form-control" placeholder="contoh: stok tinggal sedikit, besok ada pesanan banyak">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-potato w-100"><i class="bi bi-send"></i> Kirim pengajuan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-custom bg-white p-3">
                <b class="text-potato-brown d-block mb-3">Pengajuan saya</b>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
                        <thead class="text-muted border-bottom" style="font-size: 11px;">
                            <tr><th>TANGGAL</th><th>BARANG</th><th class="text-end">JUMLAH</th><th>STATUS</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($riwayat as $r)
                                <tr>
                                    <td class="text-muted">{{ $r->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $r->nama_item }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $r->label_jenis }}@if($r->catatan) · {{ $r->catatan }}@endif</div>
                                        @if($r->catatan_admin)
                                            <div style="font-size: 11px;" class="{{ $r->status === 'ditolak' ? 'text-danger' : 'text-muted' }}"><i class="bi bi-chat-left-text"></i> Admin: {{ $r->catatan_admin }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ $r->jumlah }} {{ $r->satuan }}
                                        @if($r->status === 'disetujui' && $r->jumlah_disetujui && $r->jumlah_disetujui != $r->jumlah)
                                            <div class="text-success" style="font-size: 11px;">disetujui {{ $r->jumlah_disetujui }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php $warna = ['menunggu' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'][$r->status] ?? 'secondary'; @endphp
                                        <span class="badge text-bg-{{ $warna }}">{{ ucfirst($r->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if($r->status === 'menunggu')
                                            <form method="POST" action="{{ route('kasir.permintaan-stok.batal', $r) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Batalkan</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $riwayat->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const jenis = document.getElementById('jenis');
        const satuanTeks = document.getElementById('satuanTeks');

        function tampil() {
            document.querySelectorAll('.pilih-item').forEach(function (el) {
                const aktif = el.dataset.jenis === jenis.value;
                el.style.display = aktif ? '' : 'none';
                // field yang disembunyikan tidak ikut terkirim
                el.querySelector('select').disabled = !aktif;
            });
            satuan();
        }

        function satuan() {
            const aktif = document.querySelector('.pilih-item[data-jenis="' + jenis.value + '"] select');
            const opsi = aktif && aktif.options[aktif.selectedIndex];
            satuanTeks.textContent = opsi && opsi.dataset.satuan ? '(' + opsi.dataset.satuan + ')' : '';
        }

        jenis.addEventListener('change', tampil);
        document.querySelectorAll('.pilih-item select').forEach(function (s) { s.addEventListener('change', satuan); });
        tampil();
    })();
</script>
@endpush
