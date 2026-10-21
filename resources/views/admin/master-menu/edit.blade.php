@extends('layouts.admin')

@section('title', 'Edit Menu - Potato Curly Crunch')

@section('content')
<div class="p-4">
    <div class="form-page">
        <div class="page-head">
            <div>
                <div class="crumb"><a href="{{ route('admin.menu.index') }}">Master Menu</a> <i class="bi bi-chevron-right"></i> <span>Edit</span></div>
                <h4 class="mb-0">Edit menu — {{ $menu->nama_menu }}</h4>
                <p>Perbarui detail menu. Stok diatur lewat Manajemen Stok.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada isian yang belum benar, cek lagi ya.</div>
        @endif

        <form action="{{ route('admin.menu.update', $menu) }}" method="POST" enctype="multipart/form-data">
            @include('admin.master-menu._form')
        </form>
    </div>
</div>
@endsection
