@extends('layouts.admin')

@section('title', 'Laporan - Potato Curly Crunch')

@section('content')
@php
    $rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $labelKategori = ['bahan_baku' => 'Bahan Baku', 'operasional' => 'Operasional', 'lainnya' => 'Lainnya'];
    $warnaStatus = ['aman' => 'green', 'menipis' => 'amber', 'habis' => 'red'];
    $labelStatus = ['aman' => 'Aman', 'menipis' => 'Menipis', 'habis' => 'Habis'];
    $labelMetode = ['cash' => 'Tunai', 'qris' => 'QRIS', 'paylater' => 'Paylater'];
    $totalHarian = [
        'transaksi' => $rincianHarian->sum('transaksi'),
        'item' => $rincianHarian->sum('item'),
        'penjualan' => $rincianHarian->sum('penjualan'),
        'modal' => $rincianHarian->sum('modal'),
        'laba_kotor' => $rincianHarian->sum('laba_kotor'),
        'pengeluaran' => $rincianHarian->sum('pengeluaran'),
        'bersih' => $rincianHarian->sum('bersih'),
    ];
@endphp
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-potato-brown">Laporan</h4>
            <p class="text-muted small mb-0">Laporan penjualan, keuangan, pre-order, dan stok untuk periode yang dipilih.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('admin.laporan.cetak', request()->query()) }}" target="_blank" class="btn btn-outline-potato btn-sm">
                <i class="bi bi-printer"></i> Cetak PDF
            </a>
        </div>
    </div>

    {{-- Filter periode --}}
    <div class="card-custom bg-white p-3 mb-3 no-print">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ $dari->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-potato w-100">Terapkan</button>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <a href="{{ route('admin.laporan.index', ['dari' => now()->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">Hari Ini</a>
            <a href="{{ route('admin.laporan.index', ['dari' => now()->startOfWeek()->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">Minggu Ini</a>
            <a href="{{ route('admin.laporan.index', ['dari' => now()->startOfMonth()->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">Bulan Ini</a>
            <a href="{{ route('admin.laporan.index', ['dari' => now()->subDays(29)->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">30 Hari</a>
        </div>
    </div>

    <p class="small text-muted mb-3">
        <i class="bi bi-calendar-range"></i> Periode: <b class="text-potato-brown">{{ $dari->translatedFormat('d F Y') }} &ndash; {{ $sampai->translatedFormat('d F Y') }}</b>
    </p>

    {{-- ================= RINGKASAN KEUANGAN ================= --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-label">Total Penjualan</div>
                    <div class="stat-value" style="font-size: 1.1rem;">{{ $rp($totalPenjualan) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-basket3"></i></div>
                <div>
                    <div class="stat-label">Modal (HPP)</div>
                    <div class="stat-value" style="font-size: 1.1rem;">{{ $rp($totalModal) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $labaKotor >= 0 ? 'green' : 'red' }}"><i class="bi bi-piggy-bank"></i></div>
                <div>
                    <div class="stat-label">Laba Kotor ({{ $marginPersen }}%)</div>
                    <div class="stat-value" style="font-size: 1.1rem;">{{ $rp($labaKotor) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label">Pengeluaran Operasional</div>
                    <div class="stat-value" style="font-size: 1.1rem;">{{ $rp($pengeluaranOperasional) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $keuntunganBersih >= 0 ? 'green' : 'red' }}"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="stat-label">Keuntungan Bersih ({{ $marginBersih }}%)</div>
                    <div class="stat-value" style="font-size: 1.1rem;">{{ $rp($keuntunganBersih) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon brown"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="stat-label">Jumlah Transaksi</div>
                    <div class="stat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="stat-label">Rata-rata / Transaksi</div>
                    <div class="stat-value" style="font-size: 1.1rem;">{{ $rp($rataRata) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon brown"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="stat-label">Item Terjual</div>
                    <div class="stat-value">{{ number_format($itemTerjual, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle"></i>
        Keuntungan Bersih = Laba Kotor &minus; Pengeluaran Operasional &amp; Lainnya. Pengeluaran <b>Bahan Baku</b>
        ({{ $rp($pengeluaranBahanBaku) }}) tetap dicatat tetapi tidak mengurangi laba lagi karena sudah masuk Modal (HPP).
        Transaksi dibatalkan: <b>{{ $transaksiBatal }}</b> ({{ $rp($nominalBatal) }}), tidak dihitung dalam penjualan.
    </p>

    @if($menuModalKosong > 0)
        <div class="alert alert-warning py-2 small">
            <i class="bi bi-info-circle me-1"></i>
            {{ $menuModalKosong }} menu yang terjual belum punya <b>harga modal</b>, jadi labanya masih dianggap 100%.
            Isi di <a href="{{ route('admin.menu.index') }}" class="fw-bold">Master Menu &rarr; Edit</a>.
        </div>
    @endif

    {{-- ================= RINCIAN HARIAN ================= --}}
    <div class="card-custom bg-white p-3 mb-3">
        <h6 class="fw-bold text-potato-brown mb-3">Rincian Keuangan Harian</h6>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>TANGGAL</th>
                        <th class="text-center">TRANSAKSI</th>
                        <th class="text-center">ITEM</th>
                        <th class="text-end">PENJUALAN</th>
                        <th class="text-end">MODAL</th>
                        <th class="text-end">LABA KOTOR</th>
                        <th class="text-end">PENGELUARAN</th>
                        <th class="text-end">KEUNTUNGAN BERSIH</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rincianHarian as $h)
                    <tr>
                        <td class="fw-semibold text-potato-brown">{{ $h->tanggal->translatedFormat('D, d M Y') }}</td>
                        <td class="text-center">{{ $h->transaksi }}</td>
                        <td class="text-center">{{ $h->item }}</td>
                        <td class="text-end">{{ $rp($h->penjualan) }}</td>
                        <td class="text-end">{{ $rp($h->modal) }}</td>
                        <td class="text-end">{{ $rp($h->laba_kotor) }}</td>
                        <td class="text-end">{{ $rp($h->pengeluaran) }}</td>
                        <td class="text-end fw-semibold {{ $h->bersih >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($h->bersih) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi atau pengeluaran pada periode ini.</td></tr>
                @endforelse
                </tbody>
                @if($rincianHarian->count())
                <tfoot>
                    <tr class="fw-bold">
                        <td>TOTAL</td>
                        <td class="text-center">{{ $totalHarian['transaksi'] }}</td>
                        <td class="text-center">{{ $totalHarian['item'] }}</td>
                        <td class="text-end">{{ $rp($totalHarian['penjualan']) }}</td>
                        <td class="text-end">{{ $rp($totalHarian['modal']) }}</td>
                        <td class="text-end">{{ $rp($totalHarian['laba_kotor']) }}</td>
                        <td class="text-end">{{ $rp($totalHarian['pengeluaran']) }}</td>
                        <td class="text-end {{ $totalHarian['bersih'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($totalHarian['bersih']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- ================= METODE BAYAR & KASIR ================= --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card-custom bg-white p-3 h-100">
                <h6 class="fw-bold text-potato-brown mb-3">Penjualan per Metode Bayar</h6>
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                        <thead><tr><th>METODE</th><th class="text-center">TRANSAKSI</th><th class="text-end">TOTAL</th><th class="text-end">PORSI</th></tr></thead>
                        <tbody>
                        @forelse($perMetode as $m)
                            @php $persen = $totalPenjualan > 0 ? round($m->total / $totalPenjualan * 100) : 0; @endphp
                            <tr>
                                <td class="fw-semibold text-potato-brown">{{ $labelMetode[$m->metode_bayar] ?? strtoupper($m->metode_bayar) }}</td>
                                <td class="text-center">{{ $m->jumlah }}</td>
                                <td class="text-end">{{ $rp($m->total) }}</td>
                                <td class="text-end text-muted">{{ $persen }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card-custom bg-white p-3 h-100">
                <h6 class="fw-bold text-potato-brown mb-3">Performa per Kasir</h6>
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                        <thead><tr><th>KASIR</th><th class="text-center">TRANSAKSI</th><th class="text-end">TOTAL</th></tr></thead>
                        <tbody>
                        @forelse($perKasir as $k)
                            <tr>
                                <td class="fw-semibold text-potato-brown">{{ $k->name }}</td>
                                <td class="text-center">{{ $k->jumlah }}</td>
                                <td class="text-end">{{ $rp($k->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= PENJUALAN PER MENU ================= --}}
    <div class="card-custom bg-white p-3 mb-3">
        <h6 class="fw-bold text-potato-brown mb-3">Penjualan per Menu</h6>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead><tr><th>#</th><th>MENU</th><th class="text-center">QTY</th><th class="text-end">OMZET</th><th class="text-end">LABA</th></tr></thead>
                <tbody>
                @forelse($penjualanMenu as $i => $m)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold text-potato-brown">{{ $m->nama_menu }}</td>
                        <td class="text-center">{{ $m->qty }}</td>
                        <td class="text-end">{{ $rp($m->omzet) }}</td>
                        <td class="text-end fw-semibold {{ $m->laba >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($m->laba) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= PENGELUARAN ================= --}}
    <div class="card-custom bg-white p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-potato-brown mb-0">Pengeluaran</h6>
            <a href="{{ route('admin.pengeluaran.index', ['dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')]) }}" class="small fw-bold text-decoration-none no-print" style="color: #a86200;">Kelola Pengeluaran &rarr;</a>
        </div>
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                        <thead><tr><th>KATEGORI</th><th class="text-center">CATATAN</th><th class="text-end">TOTAL</th></tr></thead>
                        <tbody>
                        @foreach($labelKategori as $key => $label)
                            <tr>
                                <td class="fw-semibold text-potato-brown">{{ $label }}</td>
                                <td class="text-center">{{ $pengeluaranPerKategori[$key]->banyak ?? 0 }}</td>
                                <td class="text-end">{{ $rp($pengeluaranPerKategori[$key]->total ?? 0) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="2">TOTAL</td><td class="text-end">{{ $rp($totalPengeluaran) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                        <thead><tr><th>TANGGAL</th><th>KATEGORI</th><th>KETERANGAN</th><th>DICATAT OLEH</th><th class="text-end">JUMLAH</th></tr></thead>
                        <tbody>
                        @forelse($daftarPengeluaran as $p)
                            <tr>
                                <td>{{ $p->tanggal->format('d/m/Y') }}</td>
                                <td>{{ $labelKategori[$p->kategori] ?? $p->kategori }}</td>
                                <td>{{ $p->deskripsi }}</td>
                                <td>{{ $p->pencatat->name ?? '-' }}</td>
                                <td class="text-end">{{ $rp($p->jumlah) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada pengeluaran pada periode ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= PRE-ORDER ================= --}}
    <div class="card-custom bg-white p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-potato-brown mb-0">Pre-Order <span class="text-muted fw-normal" style="font-size: 11px;">(berdasarkan tanggal ambil)</span></h6>
            <span class="small text-muted">{{ $totalPreOrder }} pesanan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead><tr><th>STATUS</th><th class="text-center">JUMLAH</th><th class="text-end">TOTAL PESANAN</th><th class="text-end">DP DITERIMA</th></tr></thead>
                <tbody>
                @foreach($labelPreOrder as $key => $label)
                    @continue(! isset($preOrderStatus[$key]))
                    <tr>
                        <td class="fw-semibold text-potato-brown">{{ $label }}</td>
                        <td class="text-center">{{ $preOrderStatus[$key]->jumlah }}</td>
                        <td class="text-end">{{ $rp($preOrderStatus[$key]->total) }}</td>
                        <td class="text-end">{{ $rp($preOrderStatus[$key]->dp) }}</td>
                    </tr>
                @endforeach
                @if($totalPreOrder === 0)
                    <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada pre-order pada periode ini.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="small text-muted mt-2">Pre-order yang sudah <b>Selesai</b> otomatis tercatat sebagai transaksi, jadi sudah ikut dihitung di penjualan di atas.</div>
    </div>

    {{-- ================= STOK ================= --}}
    <div class="card-custom bg-white p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-potato-brown mb-0">Stok Menu</h6>
            <span class="small text-muted">Stok saat ini &middot; pergerakan pada periode</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead><tr><th>MENU</th><th>KATEGORI</th><th class="text-center">MASUK</th><th class="text-center">KELUAR</th><th class="text-center">STOK SAAT INI</th><th class="text-center">MINIMUM</th><th>STATUS</th></tr></thead>
                <tbody>
                @forelse($stokMenu as $s)
                    <tr>
                        <td class="fw-semibold text-potato-brown">{{ $s->nama }}</td>
                        <td>{{ $s->kategori }}</td>
                        <td class="text-center">{{ $s->masuk }}</td>
                        <td class="text-center">{{ $s->keluar }}</td>
                        <td class="text-center fw-bold">{{ $s->stok }}</td>
                        <td class="text-center text-muted">{{ $s->minimum }}</td>
                        <td><span class="badge-soft {{ $warnaStatus[$s->status] }}">{{ $labelStatus[$s->status] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Belum ada menu aktif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-custom bg-white p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-potato-brown mb-0">Stok Saus &amp; Bumbu Tabur</h6>
            <span class="small text-muted">{{ $stokPerhatian }} item perlu perhatian (menu + bumbu)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead><tr><th>NAMA</th><th>JENIS</th><th class="text-center">TERPAKAI (PERIODE)</th><th class="text-center">STOK SAAT INI</th><th class="text-center">MINIMUM</th><th>STATUS</th></tr></thead>
                <tbody>
                @forelse($stokBumbu as $b)
                    <tr>
                        <td class="fw-semibold text-potato-brown">{{ $b->nama }}</td>
                        <td>{{ $b->jenis }}</td>
                        <td class="text-center">{{ $b->dipakai }} {{ $b->satuan }}</td>
                        <td class="text-center fw-bold">{{ $b->stok }} {{ $b->satuan }}</td>
                        <td class="text-center text-muted">{{ $b->minimum }}</td>
                        <td><span class="badge-soft {{ $warnaStatus[$b->status] }}">{{ $labelStatus[$b->status] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data bumbu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= TRANSAKSI DIBATALKAN ================= --}}
    <div class="card-custom bg-white p-3">
        <h6 class="fw-bold text-potato-brown mb-3">Transaksi Dibatalkan</h6>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" style="font-size: 13px;">
                <thead><tr><th>KODE</th><th>TANGGAL</th><th>KASIR</th><th>ALASAN</th><th class="text-end">TOTAL</th></tr></thead>
                <tbody>
                @forelse($daftarBatal as $t)
                    <tr>
                        <td class="fw-semibold text-potato-brown">{{ $t->kode_transaksi }}</td>
                        <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $t->kasir->name ?? '-' }}</td>
                        <td>{{ $t->alasan_batal ?? '-' }}</td>
                        <td class="text-end">{{ $rp($t->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada transaksi dibatalkan pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
