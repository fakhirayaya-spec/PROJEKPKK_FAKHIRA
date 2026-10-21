@extends('layouts.admin')

@section('title', 'Tambah Menu - Potato Curly Crunch')

@section('content')
<div class="p-4">
    <div class="form-page">
        <div class="page-head">
            <div>
                <div class="crumb"><a href="{{ route('admin.menu.index') }}">Master Menu</a> <i class="bi bi-chevron-right"></i> <span>Tambah</span></div>
                <h4 class="mb-0">Tambah menu baru</h4>
                <p>Isi detail menu yang akan ditambahkan ke Master Menu.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada isian yang belum benar, cek lagi ya.</div>
        @endif

        <form action="{{ route('admin.menu.store') }}" method="POST" enctype="multipart/form-data">
            @include('admin.master-menu._form')
        </form>
    </div>
</div>
@endsection
