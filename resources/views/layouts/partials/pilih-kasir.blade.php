{{-- Pilih kasir yang stoknya dikelola. Param: $kasirs, $kasirTerpilih --}}
@if ($kasirs->isEmpty())
    <div class="alert alert-warning">Belum ada kasir aktif. Tambahkan kasir dulu di menu Data Kasir, karena stok dikelola per kasir.</div>
@else
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 p-3 bg-white"
        style="border-radius: 16px; border: 1px solid #efe8e0; box-shadow: 0 2px 8px rgba(61, 35, 20, .04);">
        <i class="bi bi-person-badge text-muted"></i>
        <span class="small fw-semibold" style="color: #3d2314;">Stok milik kasir:</span>
        <form method="GET" class="m-0">
            @foreach (request()->except('kasir', 'page') as $k => $v)
                @if (is_scalar($v))
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <select name="kasir" class="form-select form-select-sm" style="min-width: 220px;" onchange="this.form.submit()">
                @foreach ($kasirs as $k)
                    <option value="{{ $k->id }}" @selected($kasirTerpilih && $kasirTerpilih->id === $k->id)>{{ $k->name }}</option>
                @endforeach
            </select>
        </form>
        <span class="small text-muted">Stok, tambah stok, dan tanggal exp di halaman ini khusus untuk kasir yang dipilih.</span>
    </div>
@endif
