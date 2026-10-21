@extends('layouts.admin')

@section('title', 'Edit Kasir - Potato Curly Crunch')

@section('content')
<div class="p-4">
    <div class="form-page">
        <div class="page-head">
            <div>
                <div class="crumb"><a href="{{ route('admin.kasir.index') }}">Data Kasir</a> <i class="bi bi-chevron-right"></i> <span>Edit</span></div>
                <h4 class="mb-0">Edit kasir — {{ $kasir->name }}</h4>
                <p>Ubah data akun dan biodata kasir.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada isian yang belum benar, cek lagi ya.</div>
        @endif

        <form action="{{ route('admin.kasir.update', $kasir) }}" method="POST">
            @include('admin.data-kasir._form')
        </form>
    </div>
</div>
@endsection
