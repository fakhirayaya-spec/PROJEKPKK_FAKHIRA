{{-- Param: $bahan (null = tambah baru), $dasarOptions --}}
@php $b = $bahan ?? null; @endphp
<div class="row g-3">
    <div class="col-12 col-md-8">
        <label class="form-label small fw-semibold">Nama bahan</label>
        <input type="text" name="nama" class="form-control" maxlength="60" value="{{ $b->nama ?? '' }}"
            placeholder="Contoh: Plastik kresek" required>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">Satuan stok</label>
        <input type="text" name="satuan" class="form-control" maxlength="20" value="{{ $b->satuan ?? '' }}"
            placeholder="pcs / set / order" required>
    </div>

    <div class="col-6 col-md-4">
        <label class="form-label small fw-semibold">Stok awal tiap kasir</label>
        <input type="number" name="stok_awal" class="form-control" min="0" value="{{ $b->stok_awal ?? 100 }}" required>
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label small fw-semibold">Batas menipis</label>
        <input type="number" name="stok_menipis" class="form-control" min="0" value="{{ $b->stok_menipis ?? 10 }}" required>
    </div>
    <div class="col-12 col-md-4 d-flex align-items-end">
        <div class="form-text mb-2">Stok sama atau di bawah batas ini muncul di lonceng.</div>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label small fw-semibold">Berkurang berdasarkan</label>
        <select name="dasar_pakai" class="form-select" required>
            @foreach ($dasarOptions as $val => $label)
                <option value="{{ $val }}" @selected(($b->dasar_pakai ?? 'kentang') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label small fw-semibold">Berkurang 1 untuk tiap ... (kelipatan)</label>
        <input type="number" name="per_kelompok" class="form-control" min="1" value="{{ $b->per_kelompok ?? 1 }}">
        <div class="form-text">Isi 1 kalau berkurang sesuai jumlahnya. Isi 3 seperti gas: 1-3 porsi = 1, 4-6 = 2, dst. Diabaikan untuk "Tiap transaksi".</div>
    </div>

    <div class="col-6 col-md-6">
        <label class="form-label small fw-semibold">Satuan isi ulang</label>
        <input type="text" name="unit_isi" class="form-control" maxlength="20" value="{{ $b->unit_isi ?? '' }}"
            placeholder="tabung / dus / pcs" required>
    </div>
    <div class="col-6 col-md-6">
        <label class="form-label small fw-semibold">1 satuan isi ulang = berapa stok</label>
        <input type="number" name="isi_per_unit" class="form-control" min="1" value="{{ $b->isi_per_unit ?? 1 }}" required>
        <div class="form-text">Contoh gas: 1 tabung = 50 order.</div>
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Harga beli per satuan isi ulang (Rp)</label>
        <input type="number" name="harga_per_unit" class="form-control" min="0" value="{{ $b->harga_per_unit ?? 0 }}">
        <div class="form-text">Dipakai mengisi otomatis kolom biaya saat isi ulang (contoh: 1 tabung gas = Rp 22.000). Isi 0 kalau biaya mau diketik manual tiap isi ulang.</div>
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Dicatat di Pengeluaran sebagai</label>
        <select name="kategori_pengeluaran" class="form-select">
            @foreach (\App\Models\BahanOperasional::KATEGORI_PENGELUARAN as $val => $label)
                <option value="{{ $val }}" @selected(($b->kategori_pengeluaran ?? 'operasional') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">Biaya stok awal dan tiap isi ulang bahan ini masuk ke kategori ini di halaman Pengeluaran. Catatan: kategori Bahan Baku tidak ikut mengurangi Keuntungan Bersih (dianggap sudah termasuk Harga Modal menu).</div>
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Gambar (opsional)</label>
        @if ($b && $b->gambar_url)
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="so-thumb"><img src="{{ $b->gambar_url }}" alt="{{ $b->nama }}" onerror="soGagal(this)"></div>
                <label class="small text-muted mb-0">
                    <input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar ini
                </label>
            </div>
        @endif
        <input type="file" name="gambar" class="form-control" accept="image/png,image/jpeg,image/webp">
        <div class="form-text">JPG, PNG, atau WEBP, maksimal 5 MB. Kalau kosong, dipakai ikon bawaan.</div>
    </div>
</div>
