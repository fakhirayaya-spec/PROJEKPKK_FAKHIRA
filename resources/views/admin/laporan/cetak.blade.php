<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan {{ $dari->format('d-m-Y') }} s/d {{ $sampai->format('d-m-Y') }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 12px; margin: 0; padding: 18px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 13px; margin: 20px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #f3a712; text-transform: uppercase; letter-spacing: .5px; page-break-after: avoid; }
        .sub { color: #666; margin-top: 3px; }
        .note { color: #666; font-size: 11px; margin-top: 6px; }
        .head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid #5a2d0c; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #fbf3e4; font-size: 11px; }
        tfoot th, tfoot td { background: #fbf3e4; font-weight: bold; }
        td.r, th.r { text-align: right; }
        td.c, th.c { text-align: center; }
        .ringkas td:first-child { width: 55%; color: #555; }
        .ringkas td:last-child { text-align: right; font-weight: bold; }
        .laba { color: #15803d; }
        .rugi { color: #b91c1c; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 22px; }
        .ttd { margin-top: 36px; display: flex; justify-content: flex-end; page-break-inside: avoid; }
        .ttd div { width: 200px; text-align: center; }
        .ttd .garis { margin-top: 56px; border-top: 1px solid #333; padding-top: 3px; }
        .bar { background: #fff8e6; border: 1px solid #f3dfae; padding: 10px 14px; margin-bottom: 14px; border-radius: 8px; display: flex; gap: 8px; align-items: center; }
        .bar button { background: #5a2d0c; color: #fff; border: 0; padding: 7px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .bar span { color: #8a5a00; font-size: 12px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        @media print { .bar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
@php
    $rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $labelKategori = ['bahan_baku' => 'Bahan Baku', 'operasional' => 'Operasional', 'lainnya' => 'Lainnya'];
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
    <div class="bar">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <span>Di jendela cetak, pilih <b>Simpan sebagai PDF</b> pada bagian Tujuan (Destination).</span>
    </div>

    <div class="head">
        <div>
            <h1>Laporan Penjualan &amp; Keuangan</h1>
            <div class="sub">Potato Curly Crunch</div>
        </div>
        <div style="text-align:right;">
            <div><b>Periode:</b> {{ $dari->format('d M Y') }} &ndash; {{ $sampai->format('d M Y') }}</div>
            <div class="sub">Dicetak {{ now()->format('d M Y, H:i') }} oleh {{ $dicetakOleh }}</div>
        </div>
    </div>

    {{-- ================= 1. RINGKASAN ================= --}}
    <h2>1. Ringkasan</h2>
    <div class="grid">
        <table class="ringkas">
            <tr><td>Total Penjualan</td><td>{{ $rp($totalPenjualan) }}</td></tr>
            <tr><td>Jumlah Transaksi</td><td>{{ number_format($jumlahTransaksi, 0, ',', '.') }}</td></tr>
            <tr><td>Rata-rata per Transaksi</td><td>{{ $rp($rataRata) }}</td></tr>
            <tr><td>Item Terjual</td><td>{{ number_format($itemTerjual, 0, ',', '.') }}</td></tr>
            <tr><td>Transaksi Dibatalkan</td><td>{{ number_format($transaksiBatal, 0, ',', '.') }} ({{ $rp($nominalBatal) }})</td></tr>
        </table>
        <table class="ringkas">
            <tr><td>Modal (HPP)</td><td>{{ $rp($totalModal) }}</td></tr>
            <tr><td>Laba Kotor ({{ $marginPersen }}%)</td><td class="{{ $labaKotor >= 0 ? 'laba' : 'rugi' }}">{{ $rp($labaKotor) }}</td></tr>
            <tr><td>Pengeluaran Operasional &amp; Lainnya</td><td>{{ $rp($pengeluaranOperasional) }}</td></tr>
            <tr><td>Keuntungan Bersih ({{ $marginBersih }}%)</td><td class="{{ $keuntunganBersih >= 0 ? 'laba' : 'rugi' }}">{{ $rp($keuntunganBersih) }}</td></tr>
            <tr><td>Pengeluaran Bahan Baku (tercatat)</td><td>{{ $rp($pengeluaranBahanBaku) }}</td></tr>
        </table>
    </div>
    <div class="note">Keuntungan Bersih = Laba Kotor &minus; Pengeluaran Operasional &amp; Lainnya. Pengeluaran Bahan Baku tidak dikurangkan lagi karena sudah masuk Modal (HPP).</div>
    @if($menuModalKosong > 0)
        <div class="note">* {{ $menuModalKosong }} menu yang terjual belum punya harga modal, sehingga labanya masih dianggap sama dengan penjualan.</div>
    @endif

    {{-- ================= 2. RINCIAN HARIAN ================= --}}
    <h2>2. Rincian Keuangan Harian</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th><th class="c">Trx</th><th class="c">Item</th><th class="r">Penjualan</th>
                <th class="r">Modal</th><th class="r">Laba Kotor</th><th class="r">Pengeluaran</th><th class="r">Untung Bersih</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rincianHarian as $h)
            <tr>
                <td>{{ $h->tanggal->format('d/m/Y') }}</td>
                <td class="c">{{ $h->transaksi }}</td>
                <td class="c">{{ $h->item }}</td>
                <td class="r">{{ $rp($h->penjualan) }}</td>
                <td class="r">{{ $rp($h->modal) }}</td>
                <td class="r">{{ $rp($h->laba_kotor) }}</td>
                <td class="r">{{ $rp($h->pengeluaran) }}</td>
                <td class="r {{ $h->bersih >= 0 ? 'laba' : 'rugi' }}">{{ $rp($h->bersih) }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="c">Belum ada transaksi atau pengeluaran pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if($rincianHarian->count())
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="c">{{ $totalHarian['transaksi'] }}</td>
                <td class="c">{{ $totalHarian['item'] }}</td>
                <td class="r">{{ $rp($totalHarian['penjualan']) }}</td>
                <td class="r">{{ $rp($totalHarian['modal']) }}</td>
                <td class="r">{{ $rp($totalHarian['laba_kotor']) }}</td>
                <td class="r">{{ $rp($totalHarian['pengeluaran']) }}</td>
                <td class="r">{{ $rp($totalHarian['bersih']) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- ================= 3. METODE BAYAR & KASIR ================= --}}
    <div class="grid">
        <div>
            <h2>3. Per Metode Bayar</h2>
            <table>
                <thead><tr><th>Metode</th><th class="c">Transaksi</th><th class="r">Total</th></tr></thead>
                <tbody>
                @forelse($perMetode as $m)
                    <tr><td>{{ $labelMetode[$m->metode_bayar] ?? strtoupper($m->metode_bayar) }}</td><td class="c">{{ $m->jumlah }}</td><td class="r">{{ $rp($m->total) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="c">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>
            <h2>Per Kasir</h2>
            <table>
                <thead><tr><th>Kasir</th><th class="c">Transaksi</th><th class="r">Total</th></tr></thead>
                <tbody>
                @forelse($perKasir as $k)
                    <tr><td>{{ $k->name }}</td><td class="c">{{ $k->jumlah }}</td><td class="r">{{ $rp($k->total) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="c">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= 4. PENJUALAN PER MENU ================= --}}
    <h2>4. Penjualan per Menu</h2>
    <table>
        <thead><tr><th class="c">No</th><th>Menu</th><th class="c">Qty</th><th class="r">Omzet</th><th class="r">Laba</th></tr></thead>
        <tbody>
        @forelse($penjualanMenu as $i => $m)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $m->nama_menu }}</td>
                <td class="c">{{ $m->qty }}</td>
                <td class="r">{{ $rp($m->omzet) }}</td>
                <td class="r {{ $m->laba >= 0 ? 'laba' : 'rugi' }}">{{ $rp($m->laba) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="c">Belum ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- ================= 5. PENGELUARAN ================= --}}
    <h2>5. Pengeluaran</h2>
    <table>
        <thead><tr><th class="c">No</th><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Dicatat Oleh</th><th class="r">Jumlah</th></tr></thead>
        <tbody>
        @forelse($daftarPengeluaran as $i => $p)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $p->tanggal->format('d/m/Y') }}</td>
                <td>{{ $labelKategori[$p->kategori] ?? $p->kategori }}</td>
                <td>{{ $p->deskripsi }}</td>
                <td>{{ $p->pencatat->name ?? '-' }}</td>
                <td class="r">{{ $rp($p->jumlah) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="c">Belum ada pengeluaran pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if($daftarPengeluaran->count())
        <tfoot>
            @foreach($labelKategori as $key => $label)
                <tr><td colspan="5" class="r">Subtotal {{ $label }}</td><td class="r">{{ $rp($pengeluaranPerKategori[$key]->total ?? 0) }}</td></tr>
            @endforeach
            <tr><td colspan="5" class="r">TOTAL PENGELUARAN</td><td class="r">{{ $rp($totalPengeluaran) }}</td></tr>
        </tfoot>
        @endif
    </table>

    {{-- ================= 6. PRE-ORDER ================= --}}
    <h2>6. Pre-Order (berdasarkan tanggal ambil)</h2>
    <table>
        <thead><tr><th>Status</th><th class="c">Jumlah</th><th class="r">Total Pesanan</th><th class="r">DP Diterima</th></tr></thead>
        <tbody>
        @foreach($labelPreOrder as $key => $label)
            @continue(! isset($preOrderStatus[$key]))
            <tr>
                <td>{{ $label }}</td>
                <td class="c">{{ $preOrderStatus[$key]->jumlah }}</td>
                <td class="r">{{ $rp($preOrderStatus[$key]->total) }}</td>
                <td class="r">{{ $rp($preOrderStatus[$key]->dp) }}</td>
            </tr>
        @endforeach
        @if($totalPreOrder === 0)
            <tr><td colspan="4" class="c">Tidak ada pre-order pada periode ini.</td></tr>
        @endif
        </tbody>
    </table>
    <div class="note">Pre-order berstatus Selesai otomatis tercatat sebagai transaksi sehingga sudah termasuk dalam penjualan di atas.</div>

    {{-- ================= 7. STOK ================= --}}
    <h2>7. Laporan Stok Menu</h2>
    <table>
        <thead><tr><th>Menu</th><th>Kategori</th><th class="c">Masuk</th><th class="c">Keluar</th><th class="c">Stok Saat Ini</th><th class="c">Minimum</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($stokMenu as $s)
            <tr>
                <td>{{ $s->nama }}</td>
                <td>{{ $s->kategori }}</td>
                <td class="c">{{ $s->masuk }}</td>
                <td class="c">{{ $s->keluar }}</td>
                <td class="c">{{ $s->stok }}</td>
                <td class="c">{{ $s->minimum }}</td>
                <td>{{ $labelStatus[$s->status] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="c">Belum ada menu aktif.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Laporan Stok Saus &amp; Bumbu Tabur</h2>
    <table>
        <thead><tr><th>Nama</th><th>Jenis</th><th class="c">Terpakai (Periode)</th><th class="c">Stok Saat Ini</th><th class="c">Minimum</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($stokBumbu as $b)
            <tr>
                <td>{{ $b->nama }}</td>
                <td>{{ $b->jenis }}</td>
                <td class="c">{{ $b->dipakai }} {{ $b->satuan }}</td>
                <td class="c">{{ $b->stok }} {{ $b->satuan }}</td>
                <td class="c">{{ $b->minimum }}</td>
                <td>{{ $labelStatus[$b->status] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="c">Belum ada data bumbu.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- ================= 8. DAFTAR TRANSAKSI ================= --}}
    <h2>8. Daftar Transaksi</h2>
    <table>
        <thead><tr><th class="c">No</th><th>Kode</th><th>Tanggal</th><th>Kasir</th><th>Metode</th><th class="r">Total</th></tr></thead>
        <tbody>
        @forelse($daftarTransaksi as $i => $t)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $t->kode_transaksi }}</td>
                <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $t->kasir->name ?? '-' }}</td>
                <td>{{ $labelMetode[$t->metode_bayar] ?? strtoupper($t->metode_bayar) }}</td>
                <td class="r">{{ $rp($t->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="c">Tidak ada transaksi pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if($daftarTransaksi->count())
        <tfoot><tr><td colspan="5" class="r">TOTAL</td><td class="r">{{ $rp($totalPenjualan) }}</td></tr></tfoot>
        @endif
    </table>

    @if($daftarBatal->count())
        <h2>Transaksi Dibatalkan</h2>
        <table>
            <thead><tr><th class="c">No</th><th>Kode</th><th>Tanggal</th><th>Kasir</th><th>Alasan</th><th class="r">Total</th></tr></thead>
            <tbody>
            @foreach($daftarBatal as $i => $t)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $t->kode_transaksi }}</td>
                    <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $t->kasir->name ?? '-' }}</td>
                    <td>{{ $t->alasan_batal ?? '-' }}</td>
                    <td class="r">{{ $rp($t->total) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="ttd">
        <div>
            Cimahi, {{ now()->format('d F Y') }}<br>Pemilik Usaha
            <div class="garis">( ................................. )</div>
        </div>
    </div>
</body>
</html>
