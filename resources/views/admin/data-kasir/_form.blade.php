@php
    $edit = isset($kasir);
    $fotoLama = $edit && $kasir->avatar ? asset('storage/' . $kasir->avatar) : null;
    $gender = old('jenis_kelamin', $edit ? $kasir->jenis_kelamin : '');
    $status = old('status', $edit ? $kasir->status : 'aktif');
    $tgl = old('tanggal_lahir', $edit ? optional($kasir->tanggal_lahir)->format('Y-m-d') : '');
@endphp

@csrf
@if ($edit) @method('PUT') @endif

<div class="row g-4">
    {{-- Kiri: foto + status --}}
    <div class="col-12 col-lg-4">
        <div class="form-card">
            <div class="avatar-up">
                <div class="ring">
                    <div class="inner">
                        <img id="previewImg" src="{{ $fotoLama }}" alt="" class="{{ $fotoLama ? '' : 'd-none' }}">
                        <i class="bi bi-person-fill {{ $fotoLama ? 'd-none' : '' }}" id="previewIcon"></i>
                    </div>
                </div>
                <div class="nama" id="previewNama">{{ old('name', $edit ? $kasir->name : 'Kasir baru') }}</div>
                <div class="text-muted small">Kasir · Potato Curly Crunch</div>
                <div class="hint"><i class="bi bi-info-circle"></i> Foto diunggah sendiri oleh kasir lewat menu <b>Profil</b>.</div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-body">
                <label class="form-label">Status akun</label>
                <div class="seg">
                    <div>
                        <input type="radio" name="status" id="stAktif" value="aktif" {{ $status === 'aktif' ? 'checked' : '' }}>
                        <label for="stAktif" class="on-aktif w-100"><i class="bi bi-check-circle"></i>Aktif</label>
                    </div>
                    <div>
                        <input type="radio" name="status" id="stOff" value="nonaktif" {{ $status === 'nonaktif' ? 'checked' : '' }}>
                        <label for="stOff" class="on-off w-100"><i class="bi bi-slash-circle"></i>Nonaktif</label>
                    </div>
                </div>
                @error('status') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                <div class="form-text mt-2">Kasir nonaktif tidak bisa login.</div>
            </div>
        </div>
    </div>

    {{-- Kanan: data --}}
    <div class="col-12 col-lg-8">
        <div class="form-card">
            <div class="form-card-head">
                <span class="ico"><i class="bi bi-shield-lock"></i></span>
                <div><h6>Akun login</h6><small>{{ $edit ? 'Password diubah lewat tombol Reset Password di daftar kasir.' : 'Dipakai kasir untuk masuk ke aplikasi.' }}</small></div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nama lengkap <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="name" id="inputNama" value="{{ old('name', $edit ? $kasir->name : '') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Siti Aminah">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email (untuk login) <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" value="{{ old('email', $edit ? $kasir->email : '') }}" class="form-control @error('email') is-invalid @enderror" placeholder="siti@gmail.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    @unless ($edit)
                        <div class="col-12 col-md-6">
                            <label class="form-label">Password <span class="req">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 6 karakter">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ulangi password <span class="req">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Ketik ulang password">
                            </div>
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-head">
                <span class="ico"><i class="bi bi-person-vcard"></i></span>
                <div><h6>Biodata</h6><small>Boleh dikosongkan.</small></div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Jenis kelamin</label>
                        <div class="seg">
                            <div>
                                <input type="radio" name="jenis_kelamin" id="jkL" value="L" {{ $gender === 'L' ? 'checked' : '' }}>
                                <label for="jkL" class="on-gender w-100"><i class="bi bi-gender-male"></i>Laki-laki</label>
                            </div>
                            <div>
                                <input type="radio" name="jenis_kelamin" id="jkP" value="P" {{ $gender === 'P' ? 'checked' : '' }}>
                                <label for="jkP" class="on-gender w-100"><i class="bi bi-gender-female"></i>Perempuan</label>
                            </div>
                        </div>
                        @error('jenis_kelamin') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Tanggal lahir</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="tanggal_lahir" value="{{ $tgl }}" max="{{ now()->subDay()->format('Y-m-d') }}" class="form-control @error('tanggal_lahir') is-invalid @enderror">
                            @error('tanggal_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" rows="3" class="form-control @error('alamat') is-invalid @enderror" placeholder="Alamat tempat tinggal kasir...">{{ old('alamat', $edit ? $kasir->alamat : '') }}</textarea>
                        @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.kasir.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-potato"><i class="bi bi-check2-circle me-1"></i>{{ $edit ? 'Simpan perubahan' : 'Tambah kasir' }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('inputNama').addEventListener('input', function () {
        document.getElementById('previewNama').textContent = this.value.trim() || 'Kasir baru';
    });
</script>
