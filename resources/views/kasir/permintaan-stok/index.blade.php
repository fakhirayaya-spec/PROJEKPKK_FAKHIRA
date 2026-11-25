@extends('layouts.kasir')

@section('title', 'Ajukan Stok')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Ajukan Stok</h4>
        <p class="text-muted small mb-0">Stok hampir habis atau butuh tambahan? Ajukan ke admin: bahan operasional, menu, atau bumbu. Setelah admin menyetujui, kamu perlu <b>konfirmasi barang sudah diterima</b>. Stok baru bertambah dan pengajuan berhasil setelah kamu konfirmasi.</p>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->has('status'))
        <div class="alert alert-danger">{{ $errors->first('status') }}</div>
    @endif

    @php $perluKonfirmasi = $riwayat->getCollection()->where('status', 'disetujui')->count(); @endphp
    @if($perluKonfirmasi)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-box-seam fs-5"></i>
            <div>Ada <b>{{ $perluKonfirmasi }}</b> pengajuan yang sudah disetujui admin. Cek barangnya, lalu tekan <b>Konfirmasi diterima</b> supaya stok masuk.</div>
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
                                <option value="{{ $bm->id }}" data-satuan="bungkus" data-isi="{{ $bm->isi_bungkus_efektif }}" @selected((string) old('bumbu_id') === (string) $bm->id)>{{ $bm->nama }} ({{ $bm->jenis === 'saus' ? 'saus' : 'tabur' }}, sisa {{ $bm->stok }} pakai, 1 bks = {{ $bm->isi_bungkus_efektif }} pakai)</option>
                            @endforeach
                        </select>
                        @error('bumbu_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Jumlah yang diajukan <span class="text-muted fw-normal" id="satuanTeks"></span></label>
                        <input type="number" name="jumlah" min="1" max="1000" value="{{ old('jumlah') }}" required class="form-control @error('jumlah') is-invalid @enderror" placeholder="contoh: 20">
                        @error('jumlah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text" id="hintIsi"></div>
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
                                        @if(in_array($r->status, ['disetujui', 'selesai']) && $r->jumlah_disetujui && $r->jumlah_disetujui != $r->jumlah)
                                            <div class="text-success" style="font-size: 11px;">disetujui {{ $r->jumlah_disetujui }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php $warna = ['menunggu' => 'warning', 'disetujui' => 'info', 'selesai' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'][$r->status] ?? 'secondary'; @endphp
                                        <span class="badge text-bg-{{ $warna }}">{{ ['disetujui' => 'Disetujui', 'selesai' => 'Berhasil'][$r->status] ?? ucfirst($r->status) }}</span>
                                        @if($r->status === 'disetujui')
                                            <div class="text-muted" style="font-size: 11px;">menunggu konfirmasimu</div>
                                        @elseif($r->status === 'selesai' && $r->dikonfirmasi_pada)
                                            <div class="text-muted" style="font-size: 11px;">diterima {{ $r->dikonfirmasi_pada->format('d M H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($r->status === 'menunggu')
                                            <form method="POST" action="{{ route('kasir.permintaan-stok.batal', $r) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Batalkan</button>
                                            </form>
                                        @elseif($r->status === 'disetujui')
                                            <form method="POST" action="{{ route('kasir.permintaan-stok.konfirmasi', $r) }}" class="m-0"
                                                data-judul="Barang sudah diterima?" data-ya="Ya, sudah diterima" data-tipe="tanya"
                                                data-konfirmasi="Apakah {{ $r->nama_item }} sebanyak {{ $r->jumlah_disetujui ?: $r->jumlah }} {{ $r->satuan }} benar-benar sudah kamu terima?&#10;&#10;Stok kamu akan langsung bertambah.">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-potato text-nowrap"><i class="bi bi-check2-circle"></i> Konfirmasi diterima</button>
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
            hintIsi();
        }

        // Bumbu & saus diajukan per bungkus: tampilkan berapa kali pakai yang akan masuk
        function hintIsi() {
            const aktif = document.querySelector('.pilih-item[data-jenis="' + jenis.value + '"] select');
            const opsi = aktif && aktif.options[aktif.selectedIndex];
            const isi = opsi ? parseInt(opsi.dataset.isi) || 0 : 0;
            const n = parseInt(document.querySelector('input[name="jumlah"]').value) || 0;
            document.getElementById('hintIsi').textContent = isi && jenis.value === 'bumbu' ? (n ? '= ' + (n * isi) + ' kali pakai' : '1 bungkus = ' + isi + ' kali pakai') : '';
        }
        document.querySelector('input[name="jumlah"]').addEventListener('input', hintIsi);

        jenis.addEventListener('change', tampil);
        document.querySelectorAll('.pilih-item select').forEach(function (s) { s.addEventListener('change', satuan); });
        tampil();
    })();
</script>
@endpush
