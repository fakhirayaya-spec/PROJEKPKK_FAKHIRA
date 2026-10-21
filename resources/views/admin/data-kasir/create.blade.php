@extends('layouts.admin')

@section('title', 'Tambah Kasir - Potato Curly Crunch')

@section('content')
<div class="p-4">
    <div class="form-page">
        <div class="page-head">
            <div>
                <div class="crumb"><a href="{{ route('admin.kasir.index') }}">Data Kasir</a> <i class="bi bi-chevron-right"></i> <span>Tambah</span></div>
                <h4 class="mb-0">Tambah akun kasir</h4>
                <p>Buat akun login baru sekaligus lengkapi biodatanya.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada isian yang belum benar, cek lagi ya.</div>
        @endif

        <form action="{{ route('admin.kasir.store') }}" method="POST">
            @include('admin.data-kasir._form')
        </form>
    </div>
</div>
@endsection
