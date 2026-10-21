@extends('layouts.admin')

@section('title', 'Dashboard - Potato Curly Crunch')

@push('styles')
<!-- Litepicker CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css"/>
<style>
    /* Styling Litepicker Cokelat Amber Warm */
    .litepicker {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(61, 35, 20, 0.15) !important;
        border: 1px solid #efe8e0 !important;
        z-index: 9999 !important;
    }
    .litepicker .container__days .day-item.is-start-date,
    .litepicker .container__days .day-item.is-end-date {
        background-color: #fcae1e !important;
        color: #ffffff !important;
        border-radius: 50% !important;
    }
    .litepicker .container__days .day-item.is-in-range {
        background-color: #fceecb !important;
        color: #3d2314 !important;
        border-radius: 0 !important;
    }
    .litepicker .container__days .day-item:hover {
        box-shadow: inset 0 0 0 2px #fcae1e !important;
    }

    /* Helper Colors */
    .text-potato-brown {
        color: #3d2314;
    }
    .badge-amber {
        background-color: #fceecb;
        color: #3d2314;
        border: 1px solid #fcae1e;
    }

    /* Container Picker Tanggal Interaktif */
    .date-picker-button {
        background: #ffffff;
        border: 1px solid #efe8e0;
        border-radius: 12px;
        padding: 8px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        user-select: none;
    }
    .date-picker-button:hover {
        border-color: #fcae1e;
        box-shadow: 0 4px 12px rgba(252, 174, 30, 0.15);
    }

    /* Card Standard */
    .card-custom {
        border-radius: 16px;
        border: 1px solid #efe8e0;
        box-shadow: 0 2px 8px rgba(61, 35, 20, 0.04);
        transition: all 0.2s ease;
    }

    /* Card Hero Dark Utama */
    .card-hero-dark {
        background-color: #3d2314;
        border-radius: 16px;
        padding: 20px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        border: 1px solid #3d2314;
        box-shadow: 0 4px 15px rgba(61, 35, 20, 0.2);
        transition: all 0.3s ease;
        height: 100%;
    }

    /* Bintang 6 Kelopak SVG Wrapper (Sama persis seperti template Spark) */
    .card-hero-dark .asterisk-wrapper {
        position: absolute;
        bottom: -20px;
        right: -20px;
        width: 120px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .card-hero-dark .asterisk-icon {
        width: 100%;
        height: 100%;
        transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Efek Muter Halus Saat Card Di-hover */
    .card-hero-dark:hover .asterisk-icon {
        transform: rotate(180deg);
    }


    /* ===== Grid 5 kartu metrik: sejajar satu baris ===== */
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 1rem;
    }
    /* Tablet/laptop kecil: baris 1 = 3 kartu, baris 2 = 2 kartu lebar penuh (tetap rata kanan-kiri) */
    .metric-grid > .metric-item { grid-column: span 2; display: flex; min-width: 0; }
    .metric-grid > .metric-item:nth-child(n+4) { grid-column: span 3; }

    /* Layar lebar: 5 kartu sejajar dalam satu baris */
    @media (min-width: 1200px) {
        .metric-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .metric-grid > .metric-item,
        .metric-grid > .metric-item:nth-child(n+4) { grid-column: span 1; }
    }
    /* HP: 2 kolom, kartu terakhir melebar */
    @media (max-width: 767.98px) {
        .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .metric-grid > .metric-item,
        .metric-grid > .metric-item:nth-child(n+4) { grid-column: span 1; }
        .metric-grid > .metric-item:last-child { grid-column: span 2; }
    }
    @media (max-width: 479.98px) {
        .metric-grid { grid-template-columns: minmax(0, 1fr); }
        .metric-grid > .metric-item:last-child { grid-column: span 1; }
    }

    .metric-card {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        padding: 16px 18px;
        container-type: inline-size;
    }
    .metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-height: 36px;
        margin-bottom: 8px;
    }
    .metric-label { font-size: 12px; font-weight: 600; line-height: 1.25; }
    .metric-icon {
        width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .metric-value {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-weight: 800;
        font-size: 1.25rem;
        line-height: 1.2;
        color: #3d2314;
        white-space: nowrap;
    }
    @supports (container-type: inline-size) {
        .metric-value { font-size: clamp(1rem, 9cqw, 1.5rem); }
    }
    .metric-foot { margin-top: auto; padding-top: 12px; display: flex; flex-direction: column; gap: 4px; position: relative; z-index: 1; }
    .metric-sub { font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 4px; }
    .metric-link { font-size: 11px; font-weight: 700; text-decoration: none; color: #a86200; }
    .metric-link:hover { text-decoration: underline; }
    .metric-card > .metric-top, .metric-card > .metric-value { position: relative; z-index: 1; }
    .card-hero-dark.metric-card .asterisk-wrapper { width: 84px; height: 84px; bottom: -18px; right: -16px; opacity: .22; z-index: 0; }

    @keyframes spin-custom {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .spin-icon {
        display: inline-block;
        animation: spin-custom 2s linear infinite;
    }
</style>
@endpush

@section('content')
<div class="p-4" style="background-color: #fcf8f2; min-height: calc(100vh - 68px);">

    <!-- Header & Date Range Picker -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-extrabold mb-1 text-potato-brown" style="font-weight: 800;">Dashboard Overview</h4>
            <p class="text-muted small mb-0">Pantau penjualan, stok, dan operasional Potato Curly Crunch secara real-time.</p>
        </div>

        <div>
            <!-- Button Date Range Picker Interaktif -->
            <div id="datePickerContainer" class="date-picker-button d-flex align-items-center justify-content-between shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3 fs-6" style="color: #fcae1e;"></i>
                    <span class="fw-bold small text-potato-brown" id="displayRangeText">Memuat...</span>
                </div>
                <i class="bi bi-chevron-down ms-3 text-muted extra-small"></i>
                <!-- Hidden Input untuk Pemicu Litepicker -->
                <input type="text" id="litepickerInput" style="position: absolute; opacity: 0; width: 100%; height: 100%; top:0; left:0; cursor:pointer;" readonly />
            </div>
        </div>
    </div>

    @php
        $semuaAktif = ($totalKasir > 0 && $kasirAktif == $totalKasir);
    @endphp

    <!-- 5 Kartu Metrik Utama: satu baris sejajar (5 kolom di layar lebar, otomatis rapi di layar kecil) -->
    <div class="metric-grid mb-4">

        <!-- Kartu 1: Penjualan Hari Ini -->
        <div class="metric-item">
            <div class="card-hero-dark metric-card">
                <div class="metric-top">
                    <span class="metric-label text-white-50">Penjualan Hari Ini</span>
                    <span class="badge rounded-pill px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                          style="background-color: rgba(255, 255, 255, 0.15); color: #ffffff; font-size: 10px;">
                        <span class="rounded-circle" style="width: 6px; height: 6px; background-color: #fcae1e;"></span>
                        Live
                    </span>
                </div>

                <div class="metric-value text-white">Rp {{ number_format($penjualanHariIni, 0, ',', '.') }}</div>

                <div class="metric-foot">
                    <div class="metric-sub text-white-50">
                        @if(is_null($persenPenjualan))
                            <span>Belum ada pembanding</span>
                        @elseif($persenPenjualan >= 0)
                            <span class="text-warning"><i class="bi bi-arrow-up-short"></i> +{{ $persenPenjualan }}%</span> dari kemarin
                        @else
                            <span class="text-danger"><i class="bi bi-arrow-down-short"></i> {{ $persenPenjualan }}%</span> dari kemarin
                        @endif
                    </div>
                    <a href="{{ route('admin.laporan.index') }}" class="metric-link text-warning">Lihat Laporan <i class="bi bi-arrow-right"></i></a>
                </div>

                <!-- SVG Bintang 6 Kelopak (kuning emas) -->
                <div class="asterisk-wrapper">
                    <svg class="asterisk-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g fill="#fcae1e">
                            <rect x="42" y="5" width="16" height="90" rx="8" />
                            <rect x="42" y="5" width="16" height="90" rx="8" transform="rotate(60 50 50)" />
                            <rect x="42" y="5" width="16" height="90" rx="8" transform="rotate(120 50 50)" />
                        </g>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Jumlah Transaksi -->
        <div class="metric-item">
            <div class="card-custom bg-white metric-card">
                <div class="metric-top">
                    <span class="metric-label text-muted">Jumlah Transaksi</span>
                    <span class="metric-icon" style="background-color: #fceecb; color: #fcae1e;"><i class="bi bi-receipt"></i></span>
                </div>
                <div class="metric-value">{{ $jumlahTransaksiHariIni }} Transaksi</div>
                <div class="metric-foot">
                    <div class="metric-sub text-muted"><i class="bi bi-calendar-check"></i> Hari ini</div>
                    <a href="{{ route('admin.riwayat.index') }}" class="metric-link">Lihat Riwayat <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Kartu 3: Stok Menipis / Habis -->
        <div class="metric-item">
            <div class="card-custom bg-white metric-card">
                <div class="metric-top">
                    <span class="metric-label text-muted">Stok Menipis / Habis</span>
                    <span class="metric-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle"></i></span>
                </div>
                <div class="metric-value">{{ $stokMenipis + $stokHabis }} Item</div>
                <div class="metric-foot">
                    @if($stokMenipis + $stokHabis > 0)
                        <div class="metric-sub text-danger"><i class="bi bi-exclamation-circle"></i> Perlu diperhatikan</div>
                    @else
                        <div class="metric-sub text-success"><i class="bi bi-check-circle-fill"></i> Semua stok aman</div>
                    @endif
                    <a href="{{ route('admin.stok.index') }}" class="metric-link">Lihat Stok <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Kartu 4: Kasir Bertugas -->
        <div class="metric-item">
            <div class="card-custom bg-white metric-card">
                <div class="metric-top">
                    <span class="metric-label text-muted">Kasir Bertugas</span>
                    <span class="metric-icon" style="background-color: #fceecb; color: #fcae1e;"><i class="bi bi-person-badge"></i></span>
                </div>
                <div class="metric-value">{{ $kasirAktif }} Kasir</div>
                <div class="metric-foot">
                    @if($semuaAktif)
                        <div class="metric-sub text-success"><i class="bi bi-check-circle-fill"></i> Semua kasir aktif</div>
                    @else
                        <div class="metric-sub text-danger"><i class="bi bi-x-circle-fill"></i> {{ $totalKasir - $kasirAktif }} kasir tidak aktif</div>
                    @endif
                    <a href="{{ route('admin.kasir.index') }}" class="metric-link">Kelola Kasir <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Kartu 5: Keuntungan (sesuai rentang tanggal yang dipilih) -->
        <div class="metric-item">
            <div class="card-custom bg-white metric-card">
                <div class="metric-top">
                    <span class="metric-label text-muted">Keuntungan Bersih</span>
                    <span class="metric-icon" style="background-color: #fceecb; color: #fcae1e;"><i class="bi bi-graph-up-arrow"></i></span>
                </div>
                <div class="metric-value {{ $keuntunganBersihRentang >= 0 ? '' : 'text-danger' }}">Rp {{ number_format($keuntunganBersihRentang, 0, ',', '.') }}</div>
                <div class="metric-foot">
                    <div class="metric-sub text-muted"><i class="bi bi-calendar-range"></i> {{ $dari->format('d M') }} &ndash; {{ $sampai->format('d M') }}</div>
                    <a href="{{ route('admin.laporan.index', ['dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')]) }}" class="metric-link">Rincian di Laporan <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Bar Chart & Donut Chart -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card-custom p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-potato-brown mb-0">Penjualan per Hari</h6>
                    <div class="d-flex gap-3 extra-small text-muted" style="font-size: 11px;">
                        <span><span class="d-inline-block rounded-circle me-1" style="width:8px; height:8px; background:#fcae1e;"></span> Penjualan</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:8px; height:8px; background:#3d2314;"></span> Transaksi</span>
                    </div>
                </div>
                <div class="small text-muted mb-2">Total periode: <b class="text-potato-brown">Rp {{ number_format($totalRentang, 0, ',', '.') }}</b> dari <b class="text-potato-brown">{{ $jumlahRentang }}</b> transaksi</div>
                <div id="chart-penjualan-bar" style="min-height: 250px;"></div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card-custom p-3 h-100 bg-white">
                <h6 class="fw-bold text-potato-brown mb-3">Status Stok (Menu, Saus &amp; Bumbu)</h6>
                <div id="chart-donut-stok" class="d-flex justify-content-center align-items-center" style="min-height: 180px;"></div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span><i class="bi bi-circle-fill text-success me-1" style="font-size: 8px;"></i> Aman</span>
                        <span class="fw-bold text-potato-brown">{{ $stokAman }} item ({{ round($stokAman / $totalItemStok * 100) }}%)</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span><i class="bi bi-circle-fill me-1" style="font-size: 8px; color: #fcae1e;"></i> Menipis</span>
                        <span class="fw-bold text-potato-brown">{{ $stokMenipis }} item ({{ round($stokMenipis / $totalItemStok * 100) }}%)</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span><i class="bi bi-circle-fill text-danger me-1" style="font-size: 8px;"></i> Habis</span>
                        <span class="fw-bold text-potato-brown">{{ $stokHabis }} item ({{ round($stokHabis / $totalItemStok * 100) }}%)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Tabel Peringatan Stok & Transaksi Terbaru -->
    <div class="row g-3 mb-4">
        <!-- Peringatan Stok -->
        <div class="col-12 col-lg-5">
            <div class="card-custom p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-potato-brown mb-0">Peringatan Stok</h6>
                    <a href="{{ route('admin.stok.index') }}" class="extra-small text-decoration-none fw-bold" style="font-size: 11px; color: #fcae1e;">Lihat Semua &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-sm align-middle mb-0" style="font-size: 12px;">
                        <tbody>
                            @forelse($peringatanStok as $b)
                                <tr>
                                    <td width="30">
                                        <i class="bi bi-exclamation-triangle-fill fs-6 {{ $b->status == 'habis' ? 'text-danger' : '' }}"
                                           style="{{ $b->status == 'habis' ? '' : 'color: #fcae1e;' }}"></i>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-potato-brown">{{ $b->nama }}</div>
                                        <div class="text-muted extra-small" style="font-size: 10px;">{{ $b->jenis }}</div>
                                    </td>
                                    <td>
                                        @if($b->status == 'habis')
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Habis</span>
                                        @else
                                            <span class="badge badge-amber px-2 py-1">Menipis</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-potato-brown">{{ $b->stok }} {{ $b->satuan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Semua stok aman, belum ada yang perlu diperhatikan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transaksi Terbaru -->
        <div class="col-12 col-lg-7">
            <div class="card-custom p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-potato-brown mb-0">Transaksi Terbaru <span class="text-muted fw-normal" style="font-size: 11px;">(periode dipilih)</span></h6>
                    <a href="{{ route('admin.riwayat.index') }}" class="extra-small text-decoration-none fw-bold" style="font-size: 11px; color: #fcae1e;">Lihat Semua &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-sm align-middle mb-0" style="font-size: 12px;">
                        <thead class="text-muted border-bottom" style="font-size: 11px;">
                            <tr>
                                <th>KODE TRANSAKSI</th>
                                <th>KASIR</th>
                                <th>WAKTU</th>
                                <th>METODE</th>
                                <th class="text-end">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksiTerbaru as $trx)
                                <tr>
                                    <td class="fw-bold text-potato-brown">{{ $trx->kode_transaksi }}</td>
                                    <td>{{ $trx->kasir->name ?? '-' }}</td>
                                    <td class="text-muted">{{ $trx->created_at->format('H:i') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst($trx->metode_bayar) }}</span></td>
                                    <td class="text-end fw-bold text-potato-brown">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Menu Terlaris -->
    <div class="row">
        <div class="col-12">
            <div class="card-custom p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-potato-brown mb-0">Menu Terlaris <span class="text-muted fw-normal" style="font-size: 11px;">(periode dipilih)</span></h6>
                    <a href="{{ route('admin.laporan.index', ['dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')]) }}" class="extra-small text-decoration-none fw-bold" style="font-size: 11px; color: #fcae1e;">Lihat Semua &rarr;</a>
                </div>
                <div class="row g-3">
                    @forelse($menuTerlaris as $i => $menu)
                        <div class="col-12 col-md-4">
                            <div class="border rounded-3 p-2 d-flex align-items-center justify-content-between" style="border-color: #efe8e0 !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-circle p-2" style="background: #fcae1e; color: #fff;">{{ $i + 1 }}</span>
                                    <span class="fw-bold small text-potato-brown">{{ $menu->nama_menu }}</span>
                                </div>
                                <span class="text-muted extra-small fw-semibold">{{ $menu->terjual }} pcs</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-3">Belum ada penjualan pada periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<!-- Litepicker JS -->
<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Formatter Tanggal Rapi
        function formatDateStr(d) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        }

        const dari = new Date('{{ $dari->format('Y-m-d') }}T00:00:00');
        const sampai = new Date('{{ $sampai->format('Y-m-d') }}T00:00:00');
        const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

        const displayLabel = document.getElementById('displayRangeText');
        displayLabel.innerText = `${formatDateStr(dari)} – ${formatDateStr(sampai)}`;

        // Date picker: setelah pilih rentang, halaman dimuat ulang dengan data periode itu
        new Litepicker({
            element: document.getElementById('litepickerInput'),
            singleMode: false,
            startDate: dari,
            endDate: sampai,
            numberOfMonths: 1,
            numberOfColumns: 1,
            format: 'DD MMM YYYY',
            maxDate: new Date(),
            setup: (picker) => {
                picker.on('selected', (date1, date2) => {
                    window.location.href = `{{ route('dashboard') }}?dari=${iso(date1.toJSDate())}&sampai=${iso(date2.toJSDate())}`;
                });
            }
        });

        // Bar Chart Penjualan (data real 7 hari terakhir)
        var optionsBar = {
            series: [{
                name: 'Penjualan',
                type: 'column',
                data: @json($dataPenjualan)
            }, {
                name: 'Jumlah Transaksi',
                type: 'line',
                data: @json($dataJumlahTransaksi)
            }],
            chart: {
                height: 250,
                type: 'line',
                toolbar: { show: false }
            },
            colors: ['#fcae1e', '#3d2314'],
            stroke: { width: [0, 3], curve: 'smooth' },
            plotOptions: { bar: { columnWidth: '40%', borderRadius: 6 } },
            xaxis: { categories: @json($labelHari), tickAmount: Math.min(@json(count($labelHari)), 10) },
            yaxis: [
                { show: false },
                { show: false, opposite: true, min: 0 }
            ],
            tooltip: {
                y: [
                    { formatter: v => 'Rp ' + Number(v).toLocaleString('id-ID') },
                    { formatter: v => v + ' transaksi' }
                ]
            }
        };
        new ApexCharts(document.querySelector("#chart-penjualan-bar"), optionsBar).render();

        // Donut Chart Stok (data real)
        var optionsDonut = {
            series: [{{ $stokAman }}, {{ $stokMenipis }}, {{ $stokHabis }}],
            chart: { type: 'donut', height: 180 },
            labels: ['Aman', 'Menipis', 'Habis'],
            colors: ['#22c55e', '#fcae1e', '#ef4444'],
            legend: { show: false },
            dataLabels: { enabled: false }
        };
        new ApexCharts(document.querySelector("#chart-donut-stok"), optionsDonut).render();
    });
</script>
@endpush
