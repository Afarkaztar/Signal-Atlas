@php
    $user = auth()->user();
    $nav = [
        ['Jelajahi', [
            ['home', 'Beranda', 'home', 'home', null],
            ['map', 'Peta Konektivitas', 'map', 'map', null],
            ['analytics', 'Analitik & Indeks', 'bar-chart-3', 'analytics', null],
            ['speedtest', 'Speed Test', 'gauge', 'speedtest', null],
        ]],
    ];
    if ($user) {
        $nav[] = ['Ruang Kerja', [
            ['dashboard', 'Dashboard', 'layout-dashboard', 'dashboard', null],
            ['reports.index', 'Laporan Sinyal', 'radio-tower', 'reports.*', null],
            ['projects.index', 'Proyek Infrastruktur', 'hard-hat', 'projects.*', null],
            ['notifications.index', 'Notifikasi', 'bell', 'notifications.*', $navCounts['unread'] ? [$navCounts['unread'], 'mint'] : null],
        ]];
        if ($user->isStaff()) {
            $nav[] = ['Analis & Admin', [
                ['verification.index', 'Antrean Verifikasi', 'shield-check', 'verification.*', $navCounts['pending'] ? [$navCounts['pending'], ''] : null],
                ['priorities.index', 'Prioritas Wilayah', 'target', 'priorities.*', null],
                ['indicators.index', 'Data Indikator', 'database', 'indicators.*', null],
                ['data.index', 'Pusat Data', 'folder-sync', 'data.*', null],
                ['activity.index', 'Log Aktivitas', 'scroll-text', 'activity.*', null],
            ]];
        }
        if ($user->isAdmin()) {
            $nav[] = ['Administrasi', [
                ['users.index', 'Manajemen Pengguna', 'users', 'users.*', null],
            ]];
        }
    }
@endphp
<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
<div class="page-loader"><div class="loader-ring"><i></i><i></i><i></i></div></div>
<div class="aurora"><span></span><span></span><span></span></div>
<div class="grid-overlay"></div>

<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark"><i data-lucide="activity"></i></span>
            <span><div class="brand-name">SIGNALATLAS</div><div class="brand-sub">ASEAN Connectivity</div></span>
        </a>

        @foreach ($nav as [$label, $items])
            <div class="nav-label">{{ $label }}</div>
            @foreach ($items as [$route, $text, $icon, $pattern, $badge])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <i data-lucide="{{ $icon }}"></i> {{ $text }}
                    @if ($badge)<span class="nav-badge {{ $badge[1] }}">{{ $badge[0] }}</span>@endif
                </a>
            @endforeach
        @endforeach

        <div class="sidebar-foot">
            @auth
                <a href="{{ route('reports.create') }}" class="btn btn-primary btn-block mb-2"><i data-lucide="plus"></i> Lapor Sinyal</a>
                <div class="user-chip">
                    <a href="{{ route('profile.edit') }}" class="avatar">{{ $user->initials() }}</a>
                    <a href="{{ route('profile.edit') }}" class="meta">
                        <div class="name">{{ $user->name }}</div>
                        <div class="role">{{ $user->roleLabel() }}</div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" data-confirm="Keluar dari SignalAtlas?" data-confirm-text="Sesi Anda akan diakhiri." data-confirm-btn="Keluar" data-tone="info">
                        @csrf
                        <button class="icon-btn" title="Keluar" style="width:34px;height:34px"><i data-lucide="log-out"></i></button>
                    </form>
                </div>
            @else
                <div class="card" style="padding:16px;box-shadow:none">
                    <div style="font-weight:700;font-size:13px">Ikut berkontribusi</div>
                    <p class="muted" style="font-size:12px;margin:4px 0 12px">Masuk untuk melaporkan sinyal & memantau proyek.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-block btn-sm"><i data-lucide="log-in"></i> Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-block btn-sm mt-1">Daftar gratis</a>
                </div>
            @endauth
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" aria-label="Menu"><i data-lucide="menu"></i></button>
            <div class="crumbs">
                <i data-lucide="activity" style="width:14px;color:var(--mint)"></i>
                <span>SignalAtlas</span><i data-lucide="chevron-right" style="width:13px"></i>
                <b>@yield('title', 'Beranda')</b>
            </div>
            <div class="top-actions">
                <button class="search-trigger" data-modal="palette"><i data-lucide="search" style="width:15px"></i><span>Cari menu & aksi…</span><kbd>Ctrl K</kbd></button>
                @auth
                    <a href="{{ route('notifications.index') }}" class="icon-btn" title="Notifikasi">
                        <i data-lucide="bell"></i>@if ($navCounts['unread'])<span class="dot" id="notifDot"></span>@endif
                    </a>
                @endauth
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>

        <footer class="footer">
            <span>© {{ date('Y') }} SignalAtlas · Kelompok 11 — Pemrograman Web IPB University</span>
            <span class="mono">Data: World Bank WDI · ITU · Ookla · Laporan publik terverifikasi</span>
        </footer>
    </div>
</div>

{{-- Command palette --}}
<div class="modal palette" id="palette">
    <div class="modal-backdrop"></div>
    <div class="modal-box">
        <div class="palette-input"><i data-lucide="search"></i><input placeholder="Ketik untuk mencari halaman atau aksi…"><kbd>ESC</kbd></div>
        <div class="palette-list">
            @foreach ($nav as [$label, $items])
                @foreach ($items as [$route, $text, $icon])
                    <a class="palette-item" href="{{ route($route) }}"><i data-lucide="{{ $icon }}"></i>{{ $text }}<small>{{ $label }}</small></a>
                @endforeach
            @endforeach
            @auth
                <a class="palette-item" href="{{ route('reports.create') }}"><i data-lucide="plus-circle"></i>Buat laporan baru<small>Aksi</small></a>
                <a class="palette-item" href="{{ route('profile.edit') }}"><i data-lucide="user-cog"></i>Pengaturan profil<small>Aksi</small></a>
                @if ($user->isStaff())
                    <a class="palette-item" href="{{ route('projects.create') }}"><i data-lucide="hard-hat"></i>Usulkan proyek baru<small>Aksi</small></a>
                    <a class="palette-item" href="{{ route('indicators.create') }}"><i data-lucide="database"></i>Tambah indikator<small>Aksi</small></a>
                @endif
            @else
                <a class="palette-item" href="{{ route('login') }}"><i data-lucide="log-in"></i>Masuk<small>Akun</small></a>
                <a class="palette-item" href="{{ route('register') }}"><i data-lucide="user-plus"></i>Daftar akun<small>Akun</small></a>
            @endauth
        </div>
    </div>
</div>

<div class="toasts"></div>
<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
