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

    @stack('styles')
</head>

<body>
    @php
        $fotoUser = auth()->user()->avatar
            ? asset('storage/' . auth()->user()->avatar)
            : asset('assets_dashboard/images/SEBIN.jpg');
        $alertStok = $alertStok ?? collect();
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
                        @if ($alertStok->count())
                            <span class="count">{{ $alertStok->count() }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end pop" style="min-width: 300px;">
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
    @stack('scripts')
</body>

</html>
