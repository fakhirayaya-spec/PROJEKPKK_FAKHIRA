{{-- Form cari & filter. Variabel: $tipe, $kasirs (opsional, hanya admin), $tabs (bool, hanya kasir) --}}
<form method="GET" class="row g-2 align-items-center">
    @if (! empty($tabs))
        <input type="hidden" name="tipe" value="{{ $tipe }}">
    @endif
    <div class="col-12 {{ isset($kasirs) ? 'col-md-3' : 'col-md-4' }}">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
            placeholder="Cari {{ $tipe === 'menu' ? 'menu' : 'bumbu' }}{{ isset($kasirs) ? ', kasir' : '' }}, atau keterangan...">
    </div>
    @isset($kasirs)
        <div class="col-6 col-md-2">
            <select name="kasir_id" class="form-select form-select-sm">
                <option value="">Semua kasir</option>
                @foreach ($kasirs as $k)
                    <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="col-6 col-md-2">
        <select name="jenis" class="form-select form-select-sm">
            <option value="">Masuk & keluar</option>
            <option value="masuk" @selected(request('jenis') === 'masuk')>Masuk</option>
            <option value="keluar" @selected(request('jenis') === 'keluar')>Keluar</option>
        </select>
    </div>
    <div class="col-6 col-md-2"><input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm" title="Dari tanggal"></div>
    <div class="col-6 col-md-2"><input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm" title="Sampai tanggal"></div>
    <div class="col-12 col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-sm w-100 text-white" style="background:#3d2314;"><i class="bi bi-search"></i></button>
    </div>
</form>
