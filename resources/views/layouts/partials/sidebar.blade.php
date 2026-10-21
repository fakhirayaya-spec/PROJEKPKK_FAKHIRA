{{-- Param: $home (url logo), $roleLabel, $groups (judul => [[route, ikon, label, pola-aktif, badge?]]) --}}
<aside class="sidebar-wrapper">
    <a href="{{ $home }}" class="sidebar-brand">
        <img src="{{ asset('asset_login/images/logopotato.jpg') }}" alt="Logo Potato Curly Crunch">
        <span class="brand-text">
            <span class="brand-name d-block">Potato Curly</span>
            <span class="brand-role">{{ $roleLabel }}</span>
        </span>
    </a>

    <nav class="sidebar-scroll">
        @foreach ($groups as $judul => $items)
            <div class="sidebar-group-title">{{ $judul }}</div>
            <ul class="sidebar-menu-list">
                @foreach ($items as $it)
                    <li>
                        <a href="{{ route($it[0]) }}" title="{{ $it[2] }}"
                            class="sidebar-menu-link {{ request()->routeIs($it[3]) ? 'active' : '' }}">
                            <i class="bi {{ $it[1] }}"></i>
                            <span>{{ $it[2] }}</span>
                            @if (!empty($it[4]))
                                <span class="pill">{{ $it[4] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="sidebar-profile">
        <img src="{{ $fotoUser }}" alt="">
        <div class="who">
            <b class="text-truncate">{{ auth()->user()->name }}</b>
            <small>{{ auth()->user()->email }}</small>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="logout-mini" title="Keluar"><i class="bi bi-box-arrow-right"></i></button>
        </form>
    </div>
</aside>
