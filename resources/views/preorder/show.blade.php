@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.kasir')

@section('title', $po->kode_preorder . ' - Pre-Order')

@section('content')
@php
    $tahap = ['baru' => 'Pesanan masuk', 'diproses' => 'Diproses', 'siap' => 'Siap diambil', 'selesai' => 'Selesai'];
    $urutan = ['baru' => 0, 'diproses' => 1, 'siap' => 2, 'selesai' => 3];
    $posisi = $urutan[$po->status] ?? -1;
    // Hanya kasir yang boleh mengubah pre-order. Admin hanya memantau progresnya.
    $bisaKelola = auth()->user()->role === 'kasir';
@endphp
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">
    <div class="form-page" style="max-width: 1180px;">
        <div class="page-head">
            <div>
                <div class="crumb"><a href="{{ route($rp . 'index') }}">Pre-Order</a> <i class="bi bi-chevron-right"></i> <span>{{ $po->kode_preorder }}</span></div>
                <h4 class="mb-0">{{ $po->kode_preorder }} @include('preorder._badge', ['status' => $po->status])</h4>
                <p>Dicatat oleh {{ $po->pembuat->name ?? '-' }} pada {{ $po->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 no-print">
                @if($po->nomor_wa)
                    <a href="https://wa.me/{{ $po->nomor_wa }}?text={{ rawurlencode($po->pesan_wa) }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm"><i class="bi bi-whatsapp"></i> Kabari via WhatsApp</a>
                @endif
                <button onclick="window.print()" class="btn btn-outline-potato btn-sm"><i class="bi bi-printer"></i> Cetak</button>
            </div>
        </div>

        @if(session('sukses')) <div class="alert alert-success no-print">{{ session('sukses') }}</div> @endif
        @unless($bisaKelola)
            <div class="alert alert-info py-2 small no-print"><i class="bi bi-eye me-1"></i> Mode lihat saja. Status pre-order diubah oleh kasir.</div>
        @endunless
        @if($errors->any())
            <div class="alert alert-danger no-print">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        @if($po->status === 'dibatalkan')
            <div class="alert alert-danger"><b><i class="bi bi-x-octagon"></i> Pre-order dibatalkan.</b> Alasan: {{ $po->alasan_batal ?? '-' }}.
                @if($po->dp > 0) <div class="small mt-1">DP Rp {{ number_format($po->dp, 0, ',', '.') }} perlu dikembalikan atau diurus dengan pelanggan.</div> @endif
            </div>
        @elseif($po->status === 'selesai')
            <div class="alert alert-success"><b><i class="bi bi-check-circle"></i> Pre-order selesai</b> dan tercatat sebagai transaksi
                @if($po->transaksi)
                    <a class="fw-bold" href="{{ auth()->user()->role === 'admin' ? route('admin.riwayat.show', $po->transaksi) : route('kasir.transaksi.struk', $po->transaksi) }}">{{ $po->transaksi->kode_transaksi }}</a>.
                @endif
            </div>
        @endif

        {{-- Tahapan --}}
        @if($po->status !== 'dibatalkan')
        <div class="form-card mb-3">
            <div class="form-card-body d-flex justify-content-between text-center" style="gap: 8px;">
                @foreach($tahap as $k => $lbl)
                    @php $ya = $posisi >= $urutan[$k]; @endphp
                    <div style="flex: 1;">
                        <div style="width: 34px; height: 34px; margin: 0 auto 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; {{ $ya ? 'background: #fcae1e; color: #3a1f0a;' : 'background: #eee; color: #999;' }}">
                            @if($ya) <i class="bi bi-check-lg"></i> @else {{ $urutan[$k] + 1 }} @endif
                        </div>
                        <div class="small {{ $ya ? 'fw-bold text-potato-brown' : 'text-muted' }}">{{ $lbl }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="form-card">
                    <div class="form-card-head">
                        <span class="ico"><i class="bi bi-person-lines-fill"></i></span>
                        <div><h6>Pelanggan & jadwal</h6></div>
                    </div>
                    <div class="form-card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted fw-semibold">Nama</dt><dd class="col-7 fw-bold">{{ $po->nama_pelanggan }}</dd>
                            <dt class="col-5 text-muted fw-semibold">WhatsApp</dt><dd class="col-7">{{ $po->no_whatsapp ?: '-' }}</dd>
                            <dt class="col-5 text-muted fw-semibold">Diambil</dt>
                            <dd class="col-7">{{ $po->tanggal_ambil->translatedFormat('l, d F Y') }}{{ $po->jam_ambil ? ' · ' . $po->jam_ambil : '' }}</dd>
                            <dt class="col-5 text-muted fw-semibold">Catatan</dt><dd class="col-7">{{ $po->catatan ?: '-' }}</dd>
                        </dl>
                        @if($po->aktif && $bisaKelola)
                            <button class="btn btn-sm btn-ghost mt-3 no-print" data-bs-toggle="modal" data-bs-target="#modalEdit"><i class="bi bi-pencil"></i> Ubah data / DP</button>
                        @endif
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-head">
                        <span class="ico"><i class="bi bi-cash-coin"></i></span>
                        <div><h6>Pembayaran</h6></div>
                    </div>
                    <div class="form-card-body">
                        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Total pesanan</span><b>Rp {{ number_format($po->total, 0, ',', '.') }}</b></div>
                        <div class="d-flex justify-content-between mb-1"><span class="text-muted">DP dibayar</span><span>Rp {{ number_format($po->dp, 0, ',', '.') }}</span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between"><span class="fw-semibold">Sisa saat diambil</span><b class="fs-5 text-potato-brown">Rp {{ number_format($po->sisa, 0, ',', '.') }}</b></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="form-card">
                    <div class="form-card-head">
                        <span class="ico"><i class="bi bi-basket3"></i></span>
                        <div><h6>Isi pesanan</h6><small>{{ $po->items->sum('qty') }} porsi</small></div>
                    </div>
                    <div class="form-card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>MENU</th><th class="text-center">JML</th><th class="text-end">HARGA</th><th class="text-end">SUBTOTAL</th></tr></thead>
                                <tbody>
                                @foreach($po->items as $it)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-potato-brown">{{ $it->nama_menu }}</div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                {{ collect([$it->ukuran, $it->saus, $it->bubuk_tabur])->filter(fn ($v) => $v && ! str_starts_with($v, 'Tanpa '))->implode(' · ') }}
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $it->qty }}</td>
                                        <td class="text-end">Rp {{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($po->aktif && $bisaKelola)
                    <div class="form-actions no-print">
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBatal"><i class="bi bi-x-octagon me-1"></i> Batalkan</button>
                        @if($po->status === 'baru')
                            <form method="POST" action="{{ route($rp . 'status', $po) }}">@csrf<input type="hidden" name="ke" value="diproses">
                                <button class="btn btn-outline-potato"><i class="bi bi-fire me-1"></i> Mulai Diproses</button></form>
                        @endif
                        @if(in_array($po->status, ['baru', 'diproses']))
                            <form method="POST" action="{{ route($rp . 'status', $po) }}">@csrf<input type="hidden" name="ke" value="siap">
                                <button class="btn btn-outline-potato"><i class="bi bi-bag-check me-1"></i> Tandai Siap Diambil</button></form>
                        @endif
                        <button class="btn btn-potato" data-bs-toggle="modal" data-bs-target="#modalSelesai"><i class="bi bi-check2-circle me-1"></i> Diambil & Lunasi</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($po->aktif && $bisaKelola)
{{-- Ubah data --}}
<div class="modal fade no-print" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route($rp . 'update', $po) }}">
            @csrf @method('PUT')
            <div class="modal-header"><h6 class="modal-title fw-bold">Ubah data pre-order</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label small fw-semibold">Nama pemesan</label>
                        <input type="text" name="nama_pelanggan" value="{{ old('nama_pelanggan', $po->nama_pelanggan) }}" required maxlength="100" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">No. WhatsApp</label>
                        <input type="text" name="no_whatsapp" value="{{ old('no_whatsapp', $po->no_whatsapp) }}" class="form-control"></div>
                    <div class="col-7"><label class="form-label small fw-semibold">Tanggal ambil</label>
                        <input type="date" name="tanggal_ambil" value="{{ old('tanggal_ambil', $po->tanggal_ambil->format('Y-m-d')) }}" required class="form-control"></div>
                    <div class="col-5"><label class="form-label small fw-semibold">Jam</label>
                        <input type="time" name="jam_ambil" value="{{ old('jam_ambil', $po->jam_ambil) }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">DP (Rp)</label>
                        <input type="number" min="0" max="{{ $po->total }}" name="dp" value="{{ old('dp', $po->dp) }}" class="form-control">
                        <div class="form-text">Total pesanan Rp {{ number_format($po->total, 0, ',', '.') }}.</div></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Catatan</label>
                        <textarea name="catatan" rows="2" maxlength="500" class="form-control">{{ old('catatan', $po->catatan) }}</textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn btn-potato btn-sm">Simpan</button></div>
        </form>
    </div>
</div>

{{-- Diambil & lunasi --}}
<div class="modal fade no-print" id="modalSelesai" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route($rp . 'selesai', $po) }}" id="formSelesai">
            @csrf
            <div class="modal-header"><h6 class="modal-title fw-bold">Pesanan diambil & dilunasi</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="p-3 mb-3 rounded" style="background: #fff8e6; border: 1px solid #f3dfae;">
                    <div class="d-flex justify-content-between small"><span>Total</span><b>Rp {{ number_format($po->total, 0, ',', '.') }}</b></div>
                    <div class="d-flex justify-content-between small"><span>DP</span><span>- Rp {{ number_format($po->dp, 0, ',', '.') }}</span></div>
                    <div class="d-flex justify-content-between mt-1"><span class="fw-semibold">Sisa yang dibayar</span><b class="fs-5" id="sisaBayar" data-sisa="{{ $po->sisa }}">Rp {{ number_format($po->sisa, 0, ',', '.') }}</b></div>
                </div>

                <label class="form-label small fw-semibold">Metode pembayaran</label>
                <div class="seg mb-3">
                    <div><input type="radio" name="metode_bayar" id="mCash" value="cash" checked><label for="mCash" class="on-gender w-100"><i class="bi bi-cash"></i>Cash</label></div>
                    <div><input type="radio" name="metode_bayar" id="mQris" value="qris"><label for="mQris" class="on-gender w-100"><i class="bi bi-qr-code"></i>QRIS</label></div>
                </div>

                <div id="blokUang">
                    <label class="form-label small fw-semibold">Uang diterima</label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">Rp</span>
                        <input type="text" inputmode="numeric" id="uangTampil" class="form-control" placeholder="0" autocomplete="off">
                        <input type="hidden" name="uang_diterima" id="uangAsli">
                    </div>
                    <div class="d-flex justify-content-between mt-2"><span class="small" id="labelKembali">Kembalian</span><b id="nilaiKembali">Rp 0</b></div>
                </div>
                <div class="small text-muted mt-3">Setelah ini pesanan otomatis tercatat sebagai transaksi, stok menu dan bumbu berkurang.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Kembali</button><button class="btn btn-potato btn-sm" id="btnSelesai">Selesaikan</button></div>
        </form>
    </div>
