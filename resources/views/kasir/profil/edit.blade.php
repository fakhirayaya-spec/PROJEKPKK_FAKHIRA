@extends('layouts.kasir')

@section('title', 'Profil Saya')

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-potato-brown">Profil Saya</h4>
        <p class="text-muted small mb-0">Kelola foto profil dan ganti password kamu.</p>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card-custom bg-white p-4">
                <div class="text-center mb-3">
                    <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('assets_dashboard/images/SEBIN.jpg') }}"
                        class="rounded-circle border border-3 border-warning mb-2" width="80" height="80"
                        style="object-fit: cover;">
                    <h6 class="fw-bold text-potato-brown mb-0">{{ $user->name }}</h6>
                    <div class="text-muted small">{{ $user->email }}</div>
                    <span class="badge badge-amber mt-2">Kasir</span>
                </div>

                <form method="POST" action="{{ route('kasir.profil.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/png,image/jpeg" onchange="this.form.submit()">
                    <label for="avatarInput" class="btn btn-outline-potato btn-sm w-100 mb-2" style="cursor: pointer;">
                        <i class="bi bi-camera"></i> Ganti Foto
                    </label>
                    @error('avatar') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    <div class="text-muted text-center" style="font-size: 11px;">JPG/PNG &middot; maks 2 MB</div>
                </form>

                @if($user->avatar)
                    <form method="POST" action="{{ route('kasir.profil.foto') }}" onsubmit="return confirm('Hapus foto profil?')" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-trash"></i> Hapus Foto
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card-custom bg-white p-4">
                <h6 class="fw-bold text-potato-brown mb-3">Ganti Password</h6>

                @if (session('status') === 'password-updated')
                    <div class="alert alert-success py-2 small">Password berhasil diubah.</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Baru</label>
                        <input type="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-potato">Simpan Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
