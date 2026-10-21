@extends('layouts.kasir')

@section('title', 'Transaksi')

@push('styles')
<style>
    .pos-grid { display: grid; grid-template-columns: minmax(0, 1fr) 390px; gap: 22px; align-items: start; }
    .pos-tools { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px; }
    .pos-search { position: relative; flex: 1 1 240px; }
    .pos-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--ink-3); }
    .pos-search input { padding-left: 40px; height: 44px; background: #fff; }
    .chip { border: 1px solid var(--line); background: #fff; color: var(--ink-2); border-radius: 999px; padding: 7px 14px; font-size: .82rem; font-weight: 700; cursor: pointer; }
    .chip.on { background: var(--espresso); border-color: var(--espresso); color: #ffd47d; }

    .menu-tile { background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; cursor: pointer; text-align: left; padding: 0; width: 100%; box-shadow: var(--shadow); transition: transform .12s, border-color .12s; }
    .menu-tile:hover { border-color: var(--amber); transform: translateY(-2px); }
    .menu-tile .foto { position: relative; aspect-ratio: 4 / 3; background: #f3ece1; }
    .menu-tile img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .menu-tile .sisa { position: absolute; left: 8px; top: 8px; background: rgba(35, 17, 8, .78); color: #fff; font-size: .68rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; }
    .menu-tile .sisa.tipis { background: var(--chili); }
    .menu-tile .info { padding: 10px 12px 12px; }
    .menu-tile .nama { font-weight: 700; font-size: .9rem; line-height: 1.25; color: var(--ink); }
    .menu-tile .harga { font-family: 'Bricolage Grotesque', sans-serif; font-weight: 800; color: #a86200; margin-top: 4px; }

    .cart-panel { position: sticky; top: 84px; padding: 0; overflow: hidden; }
    .cart-panel .head { padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; }
    .cart-panel .body { padding: 4px 20px; max-height: 42vh; overflow-y: auto; }
    .cart-panel .foot { padding: 16px 20px 20px; background: #fcf8f1; border-top: 1px solid var(--line); }
    .cart-empty { text-align: center; color: var(--ink-3); padding: 34px 10px; font-size: .86rem; }
    .cart-empty i { font-size: 2rem; display: block; margin-bottom: 6px; color: #d9c9b4; }
    .cart-line { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px dashed var(--line); }
    .cart-line:last-child { border-bottom: 0; }
    .cart-line .qty { width: 30px; height: 30px; border-radius: 9px; background: var(--amber-soft); color: #a35a00; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
    .cart-line .mods { font-size: .74rem; color: var(--ink-3); margin-top: 2px; }
    .cart-line .hapus { border: 0; background: none; color: var(--ink-3); font-size: .78rem; padding: 0; }
    .cart-line .hapus:hover { color: var(--chili); }
    .total-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
    .total-row b { font-family: 'Bricolage Grotesque', sans-serif; font-size: 1.7rem; font-weight: 800; }

    .pay-opts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
    .pay-opts input, .opt-group input { position: absolute; opacity: 0; pointer-events: none; }
    .pay-opts label { border: 1.5px solid var(--line); background: #fff; border-radius: 12px; padding: 10px 6px; text-align: center; font-weight: 700; font-size: .8rem; cursor: pointer; margin: 0; }
    .pay-opts label i { display: block; font-size: 1.15rem; margin-bottom: 2px; color: var(--ink-3); }
    .pay-opts input:checked + label { border-color: var(--amber); background: var(--amber-soft); }
    .pay-opts input:checked + label i { color: #a35a00; }

    .cash-box { background: #fff; border: 1.5px solid var(--line); border-radius: 14px; padding: 12px; margin-bottom: 14px; }
    .cash-box label.lbl { font-size: .78rem; font-weight: 700; color: var(--ink-2); margin-bottom: 6px; display: block; }
    .cash-box .form-control { font-weight: 800; font-size: 1.05rem; }
    .uang-cepat { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .uang-cepat button { border: 1px solid var(--line); background: #fcf8f1; border-radius: 999px; padding: 4px 11px; font-size: .74rem; font-weight: 700; color: var(--ink-2); }
    .uang-cepat button:hover { border-color: var(--amber); background: var(--amber-soft); }
    .kembalian-row { display: flex; justify-content: space-between; align-items: baseline; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line); font-size: .84rem; font-weight: 700; }
    .kembalian-row b { font-family: 'Bricolage Grotesque', sans-serif; font-size: 1.2rem; font-weight: 800; }
    .kembalian-row.ok b { color: var(--leaf, #2f8f4e); }
    .kembalian-row.kurang b { color: var(--chili, #d64545); }

    .opt-title { font-weight: 700; font-size: .84rem; margin: 16px 0 8px; color: var(--ink-2); }
    .opt-title:first-child { margin-top: 0; }
    .opt-group { display: flex; flex-wrap: wrap; gap: 8px; position: relative; }
    .opt-group label { border: 1.5px solid var(--line); border-radius: 10px; padding: 8px 13px; font-size: .84rem; font-weight: 600; cursor: pointer; margin: 0; background: #fff; }
    .opt-group label small { color: var(--ink-3); font-weight: 500; }
    .opt-group input:checked + label { border-color: var(--amber); background: var(--amber-soft); }
    .opt-group input:focus-visible + label { outline: 3px solid rgba(252, 174, 30, .55); outline-offset: 2px; }
    .opt-lock { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid var(--leaf, #2f8f4e); background: var(--leaf-soft, #e6f4ea); color: var(--leaf, #2f8f4e); border-radius: 10px; padding: 8px 13px; font-size: .84rem; font-weight: 700; }
    .opt-lock small { font-weight: 600; opacity: .8; }
    .opt-note { font-size: .74rem; color: var(--ink-3); margin-top: 6px; }
    .stepper { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 12px; overflow: hidden; }
    .stepper button { width: 42px; height: 42px; border: 0; background: #fcf8f1; font-size: 1.2rem; font-weight: 700; }
    .stepper input { width: 56px; text-align: center; border: 0; font-weight: 800; font-size: 1.05rem; height: 42px; }
    .stepper input:focus { outline: 0; }
    .modal-menu-img { width: 56px; height: 56px; border-radius: 14px; object-fit: cover; }
    .toast-lokal { position: fixed; right: 20px; bottom: 20px; z-index: 2000; background: var(--espresso); color: #fff; padding: 12px 18px; border-radius: 12px; font-size: .86rem; font-weight: 600; box-shadow: 0 12px 30px rgba(0,0,0,.3); display: none; max-width: 320px; }

    @media (max-width: 1199.98px) { .pos-grid { grid-template-columns: 1fr; } .cart-panel { position: static; } .cart-panel .body { max-height: none; } }
</style>
@endpush

@section('content')
<div class="p-4">

    <div class="page-head">
        <div>
            <h4 class="mb-0">Transaksi baru</h4>
            <p>Ketuk menu, pilih ukuran dan tambahan, lalu proses pembayaran.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="pos-grid">
        {{-- Daftar menu --}}
        <section>
            <div class="pos-tools">
                <div class="pos-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="cariMenu" class="form-control" placeholder="Cari menu..." autocomplete="off">
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-3" id="chipKategori"></div>

            <div class="row g-3" id="menuList">
                @foreach ($menus as $menu)
                    <div class="col-6 col-md-4 col-xxl-3 menu-item" data-nama="{{ strtolower($menu->nama_menu) }}" data-kategori="{{ $menu->kategori->nama_kategori ?? 'Lainnya' }}">
                        <button type="button" class="menu-tile" onclick="bukaModalMenu({{ $menu->id }})">
                            <div class="foto">
                                <img src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/spark-admin-free.png') }}" alt="" loading="lazy">
                                <span class="sisa {{ $menu->stok <= $menu->stok_minimum ? 'tipis' : '' }}">Sisa {{ $menu->stok }}</span>
                            </div>
                            <div class="info">
                                <div class="nama">{{ $menu->nama_menu }}</div>
                                <div class="harga">Rp {{ number_format($menu->harga, 0, ',', '.') }}</div>
                            </div>
                        </button>
                    </div>
                @endforeach
            </div>
            <div class="cart-empty {{ $menus->count() ? 'd-none' : '' }}" id="menuKosong">
                <i class="bi bi-emoji-frown"></i>
                {{ $menus->count() ? 'Tidak ada menu yang cocok.' : 'Belum ada menu dengan stok tersedia. Minta admin menambah stok.' }}
            </div>
        </section>

        {{-- Keranjang --}}
        <aside class="card-custom cart-panel">
            <div class="head">
                <h6 class="fw-bold mb-0">Pesanan</h6>
                <span class="badge-soft amber" id="jumlahItem">0 item</span>
            </div>
            <div class="body" id="cartBox"></div>
            <div class="foot">
                <div class="total-row"><span class="text-muted small fw-semibold">Total bayar</span><b id="cartTotal">Rp 0</b></div>

                <div class="pay-opts">
                    <div><input type="radio" name="metode" id="mCash" value="cash" checked><label for="mCash"><i class="bi bi-cash-coin"></i>Cash</label></div>
                    <div><input type="radio" name="metode" id="mQris" value="qris"><label for="mQris"><i class="bi bi-qr-code"></i>QRIS</label></div>
                    <div><input type="radio" name="metode" id="mPay" value="paylater"><label for="mPay"><i class="bi bi-hourglass-split"></i>PayLater</label></div>
                </div>

                {{-- Uang diterima (hanya untuk Cash) --}}
                <div class="cash-box" id="blokCash">
                    <label class="lbl" for="uangDiterima">Uang diterima</label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">Rp</span>
                        <input type="text" inputmode="numeric" id="uangDiterima" class="form-control" placeholder="0" autocomplete="off">
                    </div>
                    <div class="uang-cepat" id="uangCepat"></div>
                    <div class="kembalian-row" id="barisKembalian"><span id="labelKembalian">Kembalian</span><b id="nilaiKembalian">Rp 0</b></div>
                </div>

                <form id="formTransaksi" method="POST" action="{{ route('kasir.transaksi.store') }}">
                    @csrf
                    <input type="hidden" name="metode_bayar" id="inputMetodeBayar" value="cash">
                    <input type="hidden" name="uang_diterima" id="inputUangDiterima" value="">
                    <div id="hiddenItemsContainer"></div>
                    <button type="submit" class="btn btn-potato w-100 py-2" id="btnBayar" disabled>
                        <i class="bi bi-check2-circle me-1"></i> Proses pembayaran
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>

{{-- Modal pilihan menu --}}
<div class="modal fade" id="modalMenu" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header align-items-center gap-3">
                <img id="modalMenuFoto" class="modal-menu-img" alt="">
                <div class="flex-grow-1">
                    <h6 class="modal-title fw-bold" id="modalMenuNama">Menu</h6>
                    <div class="small text-muted" id="modalMenuHarga"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="opt-title">Ukuran</div>
                <div class="opt-group">
                    @foreach ($ukuranOptions as $label => $extra)
                        <input type="radio" name="ukuran" id="uk{{ $loop->index }}" value="{{ $label }}" {{ $loop->first ? 'checked' : '' }}>
                        <label for="uk{{ $loop->index }}">{{ $label }} @if ($extra > 0)<small>+Rp {{ number_format($extra, 0, ',', '.') }}</small>@endif</label>
                    @endforeach
                </div>

                {{-- SAUS: terkunci (menu bersaus) / tambahan berbayar (menu berbubuk) / tidak ada (minuman) --}}
                <div id="blokSaus" class="d-none">
                    <div class="opt-title" id="judulSaus">Saus</div>
                    <div id="sausKunci" class="d-none">
                        <span class="opt-lock"><i class="bi bi-lock-fill"></i><span id="sausKunciNama"></span><small>sudah termasuk</small></span>
                    </div>
                    <div class="opt-group" id="grupSaus">
                        <input type="radio" name="saus" id="sausTanpa" value="{{ $tanpaSaus }}" checked>
                        <label for="sausTanpa">Tanpa saus</label>
                        @foreach ($sausList as $b)
                            <input type="radio" name="saus" id="saus{{ $loop->index }}" value="{{ $b->nama }}">
                            <label for="saus{{ $loop->index }}">{{ $b->nama }} <small>+Rp {{ number_format($hargaSausTambahan, 0, ',', '.') }}</small></label>
                        @endforeach
                    </div>
                </div>

                {{-- BUBUK TABUR: terkunci (menu berbubuk) / opsional gratis (menu bersaus) / tidak ada (minuman) --}}
                <div id="blokBubuk" class="d-none">
                    <div class="opt-title" id="judulBubuk">Bumbu tabur</div>
                    <div id="bubukKunci" class="d-none">
                        <span class="opt-lock"><i class="bi bi-lock-fill"></i><span id="bubukKunciNama"></span><small>sudah termasuk</small></span>
                    </div>
                    <div class="opt-group" id="grupBubuk">
                        <input type="radio" name="bubuk" id="bubukTanpa" value="{{ $tanpaBubuk }}" checked>
                        <label for="bubukTanpa">Tanpa bumbu</label>
                        @foreach ($bubukList as $b)
                            <input type="radio" name="bubuk" id="bubuk{{ $loop->index }}" value="{{ $b->nama }}">
                            <label for="bubuk{{ $loop->index }}">{{ $b->nama }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="opt-title">Jumlah</div>
                <div class="stepper">
                    <button type="button" onclick="ubahQty(-1)" aria-label="Kurangi">−</button>
                    <input type="number" id="pilihQty" value="1" min="1" aria-label="Jumlah">
                    <button type="button" onclick="ubahQty(1)" aria-label="Tambah">+</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-potato" onclick="tambahKeKeranjang()">Masukkan ke pesanan</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-lokal" id="toastLokal" role="status"></div>
@endsection

@push('scripts')
@php
    $menuJs = $menus->map(function ($m) {
        return [
            'id' => $m->id,
            'nama' => $m->nama_menu,
            'harga' => $m->harga,
            'stok' => $m->stok,
            'sausBawaan' => optional($m->sausBawaan)->nama,
            'bubukBawaan' => optional($m->bubukBawaan)->nama,
            'foto' => $m->gambar ? asset('storage/' . $m->gambar) : asset('assets_dashboard/images/spark-admin-free.png'),
        ];
    })->values();
    $stokBumbuJs = $stokBumbu;
@endphp
<script>
    const daftarMenu = @json($menuJs);
    const ukuranExtra = @json($ukuranOptions);
    const stokBumbu = @json($stokBumbuJs);
    const TANPA = @json([$tanpaSaus, $tanpaBubuk]);
    const HARGA_SAUS_TAMBAHAN = @json($hargaSausTambahan);
    const rp = n => 'Rp ' + n.toLocaleString('id-ID');

    let keranjang = [];
    let totalKeranjang = 0;
    let menuDipilihId = null;

    /* ---------- Cari & filter kategori ---------- */
    const kategoriList = [...new Set([...document.querySelectorAll('.menu-item')].map(e => e.dataset.kategori))];
    let kategoriAktif = 'Semua';
    const chipBox = document.getElementById('chipKategori');
    if (kategoriList.length > 1) {
        ['Semua', ...kategoriList].forEach(k => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'chip' + (k === 'Semua' ? ' on' : ''); b.textContent = k;
            b.onclick = () => { kategoriAktif = k; chipBox.querySelectorAll('.chip').forEach(c => c.classList.toggle('on', c === b)); saring(); };
            chipBox.appendChild(b);
        });
    }
    function saring() {
        const q = document.getElementById('cariMenu').value.trim().toLowerCase();
        let tampil = 0;
        document.querySelectorAll('.menu-item').forEach(e => {
            const cocok = e.dataset.nama.includes(q) && (kategoriAktif === 'Semua' || e.dataset.kategori === kategoriAktif);
            e.classList.toggle('d-none', !cocok);
            if (cocok) tampil++;
        });
        document.getElementById('menuKosong').classList.toggle('d-none', tampil > 0 || !daftarMenu.length);
    }
    document.getElementById('cariMenu').addEventListener('input', saring);

    /* ---------- Modal ---------- */
    const nilai = name => document.querySelector(`input[name="${name}"]:checked`).value;
    function toast(pesan) {
        const t = document.getElementById('toastLokal');
        t.textContent = pesan; t.style.display = 'block';
        clearTimeout(t._h); t._h = setTimeout(() => t.style.display = 'none', 3200);
    }
    function ubahQty(d) {
        const el = document.getElementById('pilihQty');
        el.value = Math.max(1, (parseInt(el.value) || 1) + d);
    }
    const tampil = (id, ya) => document.getElementById(id).classList.toggle('d-none', !ya);

    // Aturan bumbu per menu:
    //  - menu bersaus  -> saus terkunci, bubuk tabur opsional & gratis
    //  - menu berbubuk -> bubuk terkunci, saus tambahan opsional +Rp 3.000
    //  - lainnya (minuman) -> tidak ada pilihan bumbu
    function aturanMenu(menu) {
        const sausKunci = !!menu.sausBawaan, bubukKunci = !!menu.bubukBawaan;
        return {
            sausKunci, bubukKunci,
            bisaTambahSaus: bubukKunci && !sausKunci,
            bisaPilihBubuk: sausKunci && !bubukKunci,
        };
    }

    function hitungHargaModal() {
        const menu = daftarMenu.find(m => m.id === menuDipilihId);
        if (!menu) return;
        const a = aturanMenu(menu);
        let harga = menu.harga + (ukuranExtra[nilai('ukuran')] || 0);
        if (a.bisaTambahSaus && nilai('saus') !== TANPA[0]) harga += HARGA_SAUS_TAMBAHAN;
        document.getElementById('modalMenuHarga').innerText = rp(harga) + ' / porsi';
    }

    function bukaModalMenu(menuId) {
        menuDipilihId = menuId;
        const menu = daftarMenu.find(m => m.id === menuId);
        const a = aturanMenu(menu);

        document.getElementById('modalMenuNama').innerText = menu.nama;
        document.getElementById('modalMenuFoto').src = menu.foto;
        document.getElementById('pilihQty').value = 1;
        ['uk0', 'sausTanpa', 'bubukTanpa'].forEach(id => { const e = document.getElementById(id); if (e) e.checked = true; });

        // Saus
        tampil('blokSaus', a.sausKunci || a.bisaTambahSaus);
        tampil('sausKunci', a.sausKunci);
        tampil('grupSaus', a.bisaTambahSaus);
        document.getElementById('sausKunciNama').innerText = menu.sausBawaan || '';
        document.getElementById('judulSaus').innerText = a.bisaTambahSaus ? 'Tambah saus (opsional, +Rp ' + HARGA_SAUS_TAMBAHAN.toLocaleString('id-ID') + ')' : 'Saus';

        // Bubuk tabur
        tampil('blokBubuk', a.bubukKunci || a.bisaPilihBubuk);
        tampil('bubukKunci', a.bubukKunci);
        tampil('grupBubuk', a.bisaPilihBubuk);
        document.getElementById('bubukKunciNama').innerText = menu.bubukBawaan || '';
        document.getElementById('judulBubuk').innerText = a.bisaPilihBubuk ? 'Bumbu tabur (opsional, gratis)' : 'Bumbu tabur';

        hitungHargaModal();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMenu')).show();
    }
    document.getElementById('modalMenu').addEventListener('change', hitungHargaModal);

    /* ---------- Cek pemakaian stok di keranjang ---------- */
    const dipakaiMenu = id => keranjang.filter(i => i.menu_id === id).reduce((a, i) => a + i.qty, 0);
    const dipakaiBumbu = nama => keranjang.reduce((a, i) => a + ((i.saus === nama ? i.qty : 0) + (i.bubuk_tabur === nama ? i.qty : 0)), 0);

    function tambahKeKeranjang() {
        const menu = daftarMenu.find(m => m.id === menuDipilihId);
        const a = aturanMenu(menu);
        const ukuran = nilai('ukuran');
        const qty = Math.max(1, parseInt(document.getElementById('pilihQty').value) || 1);

        // Saus & bubuk ditentukan aturan menu (bawaan terkunci, sisanya pilihan kasir)
        const saus = a.sausKunci ? menu.sausBawaan : (a.bisaTambahSaus ? nilai('saus') : null);
        const bubuk = a.bubukKunci ? menu.bubukBawaan : (a.bisaPilihBubuk ? nilai('bubuk') : null);
        const tambahSaus = a.bisaTambahSaus && saus !== TANPA[0];

        if (dipakaiMenu(menu.id) + qty > menu.stok) return toast(`Stok ${menu.nama} tinggal ${menu.stok}.`);
        for (const b of [saus, bubuk]) {
            if (!b || TANPA.includes(b)) continue;
            if (dipakaiBumbu(b) + qty > (stokBumbu[b] ?? 0)) return toast(`Stok ${b} tinggal ${stokBumbu[b] ?? 0}.`);
        }

        const hargaSatuan = menu.harga + (ukuranExtra[ukuran] || 0) + (tambahSaus ? HARGA_SAUS_TAMBAHAN : 0);
        keranjang.push({ menu_id: menu.id, nama: menu.nama, ukuran, saus, bubuk_tabur: bubuk, tambahSaus, qty, harga_satuan: hargaSatuan, subtotal: hargaSatuan * qty });
        renderKeranjang();
        bootstrap.Modal.getInstance(document.getElementById('modalMenu')).hide();
    }

    function hapusDariKeranjang(i) { keranjang.splice(i, 1); renderKeranjang(); }

    function renderKeranjang() {
        const box = document.getElementById('cartBox');
        const btn = document.getElementById('btnBayar');
        const totalItem = keranjang.reduce((a, i) => a + i.qty, 0);
        document.getElementById('jumlahItem').textContent = totalItem + ' item';

        if (!keranjang.length) {
            box.innerHTML = '<div class="cart-empty"><i class="bi bi-bag"></i>Pesanan masih kosong.<br>Ketuk menu di sebelah kiri.</div>';
            document.getElementById('cartTotal').innerText = 'Rp 0';
            totalKeranjang = 0;
            perbaruiBayar();
            return;
        }

        const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        let total = 0, html = '';
        keranjang.forEach((it, i) => {
            total += it.subtotal;
            const sausTeks = !it.saus || TANPA.includes(it.saus) ? null : (it.tambahSaus ? 'Tambah ' + it.saus + ' (+' + rp(HARGA_SAUS_TAMBAHAN) + ')' : it.saus);
            const mods = [it.ukuran, sausTeks, !it.bubuk_tabur || TANPA.includes(it.bubuk_tabur) ? null : it.bubuk_tabur].filter(Boolean).map(esc).join(' · ');
            html += `<div class="cart-line">
                <div class="qty">${it.qty}x</div>
                <div class="flex-grow-1">
                    <div class="fw-bold small">${esc(it.nama)}</div>
                    <div class="mods">${mods}</div>
                    <button type="button" class="hapus" onclick="hapusDariKeranjang(${i})"><i class="bi bi-x-circle"></i> Hapus</button>
                </div>
                <div class="fw-bold small text-nowrap">${rp(it.subtotal)}</div>
            </div>`;
        });
        box.innerHTML = html;
        document.getElementById('cartTotal').innerText = rp(total);
        totalKeranjang = total;
        perbaruiBayar();
    }
    /* ---------- Pembayaran cash & kembalian ---------- */
    const angka = v => parseInt(String(v).replace(/\D/g, '')) || 0;
    const inputUang = document.getElementById('uangDiterima');

    function pilihanUangCepat(total) {
        const opsi = [total];
        [10000, 20000, 50000, 100000].forEach(n => { if (n >= total && !opsi.includes(n)) opsi.push(n); });
        if (opsi.length === 1) opsi.push(Math.ceil(total / 100000) * 100000);
        return opsi.slice(0, 4);
    }

    function perbaruiBayar() {
        const btn = document.getElementById('btnBayar');
        const cash = nilai('metode') === 'cash';
        document.getElementById('blokCash').classList.toggle('d-none', !cash || !keranjang.length);

        if (!keranjang.length) { btn.disabled = true; return; }
        if (!cash) { btn.disabled = false; return; }

        // tombol nominal cepat
        const cepat = document.getElementById('uangCepat');
        cepat.innerHTML = '';
        pilihanUangCepat(totalKeranjang).forEach((n, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = i === 0 ? 'Uang pas' : rp(n);
            b.onclick = () => { inputUang.value = n.toLocaleString('id-ID'); perbaruiBayar(); };
            cepat.appendChild(b);
        });

        const selisih = angka(inputUang.value) - totalKeranjang;
        const baris = document.getElementById('barisKembalian');
        baris.classList.toggle('ok', selisih >= 0 && angka(inputUang.value) > 0);
        baris.classList.toggle('kurang', selisih < 0 && angka(inputUang.value) > 0);
        document.getElementById('labelKembalian').textContent = selisih < 0 && angka(inputUang.value) > 0 ? 'Uang kurang' : 'Kembalian';
        document.getElementById('nilaiKembalian').textContent = rp(Math.abs(angka(inputUang.value) > 0 ? selisih : 0));
        btn.disabled = selisih < 0;
    }
    inputUang.addEventListener('input', () => {
        const n = angka(inputUang.value);
        inputUang.value = n ? n.toLocaleString('id-ID') : '';
        perbaruiBayar();
    });
    document.querySelectorAll('input[name="metode"]').forEach(r => r.addEventListener('change', perbaruiBayar));

    renderKeranjang();

    /* ---------- Kirim ---------- */
    document.getElementById('formTransaksi').addEventListener('submit', function () {
        document.getElementById('inputMetodeBayar').value = nilai('metode');
        document.getElementById('inputUangDiterima').value = nilai('metode') === 'cash' ? angka(inputUang.value) : '';
        const container = document.getElementById('hiddenItemsContainer');
        container.innerHTML = '';
        keranjang.forEach((item, i) => {
            ['menu_id', 'ukuran', 'saus', 'bubuk_tabur', 'qty'].forEach(field => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = `items[${i}][${field}]`; input.value = item[field] ?? '';
                container.appendChild(input);
            });
        });
    });
</script>
@endpush
