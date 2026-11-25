{{-- Tabel riwayat stok. Variabel: $riwayat (paginator), $tipe ('menu'|'bumbu'), $tampilKasir (bool) --}}
<div class="table-responsive">
    <table class="table align-middle table-hover mb-0" style="font-size: 13px;">
        <thead class="text-muted border-bottom" style="font-size: 11px;">
            <tr>
                <th>TANGGAL</th>
                @if ($tampilKasir)<th>KASIR</th>@endif
                <th>{{ $tipe === 'menu' ? 'MENU' : 'BUMBU' }}</th>
                <th>JENIS</th>
                <th class="text-end">JUMLAH</th>
                <th>KETERANGAN</th>
                @if ($tampilKasir)<th>OLEH</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayat as $r)
                @php
                    $nama = $tipe === 'menu'
                        ? ($r->menu->nama_menu ?? '(menu dihapus)')
                        : ($r->bumbu->nama ?? $r->nama ?? '(bumbu dihapus)');
                    $satuan = $tipe === 'menu' ? 'pcs' : ($r->bumbu->satuan ?? 'porsi');
                    $masuk = $r->jenis === 'masuk';
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $r->tanggal->format('d/m/Y') }}<div class="text-muted" style="font-size: 11px;">{{ $r->created_at?->format('H:i') }}</div></td>
                    @if ($tampilKasir)<td>{{ $r->kasir->name ?? '-' }}</td>@endif
                    <td class="fw-semibold" style="color:#3d2314;">
                        {{ $nama }}
                        @if ($tipe === 'bumbu' && $r->bumbu)
                            <span class="badge bg-secondary-subtle text-secondary fw-normal">{{ $r->bumbu->jenis === 'saus' ? 'Saus' : 'Bumbu tabur' }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $masuk ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $masuk ? 'Masuk' : 'Keluar' }}</span>
                    </td>
                    <td class="text-end fw-bold text-nowrap {{ $masuk ? 'text-success' : 'text-danger' }}">
                        {{ $masuk ? '+' : '-' }}{{ $r->jumlah }} <span class="fw-normal text-muted">{{ $satuan }}</span>
                    </td>
                    <td class="text-muted">{{ $r->keterangan ?: '-' }}</td>
                    @if ($tampilKasir)<td class="text-muted">{{ $r->user->name ?? 'Sistem' }}</td>@endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $tampilKasir ? 7 : 5 }}" class="text-center text-muted py-4">
                        {{ request()->hasAny(['q', 'jenis', 'dari', 'sampai', 'kasir_id']) ? 'Tidak ada riwayat yang cocok dengan pencarian.' : 'Belum ada riwayat.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $riwayat->links() }}</div>
