@extends('layouts.admin')

@section('title', 'Permintaan Stok - Potato Curly Crunch')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Permintaan Stok</h4>
        <p class="text-muted small mb-0">Pengajuan stok dari kasir (bahan operasional, menu, bumbu). Stok bertambah otomatis setelah kamu setujui.</p>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <ul class="nav nav-pills mb-3 gap-1">
        @foreach(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui (tunggu kasir)', 'selesai' => 'Berhasil', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan', 'semua' => 'Semua'] as $k => $label)
            <li class="nav-item">
                <a class="nav-link {{ $status === $k ? 'active' : '' }}" style="{{ $status === $k ? 'background:#f5a623;color:#3d2314;' : 'color:#3d2314;' }}"
                   href="{{ route('admin.permintaan-stok.index', ['status' => $k]) }}">
                    {{ $label }}@if($k !== 'semua' && ($hitung[$k] ?? 0)) <span class="badge text-bg-light ms-1">{{ $hitung[$k] }}</span>@endif
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card-custom bg-white p-3">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
                <thead class="text-muted border-bottom" style="font-size: 11px;">
                    <tr><th>TANGGAL</th><th>KASIR</th><th>BARANG</th><th class="text-end">DIAJUKAN</th><th>STATUS</th><th class="text-end">AKSI</th></tr>
                </thead>
                <tbody>
                    @forelse($daftar as $p)
                        <tr>
                            <td class="text-muted">{{ $p->created_at->format('d M Y H:i') }}</td>
                            <td class="fw-semibold">{{ $p->kasir?->name ?? '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $p->nama_item }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $p->label_jenis }}@if($p->catatan) · "{{ $p->catatan }}"@endif</div>
                                @if($p->catatan_admin)
                                    <div class="text-muted" style="font-size: 11px;"><i class="bi bi-chat-left-text"></i> {{ $p->catatan_admin }}</div>
                                @endif
                            </td>
                            <td class="text-end">{{ $p->jumlah }} {{ $p->satuan }}</td>
                            <td>
                                @php $warna = ['menunggu' => 'warning', 'disetujui' => 'info', 'selesai' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'][$p->status] ?? 'secondary'; @endphp
                                <span class="badge text-bg-{{ $warna }}">{{ ['disetujui' => 'Disetujui', 'selesai' => 'Berhasil'][$p->status] ?? ucfirst($p->status) }}</span>
                                @if($p->status === 'disetujui')
                                    <div class="text-muted" style="font-size: 11px;">menunggu konfirmasi kasir</div>
                                @elseif($p->status === 'selesai' && $p->dikonfirmasi_pada)
                                    <div class="text-muted" style="font-size: 11px;">diterima {{ $p->dikonfirmasi_pada->format('d M H:i') }}</div>
                                @endif
                                @if($p->ditangani_pada)
                                    <div class="text-muted" style="font-size: 11px;">{{ $p->ditangani_pada->format('d M H:i') }}@if($p->penangan) · {{ $p->penangan->name }}@endif</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($p->status === 'menunggu')
                                    <button type="button" class="btn btn-sm btn-success btn-setuju"
                                        data-bs-toggle="modal" data-bs-target="#modalSetuju"
                                        data-action="{{ route('admin.permintaan-stok.setujui', $p) }}"
                                        data-nama="{{ $p->nama_item }}" data-kasir="{{ $p->kasir?->name }}"
                                        data-jumlah="{{ $p->jumlah }}" data-satuan="{{ $p->satuan }}"
                                        data-jenis="{{ $p->jenis }}" data-harga="{{ $p->jenis === 'operasional' ? (int) ($hargaBahan[$p->bahan_kode] ?? 0) : ($p->jenis === 'menu' ? (int) ($hargaMenu[$p->item_id] ?? 0) : 0) }}">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-tolak"
                                        data-bs-toggle="modal" data-bs-target="#modalTolak"
                                        data-action="{{ route('admin.permintaan-stok.tolak', $p) }}"
                                        data-nama="{{ $p->nama_item }}" data-kasir="{{ $p->kasir?->name }}">
                                        Tolak
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pengajuan{{ $status !== 'semua' ? ' dengan status ' . $status : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $daftar->links() }}</div>
    </div>
</div>

{{-- Modal setujui --}}
<div class="modal fade" id="modalSetuju" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formSetuju" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Setujui pengajuan stok</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="infoSetuju"></p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jumlah yang disetujui <span class="text-muted fw-normal" id="satuanSetuju"></span></label>
                    <input type="number" name="jumlah" id="inpJumlahSetuju" min="1" max="1000" required class="form-control">
                </div>
                <div class="mb-3" id="boxBiaya">
                    <label class="form-label small fw-semibold">Biaya pembelian (Rp) <span id="wajibBiaya" class="text-danger">*</span></label>
                    <input type="number" name="biaya" id="inpBiaya" min="0" class="form-control" placeholder="0">
                    <div class="form-text" id="infoBiaya"></div>
                </div>
                <div>
                    <label class="form-label small fw-semibold">Catatan untuk kasir <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="text" name="catatan_admin" maxlength="200" class="form-control">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success">Setujui</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal tolak --}}
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formTolak" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Tolak pengajuan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="infoTolak"></p>
                <label class="form-label small fw-semibold">Alasan penolakan <span class="text-danger">*</span></label>
                <input type="text" name="catatan_admin" maxlength="200" minlength="3" required class="form-control" placeholder="contoh: stok gudang masih cukup">
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger">Tolak pengajuan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        let hargaSatuan = 0;
        let jenis = '';
        const rp = n => 'Rp ' + Number(n).toLocaleString('id-ID');
        const inpJumlah = document.getElementById('inpJumlahSetuju');
        const inpBiaya = document.getElementById('inpBiaya');

        function hitungBiaya() {
            if (jenis !== 'bumbu' && hargaSatuan > 0) {
                inpBiaya.value = (parseInt(inpJumlah.value) || 0) * hargaSatuan;
            }
        }

        document.querySelectorAll('.btn-setuju').forEach(function (b) {
            b.addEventListener('click', function () {
                document.getElementById('formSetuju').action = b.dataset.action;
                document.getElementById('infoSetuju').textContent = (b.dataset.kasir || 'Kasir') + ' mengajukan ' + b.dataset.nama + ' sebanyak ' + b.dataset.jumlah + ' ' + b.dataset.satuan + '.';
                document.getElementById('satuanSetuju').textContent = '(' + b.dataset.satuan + ')';
                inpJumlah.value = b.dataset.jumlah;
                jenis = b.dataset.jenis;
                hargaSatuan = parseInt(b.dataset.harga) || 0;

                const wajib = jenis === 'operasional';
                const tanpaBiaya = jenis === 'bumbu';
                document.getElementById('boxBiaya').style.display = tanpaBiaya ? 'none' : '';
                document.getElementById('wajibBiaya').style.display = wajib ? '' : 'none';
                inpBiaya.required = wajib;
                inpBiaya.value = '';
                let info = '';
                if (wajib) {
                    info = 'Otomatis ' + (hargaSatuan ? '(harga per ' + b.dataset.satuan + ' ' + rp(hargaSatuan) + ' x jumlah)' : '') + ', tercatat di Pengeluaran. Bisa diubah.';
                } else if (!tanpaBiaya) {
                    info = hargaSatuan
                        ? 'Otomatis (harga modal ' + rp(hargaSatuan) + ' x jumlah), tercatat di Pengeluaran Bahan Baku. Bisa diubah.'
                        : 'Harga modal menu belum diisi. Isi manual kalau mau dicatat di Pengeluaran Bahan Baku.';
                }
                document.getElementById('infoBiaya').textContent = info;
                hitungBiaya();
            });
        });

        inpJumlah.addEventListener('input', hitungBiaya);

        document.querySelectorAll('.btn-tolak').forEach(function (b) {
            b.addEventListener('click', function () {
                document.getElementById('formTolak').action = b.dataset.action;
                document.getElementById('infoTolak').textContent = 'Pengajuan ' + b.dataset.nama + ' dari ' + (b.dataset.kasir || 'kasir') + '.';
            });
        });
    })();
</script>
@endpush
