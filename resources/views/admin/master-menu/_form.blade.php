@php
    $edit = isset($menu);
    $gambarLama = $edit && $menu->gambar ? asset('storage/' . $menu->gambar) : null;
    $status = old('status', $edit ? $menu->status : 'aktif');
@endphp

@csrf
@if ($edit) @method('PUT') @endif

<div class="row g-4">
    {{-- Kiri: gambar --}}
    <div class="col-12 col-lg-4">
        <div class="form-card">
            <div class="form-card-head">
                <span class="ico"><i class="bi bi-image"></i></span>
                <div><h6>Foto menu</h6><small>Tampil di halaman transaksi kasir.</small></div>
            </div>
            <div class="form-card-body">
                <div class="dropzone-img" id="dropzone">
                    <img id="previewGambar" src="{{ $gambarLama }}" alt="" class="{{ $gambarLama ? '' : 'd-none' }}">
                    <div class="ph {{ $gambarLama ? 'd-none' : '' }}" id="placeholderGambar">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <b>Klik atau tarik foto ke sini</b>
                        <small>JPG / PNG, maks 2 MB</small>
                    </div>
                    <span class="ganti {{ $gambarLama ? '' : 'd-none' }}" id="labelGanti"><i class="bi bi-arrow-repeat me-1"></i>Klik untuk ganti foto</span>
                    <input type="file" name="gambar" id="inputGambar" accept="image/*">
                </div>
                @error('gambar') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- Kanan: detail --}}
    <div class="col-12 col-lg-8">
        <div class="form-card">
            <div class="form-card-head">
                <span class="ico"><i class="bi bi-cup-straw"></i></span>
                <div><h6>Detail menu</h6><small>{{ $edit ? 'Kode menu: ' . $menu->kode_menu : 'Kode menu dibuat otomatis.' }}</small></div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama menu <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <input type="text" name="nama_menu" value="{{ old('nama_menu', $menu->nama_menu ?? '') }}" class="form-control @error('nama_menu') is-invalid @enderror" placeholder="Contoh: Curly BBQ">
                            @error('nama_menu') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Kategori <span class="req">*</span></label>
                        <select name="kategori_menu_id" class="form-select @error('kategori_menu_id') is-invalid @enderror">
                            <option value="">-- Pilih kategori --</option>
                            @foreach ($kategoris as $k)
                                <option value="{{ $k->id }}" {{ old('kategori_menu_id', $menu->kategori_menu_id ?? '') == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                            @endforeach
                        </select>
                        @error('kategori_menu_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($kategoris->isEmpty())
                            <div class="form-text text-danger">Belum ada kategori. Tambahkan lewat "Kelola Kategori" di halaman daftar menu dulu.</div>
                        @endif
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Harga jual <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="number" name="harga" id="inputHarga" min="0" value="{{ old('harga', $menu->harga ?? '') }}" class="form-control @error('harga') is-invalid @enderror" placeholder="15000">
                            @error('harga') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Harga modal / HPP <span class="text-muted fw-normal">(per porsi)</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="number" name="harga_modal" id="inputModal" min="0" value="{{ old('harga_modal', $menu->harga_modal ?? 0) }}" class="form-control @error('harga_modal') is-invalid @enderror" placeholder="8000">
                            @error('harga_modal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text" id="infoLaba">Biaya bahan + kemasan untuk 1 porsi. Dipakai untuk hitung laba di Laporan.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Stok {{ $edit ? '' : 'awal' }}</label>
                        @if ($edit)
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                <input type="text" class="form-control" value="{{ $menu->stok }} pcs" disabled>
                            </div>
                            <div class="form-text">Stok diubah lewat <b>Manajemen Stok</b> supaya tercatat di riwayat.</div>
                        @else
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                <input type="number" name="stok" min="0" value="{{ old('stok', 0) }}" class="form-control @error('stok') is-invalid @enderror">
                                @error('stok') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Stok minimum <span class="text-muted fw-normal">(batas "menipis")</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                            <input type="number" name="stok_minimum" min="0" value="{{ old('stok_minimum', $menu->stok_minimum ?? 5) }}" class="form-control @error('stok_minimum') is-invalid @enderror">
                            @error('stok_minimum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Saus bawaan <span class="text-muted fw-normal">(opsional)</span></label>
                        <select name="saus_bawaan_id" class="form-select @error('saus_bawaan_id') is-invalid @enderror">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($sausList as $b)
                                <option value="{{ $b->id }}" {{ old('saus_bawaan_id', $menu->saus_bawaan_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->nama }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Kalau diisi, di kasir saus ini terkunci. Bubuk tabur jadi pilihan gratis.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Bubuk tabur bawaan <span class="text-muted fw-normal">(opsional)</span></label>
                        <select name="bubuk_bawaan_id" class="form-select @error('bubuk_bawaan_id') is-invalid @enderror">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($taburList as $b)
                                <option value="{{ $b->id }}" {{ old('bubuk_bawaan_id', $menu->bubuk_bawaan_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->nama }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Kalau diisi, di kasir bubuk ini terkunci. Tambahan saus +Rp 3.000.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Status menu</label>
                        <div class="seg" style="max-width: 360px;">
                            <div>
                                <input type="radio" name="status" id="mnAktif" value="aktif" {{ $status === 'aktif' ? 'checked' : '' }}>
                                <label for="mnAktif" class="on-aktif w-100"><i class="bi bi-check-circle"></i>Aktif</label>
                            </div>
                            <div>
                                <input type="radio" name="status" id="mnOff" value="nonaktif" {{ $status === 'nonaktif' ? 'checked' : '' }}>
                                <label for="mnOff" class="on-off w-100"><i class="bi bi-slash-circle"></i>Nonaktif</label>
                            </div>
                        </div>
                        @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <div class="form-text mt-1">Menu nonaktif tidak muncul di halaman transaksi kasir.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="deskripsi" rows="3" class="form-control @error('deskripsi') is-invalid @enderror" placeholder="Deskripsi singkat menu...">{{ old('deskripsi', $menu->deskripsi ?? '') }}</textarea>
                        @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.menu.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-potato"><i class="bi bi-check2-circle me-1"></i>{{ $edit ? 'Simpan perubahan' : 'Tambah menu' }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const h = document.getElementById('inputHarga'), m = document.getElementById('inputModal'), info = document.getElementById('infoLaba');
        const hitung = () => {
            const jual = parseInt(h.value) || 0, modal = parseInt(m.value) || 0;
            if (!modal) return;
            const laba = jual - modal;
            info.innerHTML = 'Laba per porsi: <b class="' + (laba >= 0 ? 'text-success' : 'text-danger') + '">Rp ' + laba.toLocaleString('id-ID') + '</b>' + (jual ? ' (' + Math.round(laba / jual * 100) + '%)' : '');
        };
        h.addEventListener('input', hitung); m.addEventListener('input', hitung); hitung();
    })();
    (function () {
        const input = document.getElementById('inputGambar');
        const zone = document.getElementById('dropzone');
        input.addEventListener('change', function () {
            const f = this.files && this.files[0];
            if (!f) return;
            const r = new FileReader();
            r.onload = e => {
                const img = document.getElementById('previewGambar');
                img.src = e.target.result; img.classList.remove('d-none');
                document.getElementById('placeholderGambar').classList.add('d-none');
                document.getElementById('labelGanti').classList.remove('d-none');
            };
            r.readAsDataURL(f);
        });
        ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, () => zone.classList.add('drag')));
        ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('drag')));
    })();
</script>