</div>

{{-- Batalkan --}}
<div class="modal fade no-print" id="modalBatal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route($rp . 'batal', $po) }}">
            @csrf
            <div class="modal-header"><h6 class="modal-title fw-bold">Batalkan {{ $po->kode_preorder }}?</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Stok tidak berubah karena belum dipotong. @if($po->dp > 0) DP Rp {{ number_format($po->dp, 0, ',', '.') }} perlu diurus dengan pelanggan. @endif</p>
                <label class="form-label small fw-semibold">Alasan pembatalan <span class="text-danger">*</span></label>
                <textarea name="alasan" rows="3" maxlength="150" required class="form-control" placeholder="Contoh: pelanggan membatalkan"></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Kembali</button><button class="btn btn-danger btn-sm">Ya, batalkan</button></div>
        </form>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    @media print {
        body * { visibility: hidden !important; }
        .form-page, .form-page * { visibility: visible !important; }
        .form-page { position: absolute; left: 0; top: 0; width: 100%; max-width: none !important; }
        .no-print { display: none !important; }
    }
</style>
@endpush

@push('scripts')
@if($po->aktif && $bisaKelola)
<script>
    (function () {
        const sisa = parseInt(document.getElementById('sisaBayar').dataset.sisa) || 0;
        const tampil = document.getElementById('uangTampil'), asli = document.getElementById('uangAsli');
        const blok = document.getElementById('blokUang'), btn = document.getElementById('btnSelesai');
        const rp = n => 'Rp ' + Number(n).toLocaleString('id-ID');

        function hitung() {
            const cash = document.getElementById('mCash').checked;
            blok.classList.toggle('d-none', !cash || sisa === 0);
            const uang = parseInt(String(tampil.value).replace(/\D/g, '')) || 0;
            asli.value = cash ? uang : '';
            const selisih = uang - sisa;
            document.getElementById('labelKembali').textContent = selisih < 0 && uang > 0 ? 'Uang kurang' : 'Kembalian';
            document.getElementById('nilaiKembali').textContent = rp(uang > 0 ? Math.abs(selisih) : 0);
            btn.disabled = cash && sisa > 0 && selisih < 0;
        }
        tampil.addEventListener('input', () => {
            const n = parseInt(String(tampil.value).replace(/\D/g, '')) || 0;
            tampil.value = n ? n.toLocaleString('id-ID') : '';
            hitung();
        });
        document.querySelectorAll('input[name="metode_bayar"]').forEach(r => r.addEventListener('change', hitung));
        hitung();
    })();
</script>
@endif
@endpush
