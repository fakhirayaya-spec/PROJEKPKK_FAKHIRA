<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Potato Curly Crunch')</title>

    <link rel="icon" type="image/jpg" href="{{ asset('asset_login/images/logopotato.jpg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/apexcharts/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets_dashboard/css/potato-ui.css') }}?v=3">

    <style>
        .potato-dialog { border: 0; border-radius: 22px; box-shadow: 0 24px 60px rgba(61, 35, 20, .28); overflow: hidden; }
        .potato-dialog .modal-body { padding: 30px 28px 26px; }
        .potato-dialog .pd-icon { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; }
        .potato-dialog .pd-icon.bahaya { background: #fee2e2; color: #dc2626; box-shadow: 0 0 0 8px #fef2f2; }
        .potato-dialog .pd-icon.tanya { background: #fff1cf; color: #b86e00; box-shadow: 0 0 0 8px #fff8e6; }
        .potato-dialog .pd-icon.info { background: #fff1cf; color: #b86e00; box-shadow: 0 0 0 8px #fff8e6; }
        .potato-dialog .pd-title { font-family: 'Bricolage Grotesque', 'Plus Jakarta Sans', sans-serif; font-weight: 800; color: #3d2314; margin-bottom: 8px; }
        .potato-dialog .pd-msg { color: #7a6455; font-size: .9rem; line-height: 1.55; white-space: pre-line; margin-bottom: 0; }
        .potato-dialog .pd-actions { display: flex; gap: 10px; justify-content: center; margin-top: 24px; }
        .potato-dialog .pd-btn { flex: 1; border-radius: 12px; font-weight: 700; font-size: .88rem; padding: 10px 14px; border: 0; }
        .potato-dialog .pd-btn-batal { background: #f3ece4; color: #3d2314; }
        .potato-dialog .pd-btn-batal:hover { background: #eadfd2; }
        .potato-dialog .pd-btn-ya { background: linear-gradient(135deg, #fcae1e, #f7931e); color: #3a1f0a; }
        .potato-dialog .pd-btn-ya:hover { filter: brightness(.95); color: #3a1f0a; }
        .potato-dialog .pd-btn-ya.btn-danger-potato { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
        .potato-dialog .pd-btn-ya.btn-danger-potato:hover { color: #fff; }
    </style>
    @stack('styles')
</head>

<body>
    @php
        $fotoUser = auth()->user()->foto_url;
        $alertStok = $alertStok ?? collect();
        $notifPermintaan = $notifPermintaan ?? collect();
        $jumlahPermintaan = $jumlahPermintaan ?? 0;
        $totalLonceng = $alertStok->count() + $jumlahPermintaan;
    @endphp

    @yield('sidebar')
    <div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

    <div class="main-wrapper">
        <header class="navbar-custom">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="icon-btn" id="btn-toggle-sidebar" aria-label="Buka atau kecilkan menu samping">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
                <div class="topbar-date d-none d-sm-block">
                    <b>{{ now()->translatedFormat('l, d F Y') }}</b>
                    @yield('topbar_sub')
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="icon-btn d-none d-md-inline-flex" title="Layar penuh" onclick="toggleFullScreen()">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>

                {{-- Lonceng stok: isinya menu & bumbu yang menipis/habis --}}
                <div class="dropdown">
                    <button type="button" class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Peringatan stok">
                        <i class="bi bi-bell"></i>
                        @if ($totalLonceng)
                            <span class="count">{{ $totalLonceng }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end pop" style="min-width: 300px;">
                        @if ($notifPermintaan->count())
                            <div class="pop-title">Pengajuan stok</div>
                            @foreach ($notifPermintaan as $n)
                                <a href="{{ $n['url'] }}" class="alert-row text-decoration-none text-reset">
                                    <span class="dot {{ $n['warna'] }}" @if($n['warna'] === 'aman') style="background:#198754" @endif></span>
                                    <div class="flex-grow-1">
                                        <b>{{ $n['judul'] }}</b>
                                        <small>{{ $n['sub'] }}</small>
                                    </div>
                                </a>
                            @endforeach
                            @if ($jumlahPermintaan > $notifPermintaan->count())
                                <div class="text-center text-muted small pt-1">+{{ $jumlahPermintaan - $notifPermintaan->count() }} lainnya</div>
                            @endif
                            <hr class="my-2 opacity-25">
                        @endif
                        <div class="pop-title">Stok perlu perhatian</div>
                        @forelse ($alertStok->take(8) as $a)
                            <div class="alert-row">
                                <span class="dot {{ $a['status'] }}"></span>
                                <div class="flex-grow-1">
                                    <b>{{ $a['nama'] }}</b>
                                    <small>{{ $a['jenis'] }} · {{ $a['status'] === 'habis' ? 'habis' : 'sisa ' . $a['stok'] }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted small py-3"><i class="bi bi-check-circle text-success"></i> Semua stok aman.</div>
                        @endforelse
                        @if ($alertStok->count() > 8)
                            <div class="text-center text-muted small pt-1">+{{ $alertStok->count() - 8 }} lainnya</div>
                        @endif
                        @hasSection('stok_url')
                            <hr class="my-2 opacity-25">
                            <a href="@yield('stok_url')" class="dropdown-item"><i class="bi bi-boxes"></i> Lihat stok</a>
                        @endif
                    </div>
                </div>

                <div class="dropdown">
                    <div class="user-chip" data-bs-toggle="dropdown" aria-expanded="false" role="button" tabindex="0">
                        <img src="{{ $fotoUser }}" alt="">
                        <b class="d-none d-md-inline">{{ auth()->user()->name }}</b>
                        <i class="bi bi-chevron-down text-muted small"></i>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end pop mt-2">
                        <div class="pop-title">Masuk sebagai {{ auth()->user()->role === 'admin' ? 'Admin' : 'Kasir' }}</div>
                        <a href="@yield('profil_url')" class="dropdown-item"><i class="bi bi-person-circle"></i> Profil saya</a>
                        <hr class="my-2 opacity-25">
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item danger w-100 border-0 bg-transparent">
                                <i class="bi bi-box-arrow-right"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        @yield('content')
    </div>

    {{-- Dialog bertema Potato: dipakai untuk SEMUA konfirmasi (hapus, reset, isi stok, dll) dan pengganti alert() --}}
    <div class="modal fade" id="modalPotato" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content potato-dialog">
                <div class="modal-body text-center">
                    <div class="pd-icon" id="potatoIcon"><i class="bi bi-question-lg"></i></div>
                    <h5 class="pd-title" id="potatoJudul">Konfirmasi</h5>
                    <p class="pd-msg" id="potatoPesan"></p>
                    <div class="pd-actions">
                        <button type="button" class="btn pd-btn pd-btn-batal" id="potatoBatal">Batal</button>
                        <button type="button" class="btn pd-btn pd-btn-ya" id="potatoYa">Ya</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets_dashboard/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets_dashboard/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        function toggleFullScreen() {
            if (!document.fullscreenElement) document.documentElement.requestFullscreen();
            else if (document.exitFullscreen) document.exitFullscreen();
        }

        (function () {
            const body = document.body;
            const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;
            try { if (!isMobile() && localStorage.getItem('potato.sidebar') === 'mini') body.classList.add('sidebar-collapsed'); } catch (e) {}

            document.getElementById('btn-toggle-sidebar').addEventListener('click', function () {
                if (isMobile()) { body.classList.toggle('sidebar-open'); return; }
                body.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('potato.sidebar', body.classList.contains('sidebar-collapsed') ? 'mini' : 'full'); } catch (e) {}
            });
        })();
    </script>
    <script>
        /*
         * Dialog Potato.
         *  - potatoDialog({tipe, judul, pesan, ya, batal, tanpaBatal}) -> Promise<boolean>
         *  - window.alert() otomatis jadi dialog yang sama
         *  - Form dengan data-konfirmasi="..." (atau method DELETE) minta konfirmasi dulu sebelum dikirim.
         *    Opsional: data-judul, data-ya, data-tipe (bahaya | tanya)
         */
        (function () {
            const el = document.getElementById('modalPotato');
            if (!el || !window.bootstrap) return;
            const modal = new bootstrap.Modal(el);
            const $ = id => document.getElementById(id);
            const TIPE = {
                bahaya: { cls: 'bahaya', ikon: 'bi-trash3', ya: 'btn-danger-potato' },
                tanya:  { cls: 'tanya',  ikon: 'bi-question-lg', ya: '' },
                info:   { cls: 'info',   ikon: 'bi-exclamation-lg', ya: '' },
            };
            let selesai = null;

            window.potatoDialog = function (o) {
                o = o || {};
                const t = TIPE[o.tipe] || TIPE.tanya;
                $('potatoIcon').className = 'pd-icon ' + t.cls;
                $('potatoIcon').innerHTML = '<i class="bi ' + t.ikon + '"></i>';
                $('potatoJudul').textContent = o.judul || 'Konfirmasi';
                $('potatoPesan').textContent = o.pesan || '';
                $('potatoPesan').classList.toggle('d-none', !o.pesan);
                $('potatoYa').textContent = o.ya || (o.tanpaBatal ? 'Mengerti' : 'Ya, lanjutkan');
                $('potatoYa').className = 'btn pd-btn pd-btn-ya ' + t.ya;
                $('potatoBatal').textContent = o.batal || 'Batal';
                $('potatoBatal').classList.toggle('d-none', !!o.tanpaBatal);

                return new Promise(function (resolve) {
                    if (selesai) selesai(false);
                    selesai = resolve;
                    modal.show();
                });
            };

            function tutup(hasil) {
                const r = selesai; selesai = null;
                modal.hide();
                if (r) r(hasil);
            }
            $('potatoYa').addEventListener('click', function () { tutup(true); });
            $('potatoBatal').addEventListener('click', function () { tutup(false); });
            el.addEventListener('hidden.bs.modal', function () { if (selesai) { const r = selesai; selesai = null; r(false); } });

            // alert() bawaan browser diganti dialog Potato
            window.alert = function (pesan) {
                potatoDialog({ tipe: 'info', judul: 'Perhatian', pesan: String(pesan), tanpaBatal: true });
            };

            // Form yang minta konfirmasi
            document.addEventListener('submit', function (e) {
                const f = e.target;
                if (!(f instanceof HTMLFormElement) || f.dataset.sudahKonfirmasi) return;
                const m = f.querySelector('input[name="_method"]');
                const hapus = m && String(m.value).toUpperCase() === 'DELETE';
                if (!hapus && !f.dataset.konfirmasi) return;
                if (hapus && f.closest('.modal') && !f.dataset.konfirmasi) return;

                e.preventDefault();
                e.stopImmediatePropagation();
                potatoDialog({
                    tipe: f.dataset.tipe || (hapus ? 'bahaya' : 'tanya'),
                    judul: f.dataset.judul || (hapus ? 'Yakin ingin menghapus?' : 'Konfirmasi'),
                    pesan: f.dataset.konfirmasi || (hapus ? 'Data yang dihapus tidak bisa dikembalikan.' : ''),
                    ya: f.dataset.ya || (hapus ? 'Ya, Hapus' : 'Ya, lanjutkan'),
                }).then(function (ok) {
                    if (!ok) return;
                    f.dataset.sudahKonfirmasi = '1';
                    f.submit();
                });
            }, true);
        })();
    </script>
    @stack('scripts')
</body>

</html>
