@extends('layouts.admin')

@section('title', 'Manajemen Bumbu - Potato Curly Crunch')

@push('styles')
    <style>
        .rak-head { display: flex; align-items: center; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--line); }
        .rak-head .ico { width: 42px; height: 42px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .rak-head.tabur .ico { background: #fdefc4; color: #a35a00; }
        .rak-head.saus .ico { background: var(--chili-soft); color: var(--chili); }
        .rak-head h5 { margin: 0; font-weight: 800; font-size: 1.1rem; }
        .rak-head small { color: var(--ink-3); }
        .bumbu-row { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f3ece1; }
        .bumbu-row:last-child { border-bottom: 0; }
        .bumbu-row.off { opacity: .55; }
        .bumbu-row .nama { font-weight: 700; }
        .bumbu-row .angka { font-size: .78rem; color: var(--ink-3); margin-top: 6px; display: flex; justify-content: space-between; }
        .bumbu-row .angka b { color: var(--ink); }
        .kosong { padding: 34px 20px; text-align: center; color: var(--ink-3); font-size: .88rem; }
        .seg { display: inline-flex; background: #f3ece1; border-radius: 12px; padding: 4px; gap: 4px; width: 100%; }
        .seg input { display: none; }
        .seg label { flex: 1; text-align: center; padding: 9px 10px; border-radius: 9px; font-weight: 700; font-size: .85rem; cursor: pointer; color: var(--ink-2); margin: 0; }
        .seg input:checked + label { background: #fff; color: var(--ink); box-shadow: 0 1px 4px rgba(61, 35, 20, .15); }
        @media (max-width: 767.98px) { .bumbu-row { grid-template-columns: 1fr auto; } .bumbu-row .stokbox { grid-column: 1 / -1; order: 3; } }
    </style>
@endpush

@section('content')
    <div class="p-4">

        <div class="page-head">
            <div>
                <h4 class="mb-0">Manajemen Bumbu</h4>
                <p>Atur stok bumbu tabur dan saus. Pilihan di halaman kasir mengikuti daftar ini.</p>
            </div>
            <button type="button" class="btn btn-potato" onclick="bukaTambah('tabur')">
                <i class="bi bi-plus-lg me-1"></i> Tambah bumbu
            </button>
        </div>

        @if (session('sukses'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('sukses') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <b>Belum bisa disimpan:</b> {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="bi bi-droplet-half"></i></div>
                    <div><div class="stat-label">Total bumbu &amp; saus</div><div class="stat-value">{{ $total }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="bi bi-exclamation-triangle"></i></div>
                    <div><div class="stat-label">Stok menipis</div><div class="stat-value">{{ $menipis }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-x-octagon"></i></div>
                    <div><div class="stat-label">Stok habis</div><div class="stat-value">{{ $habis }}</div></div>
                </div>
            </div>
        </div>

        @php
            $rak = [
                'tabur' => ['judul' => 'Bumbu Tabur', 'ikon' => 'bi-stars', 'data' => $tabur],
                'saus' => ['judul' => 'Saus', 'ikon' => 'bi-droplet-fill', 'data' => $saus],
            ];
        @endphp

        <div class="row g-4">
            @foreach ($rak as $kode => $r)
                <div class="col-12 col-xl-6">
                    <div class="card-custom overflow-hidden h-100">
                        <div class="rak-head {{ $kode }}">
                            <div class="ico"><i class="bi {{ $r['ikon'] }}"></i></div>
                            <div class="flex-grow-1">
                                <h5>{{ $r['judul'] }}</h5>
                                <small>{{ $r['data']->count() }} pilihan</small>
                            </div>
                        </div>

                        @forelse ($r['data'] as $b)
                            <div class="bumbu-row {{ $b->status === 'nonaktif' ? 'off' : '' }}">
                                <div>
                                    <div class="nama">{{ $b->nama }}</div>
                                    <span class="badge-soft {{ ['aman' => 'green', 'menipis' => 'amber', 'habis' => 'red'][$b->status_stok] }}">
                                        {{ $b->status === 'nonaktif' ? 'Nonaktif' : ucfirst($b->status_stok) }}
                                    </span>
                                </div>

                                <div class="stokbox">
                                    <div class="stok-bar {{ $b->status_stok }}"><span style="width: {{ $b->persen_stok }}%"></span></div>
                                    <div class="angka">
                                        <span>Sisa <b>{{ number_format($b->stok, 0, ',', '.') }}</b> {{ $b->satuan }}</span>
                                        <span>min. {{ $b->stok_minimum }}</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-potato"
                                        onclick="bukaStok({{ $b->id }}, @js($b->nama), {{ $b->stok }}, @js($b->satuan))">
                                        <i class="bi bi-plus-lg"></i> Stok
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" aria-label="Aksi lainnya">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end pop">
                                            <button type="button" class="dropdown-item"
                                                onclick="bukaEdit(@js($b->only(['id', 'nama', 'stok', 'stok_minimum', 'satuan'])))">
                                                <i class="bi bi-pencil"></i> Ubah data
                                            </button>
                                            <form method="POST" action="{{ route('admin.bumbu.toggle', $b) }}">
                                                @csrf @method('PATCH')
                                                <button class="dropdown-item">
                                                    <i class="bi {{ $b->status === 'aktif' ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                                    {{ $b->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <hr class="my-1 opacity-25">
                                            <form method="POST" action="{{ route('admin.bumbu.destroy', $b) }}"
                                                onsubmit="return confirm(@js('Hapus ' . $b->nama . '? Riwayat transaksi lama tetap aman.'))">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item danger"><i class="bi bi-trash3"></i> Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="kosong">
                                Belum ada {{ strtolower($r['judul']) }}. Pakai tombol "Tambah bumbu" di kanan atas.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Modal tambah bumbu --}}
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.bumbu.store') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Tambah bumbu</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="seg mb-3">
                        <input type="radio" name="jenis" id="jenisTabur" value="tabur" checked>
                        <label for="jenisTabur">Bumbu tabur</label>
                        <input type="radio" name="jenis" id="jenisSaus" value="saus">
                        <label for="jenisSaus">Saus</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nama</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Bubuk BBQ atau Saus Sambal" required maxlength="100">
                        <div class="form-text">Nama ini yang tampil di pilihan kasir dan struk.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label small">Stok awal</label>
                            <input type="number" name="stok" class="form-control" value="50" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Stok minimum</label>
                            <input type="number" name="stok_minimum" class="form-control" value="20" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Satuan</label>
                            <input type="text" name="satuan" class="form-control" value="porsi" maxlength="20" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-potato btn-sm">Simpan bumbu</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal tambah stok --}}
    <div class="modal fade" id="modalStok" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form class="modal-content" method="POST" id="formStok">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="stokJudul">Tambah stok</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">Jumlah masuk (<span id="stokSatuan">porsi</span>)</label>
                    <input type="number" name="jumlah" id="stokJumlah" class="form-control form-control-lg" min="1" placeholder="Contoh: 50" required>
                    <div class="form-text">Stok sekarang: <b id="stokSekarang">0</b></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-potato btn-sm">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal ubah data --}}
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" id="formEdit">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Ubah data bumbu</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Nama</label>
                        <input type="text" name="nama" id="editNama" class="form-control" required maxlength="100">
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label small">Stok</label>
                            <input type="number" name="stok" id="editStok" class="form-control" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Stok minimum</label>
                            <input type="number" name="stok_minimum" id="editMin" class="form-control" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Satuan</label>
                            <input type="text" name="satuan" id="editSatuan" class="form-control" maxlength="20" required>
                        </div>
                    </div>
                    <div class="form-text mt-2">Mengubah angka stok di sini berarti koreksi stok (misalnya setelah hitung ulang).</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-potato btn-sm">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const urlStok = "{{ route('admin.bumbu.tambah', ['bumbu' => '__ID__']) }}";
        const urlEdit = "{{ route('admin.bumbu.update', ['bumbu' => '__ID__']) }}";
        const tampil = id => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();

        function bukaTambah(jenis) {
            document.getElementById(jenis === 'saus' ? 'jenisSaus' : 'jenisTabur').checked = true;
            tampil('modalTambah');
        }

        function bukaStok(id, nama, stok, satuan) {
            document.getElementById('formStok').action = urlStok.replace('__ID__', id);
            document.getElementById('stokJudul').textContent = 'Tambah stok ' + nama;
            document.getElementById('stokSatuan').textContent = satuan;
            document.getElementById('stokSekarang').textContent = stok;
            document.getElementById('stokJumlah').value = '';
            tampil('modalStok');
            setTimeout(() => document.getElementById('stokJumlah').focus(), 300);
        }

        function bukaEdit(b) {
            document.getElementById('formEdit').action = urlEdit.replace('__ID__', b.id);
            document.getElementById('editNama').value = b.nama;
            document.getElementById('editStok').value = b.stok;
            document.getElementById('editMin').value = b.stok_minimum;
            document.getElementById('editSatuan').value = b.satuan;
            tampil('modalEdit');
        }
    </script>
@endpush
