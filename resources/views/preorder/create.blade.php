@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.kasir')

@section('title', 'Buat Pre-Order - Potato Curly Crunch')

@section('content')
@php
    $menuJs = $menus->map(function ($m) {
        return [
            'id' => $m->id,
            'nama' => $m->nama_menu,
            'harga' => (int) $m->harga,
            'sausBawaan' => optional($m->sausBawaan)->nama,
            'bubukBawaan' => optional($m->bubukBawaan)->nama,
        ];
    })->values();
@endphp
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">
    <div class="form-page" style="max-width: 1180px;">
        <div class="page-head">
            <div>
                <div class="crumb"><a href="{{ route($rp . 'index') }}">Pre-Order</a> <i class="bi bi-chevron-right"></i> <span>Buat</span></div>
                <h4 class="mb-0">Buat pre-order</h4>
                <p>Catat pesanan yang diambil nanti. Stok baru berkurang saat pesanan diambil dan dilunasi.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-1"></i> Ada isian yang belum benar:
                <ul class="mb-0 mt-1 small">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route($rp . 'store') }}" id="formPO">
            @csrf
            <div class="row g-4">
                {{-- Kiri: pelanggan & jadwal --}}
                <div class="col-12 col-lg-5">
                    <div class="form-card">
                        <div class="form-card-head">
                            <span class="ico"><i class="bi bi-person-lines-fill"></i></span>
                            <div><h6>Data pelanggan</h6><small>Dari chat WhatsApp pemesan.</small></div>
                        </div>
                        <div class="form-card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nama pemesan <span class="req">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="nama_pelanggan" value="{{ old('nama_pelanggan') }}" class="form-control @error('nama_pelanggan') is-invalid @enderror" placeholder="Contoh: Bu Rina">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">No. WhatsApp <span class="text-muted fw-normal">(opsional)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                        <input type="text" name="no_whatsapp" value="{{ old('no_whatsapp') }}" inputmode="tel" class="form-control @error('no_whatsapp') is-invalid @enderror" placeholder="08123456789">
                                    </div>
                                </div>
                                <div class="col-7">
                                    <label class="form-label">Tanggal ambil <span class="req">*</span></label>
                                    <input type="date" name="tanggal_ambil" value="{{ old('tanggal_ambil', now()->addDay()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" class="form-control @error('tanggal_ambil') is-invalid @enderror">
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Jam <span class="text-muted fw-normal">(opsional)</span></label>
                                    <input type="time" name="jam_ambil" value="{{ old('jam_ambil') }}" class="form-control @error('jam_ambil') is-invalid @enderror">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                                    <textarea name="catatan" rows="2" maxlength="500" class="form-control" placeholder="Contoh: dibungkus per 10 porsi, untuk acara arisan">{{ old('catatan') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">DP / uang muka <span class="text-muted fw-normal">(opsional)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">Rp</span>
                                        <input type="number" min="0" name="dp" id="inputDp" value="{{ old('dp', 0) }}" class="form-control @error('dp') is-invalid @enderror">
                                    </div>
                                    <div class="form-text">DP dipotong dari total saat pesanan diambil.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kanan: isi pesanan --}}
                <div class="col-12 col-lg-7">
                    <div class="form-card">
                        <div class="form-card-head">
                            <span class="ico"><i class="bi bi-basket3"></i></span>
                            <div><h6>Isi pesanan</h6><small>Pilih menu, atur ukuran dan bumbu, lalu tambahkan.</small></div>
                        </div>
                        <div class="form-card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-7">
                                    <label class="form-label">Menu</label>
                                    <select id="bMenu" class="form-select">
                                        <option value="">-- Pilih menu --</option>
                                        @foreach ($menus as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama_menu }} — Rp {{ number_format($m->harga, 0, ',', '.') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Ukuran</label>
                                    <select id="bUkuran" class="form-select">
                                        @foreach ($ukuranOptions as $nama => $extra)
                                            <option value="{{ $nama }}">{{ $nama }}{{ $extra ? ' (+Rp ' . number_format($extra, 0, ',', '.') . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" id="bQty" min="1" max="1000" value="1" class="form-control">
                                </div>

                                <div class="col-12 col-md-6 d-none" id="wrapSaus">
                                    <label class="form-label" id="judulSaus">Saus</label>
                                    <div id="kunciSaus" class="opt-lock d-none"><i class="bi bi-lock-fill"></i><span id="kunciSausNama"></span><small>sudah termasuk</small></div>
                                    <select id="bSaus" class="form-select d-none"></select>
                                </div>
                                <div class="col-12 col-md-6 d-none" id="wrapBubuk">
                                    <label class="form-label" id="judulBubuk">Bumbu tabur</label>
                                    <div id="kunciBubuk" class="opt-lock d-none"><i class="bi bi-lock-fill"></i><span id="kunciBubukNama"></span><small>sudah termasuk</small></div>
                                    <select id="bBubuk" class="form-select d-none"></select>
                                </div>

                                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted">Harga: <b class="text-potato-brown" id="hargaPreview">Rp 0</b> / porsi</div>
                                    <button type="button" class="btn btn-outline-potato" id="btnTambah"><i class="bi bi-plus-circle me-1"></i> Tambahkan ke pesanan</button>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="table-responsive">
                                <table class="table align-middle mb-0" id="tabelItem">
                                    <thead><tr><th>MENU</th><th class="text-center">JML</th><th class="text-end">SUBTOTAL</th><th></th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="text-center text-muted py-4" id="kosongItem"><i class="bi bi-basket fs-3 d-block mb-1"></i>Belum ada menu di pesanan ini.</div>

                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between"><span class="text-muted">Total</span><b class="fs-5 text-potato-brown" id="totalPO">Rp 0</b></div>
                                <div class="d-flex justify-content-between small mt-1"><span class="text-muted">DP</span><span id="dpTampil">Rp 0</span></div>
                                <div class="d-flex justify-content-between mt-1"><span class="fw-semibold">Sisa dibayar saat diambil</span><b id="sisaTampil">Rp 0</b></div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <a href="{{ route($rp . 'index') }}" class="btn btn-ghost">Batal</a>
                            <button type="submit" class="btn btn-potato"><i class="bi bi-check2-circle me-1"></i> Simpan Pre-Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .opt-lock { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid #2f8f4e; background: #e6f4ea; color: #2f8f4e; border-radius: 10px; padding: 8px 13px; font-size: .84rem; font-weight: 700; }
    .opt-lock small { font-weight: 600; opacity: .8; }
</style>
@endpush

@push('scripts')
<script>
    const MENUS = @json($menuJs);
    const SAUS = @json($sausList->values());
    const BUBUK = @json($bubukList->values());
    const UKURAN = @json($ukuranOptions);
    const HARGA_SAUS = @json($hargaSausTambahan);
    const TANPA_SAUS = @json($tanpaSaus);
    const TANPA_BUBUK = @json($tanpaBubuk);
    const OLD_ITEMS = @json(old('items', []));

    const byId = id => document.getElementById(id);
    const rp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    let items = [];

    function aturan(m) {
        return {
            sausKunci: !!m.sausBawaan,
            bubukKunci: !!m.bubukBawaan,
            tambahSaus: !!m.bubukBawaan && !m.sausBawaan,
            pilihBubuk: !!m.sausBawaan && !m.bubukBawaan,
        };
    }

    function isiSelect(sel, tanpa, daftar, hargaTambah) {
        sel.innerHTML = '';
        sel.add(new Option(tanpa, tanpa));
        daftar.forEach(n => sel.add(new Option(n + (hargaTambah ? ' (+' + rp(hargaTambah) + ')' : ''), n)));
    }

    function perbaruiBuilder() {
        const m = MENUS.find(x => String(x.id) === byId('bMenu').value);
        ['wrapSaus', 'wrapBubuk'].forEach(id => byId(id).classList.add('d-none'));
        if (!m) { byId('hargaPreview').textContent = rp(0); return; }
        const a = aturan(m);

        // Saus
        byId('wrapSaus').classList.toggle('d-none', !(a.sausKunci || a.tambahSaus));
        byId('kunciSaus').classList.toggle('d-none', !a.sausKunci);
        byId('bSaus').classList.toggle('d-none', !a.tambahSaus);
        byId('kunciSausNama').textContent = m.sausBawaan || '';
        byId('judulSaus').textContent = a.tambahSaus ? 'Tambah saus (opsional, +' + rp(HARGA_SAUS) + ')' : 'Saus';
        if (a.tambahSaus) isiSelect(byId('bSaus'), TANPA_SAUS, SAUS, HARGA_SAUS);

        // Bubuk tabur
        byId('wrapBubuk').classList.toggle('d-none', !(a.bubukKunci || a.pilihBubuk));
        byId('kunciBubuk').classList.toggle('d-none', !a.bubukKunci);
        byId('bBubuk').classList.toggle('d-none', !a.pilihBubuk);
        byId('kunciBubukNama').textContent = m.bubukBawaan || '';
        byId('judulBubuk').textContent = a.pilihBubuk ? 'Bumbu tabur (opsional, gratis)' : 'Bumbu tabur';
        if (a.pilihBubuk) isiSelect(byId('bBubuk'), TANPA_BUBUK, BUBUK, 0);

        hitungPreview();
    }

    function buatItem(m, ukuran, saus, bubuk, qty) {
        const a = aturan(m);
        const labelSaus = a.sausKunci ? m.sausBawaan : (a.tambahSaus ? (saus || TANPA_SAUS) : null);
        const labelBubuk = a.bubukKunci ? m.bubukBawaan : (a.pilihBubuk ? (bubuk || TANPA_BUBUK) : null);
        const extraSaus = a.tambahSaus && labelSaus !== TANPA_SAUS ? HARGA_SAUS : 0;
        const harga = m.harga + (UKURAN[ukuran] || 0) + extraSaus;
        return { menu_id: m.id, nama: m.nama, ukuran, saus: labelSaus, bubuk_tabur: labelBubuk, qty, harga };
    }

    function hitungPreview() {
        const m = MENUS.find(x => String(x.id) === byId('bMenu').value);
        if (!m) { byId('hargaPreview').textContent = rp(0); return; }
        const it = buatItem(m, byId('bUkuran').value, byId('bSaus').value, byId('bBubuk').value, 1);
        byId('hargaPreview').textContent = rp(it.harga);
    }

    function render() {
        const body = byId('tabelItem').querySelector('tbody');
        body.innerHTML = '';
        let total = 0;
        items.forEach((it, i) => {
            total += it.harga * it.qty;
            const mods = [it.ukuran, it.saus && it.saus !== TANPA_SAUS ? it.saus : null, it.bubuk_tabur && it.bubuk_tabur !== TANPA_BUBUK ? it.bubuk_tabur : null].filter(Boolean).map(esc).join(' · ');
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><div class="fw-semibold text-potato-brown">${esc(it.nama)}</div><div class="text-muted" style="font-size: 11px;">${mods}</div></td>
                <td class="text-center">${it.qty}</td>
                <td class="text-end fw-bold">${rp(it.harga * it.qty)}</td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></td>`;
            tr.querySelector('button').onclick = () => { items.splice(i, 1); render(); };
            body.appendChild(tr);
        });
        byId('kosongItem').classList.toggle('d-none', items.length > 0);
        byId('tabelItem').classList.toggle('d-none', items.length === 0);

        const dp = Math.max(0, parseInt(byId('inputDp').value) || 0);
        byId('totalPO').textContent = rp(total);
        byId('dpTampil').textContent = rp(dp);
        const sisa = total - dp;
        byId('sisaTampil').textContent = rp(Math.max(0, sisa));
        byId('sisaTampil').style.color = dp > total && total > 0 ? '#b91c1c' : '';
        byId('sisaTampil').title = dp > total && total > 0 ? 'DP lebih besar dari total!' : '';
    }

    byId('bMenu').addEventListener('change', perbaruiBuilder);
    ['bUkuran', 'bSaus', 'bBubuk'].forEach(id => byId(id).addEventListener('change', hitungPreview));
    byId('inputDp').addEventListener('input', render);

    byId('btnTambah').addEventListener('click', () => {
        const m = MENUS.find(x => String(x.id) === byId('bMenu').value);
        if (!m) { alert('Pilih menu dulu.'); return; }
        const qty = Math.max(1, parseInt(byId('bQty').value) || 1);
        items.push(buatItem(m, byId('bUkuran').value, byId('bSaus').value, byId('bBubuk').value, qty));
        byId('bQty').value = 1;
        render();
    });

    byId('formPO').addEventListener('submit', e => {
        if (!items.length) { e.preventDefault(); alert('Tambahkan minimal satu menu ke pesanan.'); return; }
        document.querySelectorAll('.item-hidden').forEach(x => x.remove());
        items.forEach((it, i) => {
            ['menu_id', 'ukuran', 'saus', 'bubuk_tabur', 'qty'].forEach(f => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.className = 'item-hidden';
                inp.name = `items[${i}][${f}]`; inp.value = it[f] ?? '';
                byId('formPO').appendChild(inp);
            });
        });
    });

    // Kalau form gagal validasi, isi pesanan yang tadi dikembalikan
    (OLD_ITEMS || []).forEach(o => {
        const m = MENUS.find(x => String(x.id) === String(o.menu_id));
        if (m && UKURAN.hasOwnProperty(o.ukuran)) items.push(buatItem(m, o.ukuran, o.saus, o.bubuk_tabur, Math.max(1, parseInt(o.qty) || 1)));
    });
    render();
</script>
@endpush
