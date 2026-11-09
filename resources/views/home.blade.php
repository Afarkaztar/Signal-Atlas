<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
<div class="page-loader"><div class="loader-ring"><i></i><i></i><i></i></div></div>
<div class="aurora"><span></span><span></span><span></span></div>

<nav class="landing-nav" id="lnav">
    <a href="{{ route('home') }}" class="brand" style="padding:0">
        <span class="brand-mark"><i data-lucide="activity"></i></span>
        <span class="brand-name">SIGNALATLAS</span>
    </a>
    <div class="links">
        <a href="#fitur">Fitur</a>
        <a href="#alur">Alur Kerja</a>
        <a href="#indeks">Indeks ASEAN</a>
        <a href="{{ route('map') }}">Peta</a>
        <a href="{{ route('speedtest') }}">Speed Test</a>
    </div>
    <div class="row" style="margin-left:auto">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Mulai Berkontribusi <i data-lucide="arrow-right"></i></a>
        @endauth
    </div>
</nav>

{{-- ================= HERO ================= --}}
<section class="hero">
    <canvas id="network"></canvas>
    <div class="stagger">
        <span class="hero-badge"><span>BARU</span> Speed test langsung jadi laporan publik</span>
        <h1>Petakan <span class="grad-text">kesenjangan</span><br>digital Asia Tenggara.</h1>
        <p class="lead">
            SignalAtlas menghubungkan warga, analis, dan pemangku kebijakan: <b style="color:var(--text)">laporkan</b> masalah sinyal,
            <b style="color:var(--text)">verifikasi</b> bersama, lalu ubah data menjadi <span class="typed" id="typed" style="color:var(--mint);font-weight:700"></span>
        </p>
        <div class="hero-cta">
            <a href="{{ auth()->check() ? route('reports.create') : route('register') }}" class="btn btn-primary btn-lg"><i data-lucide="radio-tower"></i> Laporkan Sinyal</a>
            <a href="{{ route('speedtest') }}" class="btn btn-lg"><i data-lucide="gauge"></i> Tes Kecepatan</a>
            <a href="{{ route('map') }}" class="btn btn-lg btn-ghost"><i data-lucide="map"></i> Lihat peta</a>
        </div>
        <div class="row mt-3 wrap" style="gap:22px;color:var(--muted);font-size:12.5px">
            <span class="row" style="gap:6px"><i data-lucide="shield-check" style="color:var(--mint);width:16px"></i> Diverifikasi analis</span>
            <span class="row" style="gap:6px"><i data-lucide="globe-2" style="color:var(--blue);width:16px"></i> {{ $stats['countries'] }} negara</span>
            <span class="row" style="gap:6px"><i data-lucide="file-spreadsheet" style="color:var(--violet);width:16px"></i> Data terbuka CSV</span>
        </div>
    </div>

    <div class="hero-visual">
        <div class="globe-wrap">
            <div class="globe-ring r2"><span class="sat"></span></div>
            <div class="globe-ring r1"><span class="sat"></span></div>
            <div class="globe"></div>
        </div>
        <div class="float-card" style="top:8%;left:2%">
            <span class="muted">Laporan masuk</span>
            <b class="grad-text" data-count="{{ $stats['reports'] }}">0</b>
            <span class="badge b-resolved" style="margin-top:6px">{{ $stats['resolved'] }} terselesaikan</span>
        </div>
        <div class="float-card" style="bottom:14%;left:-2%;animation-delay:-2s">
            <div class="row"><span class="kpi-icon" style="--c:var(--violet);margin:0;width:36px;height:36px"><i data-lucide="hard-hat"></i></span>
                <div><span class="muted">Proyek infrastruktur</span><b data-count="{{ $stats['projects'] }}">0</b></div></div>
        </div>
        <div class="float-card" style="top:30%;right:0;animation-delay:-4s">
            <span class="muted">Indeks tertinggi</span>
            @if ($top = $ranking->first())
                <b>{{ $top['country']->flag() }} {{ $top['score'] }}</b>
                <span class="muted" style="font-size:11px">{{ $top['country']->name }} · Grade {{ $top['grade'][0] }}</span>
            @endif
        </div>
    </div>
</section>

<div class="marquee">
    <div class="marquee-track">
        @foreach (range(1, 2) as $_)
            @foreach (['🇮🇩 Indonesia', '🇲🇾 Malaysia', '🇵🇭 Filipina', '🇸🇬 Singapura', '🇹🇭 Thailand', '🇻🇳 Vietnam', '🇧🇳 Brunei', '🇰🇭 Kamboja', '🇱🇦 Laos', '🇲🇲 Myanmar', '🇹🇱 Timor-Leste'] as $c)
                <span>{{ $c }} <i data-lucide="signal" style="width:14px;color:var(--mint)"></i></span>
            @endforeach
        @endforeach
    </div>
</div>

{{-- ================= STATS ================= --}}
<section class="section" style="padding-top:70px;padding-bottom:40px">
    <div class="grid g-4">
        @foreach ([
            ['Laporan publik', $stats['reports'], 'var(--mint)'],
            ['Terverifikasi', $stats['verified'], 'var(--blue)'],
            ['Kontributor aktif', $stats['contributors'], 'var(--violet)'],
            ['Masalah terselesaikan', $stats['resolved'], 'var(--yellow)'],
        ] as $i => [$label, $value, $color])
            <div class="card big-stat hover spotlight" data-reveal style="--d:{{ $i * .1 }}s">
                <b style="color:{{ $color }}" data-count="{{ $value }}">0</b>
                <span>{{ $label }}</span>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= FEATURES ================= --}}
<section class="section" id="fitur">
    <div class="section-head" data-reveal>
        <span class="eyebrow">Fitur utama</span>
        <h2>Satu platform, dari <span class="grad-text">sinyal</span> sampai <span class="grad-text">kebijakan</span></h2>
        <p>Bukan sekadar dashboard — setiap pengguna dapat berinteraksi, mengirim data, dan melihat dampaknya secara nyata.</p>
    </div>
    <div class="grid g-3">
        @foreach ([
            ['radio-tower', 'Pelaporan Sinyal Publik', 'Kirim laporan blank spot, sinyal lambat, atau gangguan lengkap dengan titik lokasi peta & foto bukti.', 'var(--mint)'],
            ['gauge', 'Speed Test Terintegrasi', 'Ukur ping, unduh, dan unggah langsung di browser — hasilnya bisa dikirim sebagai laporan dengan satu klik.', 'var(--blue)'],
            ['shield-check', 'Verifikasi Berjenjang', 'Analis meninjau setiap laporan (setujui/tolak, massal) dan kontributor mendapat poin reputasi.', 'var(--violet)'],
            ['target', 'Skor Prioritas Otomatis', 'Algoritma Connectivity Gap Score menghitung wilayah paling mendesak & merekomendasikan jenis infrastruktur.', 'var(--yellow)'],
            ['hard-hat', 'Manajemen Proyek', 'Kanban drag-and-drop dengan state machine: diusulkan → disetujui → dikerjakan → selesai.', 'var(--orange)'],
            ['file-spreadsheet', 'Impor & Ekspor Data', 'Impor CSV dengan validasi per baris & dry-run, ekspor CSV/JSON, serta laporan eksekutif siap cetak PDF.', 'var(--red)'],
        ] as $i => [$icon, $title, $desc, $color])
            <div class="card feature hover spotlight glow-border" data-reveal style="--d:{{ ($i % 3) * .1 }}s;--c:{{ $color }}">
                <div class="feature-icon"><i data-lucide="{{ $icon }}"></i></div>
                <h3>{{ $title }}</h3>
                <p>{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= FLOW ================= --}}
<section class="section" id="alur" style="background:linear-gradient(180deg,transparent,rgba(16,26,44,.5),transparent)">
    <div class="section-head" data-reveal>
        <span class="eyebrow">Business flow</span>
        <h2>Bagaimana laporan Anda menjadi menara baru</h2>
        <p>Setiap langkah tercatat di log audit dan memicu notifikasi otomatis kepada pihak terkait.</p>
    </div>
    <div class="flow">
        @foreach ([
            ['send', 'Lapor', 'Warga mengirim laporan / hasil speed test', 'var(--mint)'],
            ['shield-check', 'Verifikasi', 'Analis memvalidasi & memberi poin', 'var(--blue)'],
            ['calculator', 'Analisis', 'Sistem menghitung Gap Score wilayah', 'var(--violet)'],
            ['hard-hat', 'Proyek', 'Admin menyetujui & memantau progres', 'var(--yellow)'],
            ['party-popper', 'Selesai', 'Laporan otomatis ditandai terselesaikan', 'var(--orange)'],
        ] as $i => [$icon, $title, $desc, $color])
            <div class="flow-step" data-reveal style="--d:{{ $i * .12 }}s;--c:{{ $color }}">
                <div class="flow-num"><i data-lucide="{{ $icon }}"></i><small>{{ $i + 1 }}</small></div>
                <h4>{{ $title }}</h4>
                <p>{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= RANKING + LATEST ================= --}}
<section class="section" id="indeks">
    <div class="grid g-main">
        <div class="card" data-reveal>
            <div class="card-head">
                <div><h3 class="card-title"><i data-lucide="trophy"></i> Digital Connectivity Index</h3><div class="card-sub">5 negara teratas · indeks komposit 0–100</div></div>
                <a href="{{ route('analytics') }}" class="btn btn-sm">Lihat semua <i data-lucide="arrow-up-right"></i></a>
            </div>
            <div class="list">
                @foreach ($ranking as $i => $row)
                    <div class="list-item">
                        <span class="rank-no {{ $i === 0 ? 'top' : '' }}">0{{ $i + 1 }}</span>
                        <span style="font-size:24px">{{ $row['country']->flag() }}</span>
                        <div class="list-body">
                            <div class="list-title">{{ $row['country']->name }}</div>
                            <div class="progress thin mt-1"><span data-width="{{ $row['score'] }}"></span></div>
                        </div>
                        <div style="text-align:right">
                            <div class="mono" style="font-weight:700;font-size:15px">{{ $row['score'] }}</div>
                            <span class="badge no-dot" style="color:{{ $row['grade'][2] }};border-color:{{ $row['grade'][2] }}44">Grade {{ $row['grade'][0] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card" data-reveal style="--d:.15s">
            <div class="card-head"><div><h3 class="card-title"><i data-lucide="radio"></i> Laporan terbaru</h3><div class="card-sub">Terverifikasi oleh analis</div></div></div>
            <div class="list">
                @foreach ($latest as $r)
                    <div class="list-item">
                        <span class="list-icon" style="color:var(--{{ ['low' => 'mint', 'medium' => 'yellow', 'high' => 'orange', 'critical' => 'red'][$r->severity_level] }})"><i data-lucide="{{ $r->issue_type === 'blank_spot' ? 'wifi-off' : 'signal-low' }}"></i></span>
                        <div class="list-body">
                            <div class="list-title">{{ $r->region->name }}, {{ $r->region->country->name }}</div>
                            <div class="list-sub">{{ $r->issueLabel() }} · {{ $r->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="badge b-{{ $r->severity_level }}">{{ $r->severity_score }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="cta-band" data-reveal>
    <span class="eyebrow">Bergabung sekarang</span>
    <h2 style="font-size:clamp(28px,3.4vw,42px);letter-spacing:-.04em;margin:14px 0 10px">Suara Anda menentukan di mana<br>menara berikutnya dibangun.</h2>
    <p class="muted" style="max-width:560px;margin:0 auto 26px">Daftar gratis, kirim laporan pertama Anda, kumpulkan poin, dan naik peringkat dari <b style="color:var(--mint)">Scout</b> hingga <b style="color:var(--yellow)">Sentinel</b>.</p>
    <div class="row" style="justify-content:center;flex-wrap:wrap">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg"><i data-lucide="user-plus"></i> Buat akun gratis</a>
        <a href="{{ route('analytics') }}" class="btn btn-lg"><i data-lucide="bar-chart-3"></i> Jelajahi analitik</a>
    </div>
</section>

<footer class="footer" style="padding:24px 6vw">
    <span>© {{ date('Y') }} SignalAtlas · Kelompok 11 — Pemrograman Web IPB University</span>
    <span class="mono">World Bank WDI · ITU · Ookla · Laporan publik terverifikasi</span>
</footer>

<div class="toasts"></div>
<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
<script>
    // Nav saat scroll
    const nav = document.getElementById('lnav');
    addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 30));

    // Efek mengetik
    const words = ['prioritas pembangunan.', 'menara BTS baru.', 'jaringan fiber optik.', 'kebijakan berbasis data.'];
    let w = 0, c = 0, del = false; const el = document.getElementById('typed');
    (function type() {
        const word = words[w];
        el.textContent = word.slice(0, c);
        if (!del && c === word.length) { del = true; return setTimeout(type, 1800); }
        if (del && c === 0) { del = false; w = (w + 1) % words.length; }
        c += del ? -1 : 1;
        setTimeout(type, del ? 35 : 70);
    })();

    // Partikel jaringan interaktif
    const cv = document.getElementById('network'), ctx = cv.getContext('2d');
    let pts = [], mouse = { x: -999, y: -999 };
    const resize = () => {
        cv.width = cv.offsetWidth * devicePixelRatio; cv.height = cv.offsetHeight * devicePixelRatio;
        ctx.setTransform(devicePixelRatio, 0, 0, devicePixelRatio, 0, 0);
        pts = Array.from({ length: Math.min(90, Math.floor(cv.offsetWidth / 16)) }, () => ({
            x: Math.random() * cv.offsetWidth, y: Math.random() * cv.offsetHeight,
            vx: (Math.random() - .5) * .35, vy: (Math.random() - .5) * .35,
        }));
    };
    resize(); addEventListener('resize', resize);
    cv.parentElement.addEventListener('pointermove', (e) => { const r = cv.getBoundingClientRect(); mouse = { x: e.clientX - r.left, y: e.clientY - r.top }; });
    (function draw() {
        const W = cv.offsetWidth, H = cv.offsetHeight;
        ctx.clearRect(0, 0, W, H);
        pts.forEach((p) => {
            p.x += p.vx; p.y += p.vy;
            if (p.x < 0 || p.x > W) p.vx *= -1;
            if (p.y < 0 || p.y > H) p.vy *= -1;
            const dm = Math.hypot(p.x - mouse.x, p.y - mouse.y);
            if (dm < 140) { p.x += (p.x - mouse.x) / dm; p.y += (p.y - mouse.y) / dm; }
        });
        for (let i = 0; i < pts.length; i++) {
            for (let j = i + 1; j < pts.length; j++) {
                const d = Math.hypot(pts[i].x - pts[j].x, pts[i].y - pts[j].y);
                if (d < 130) { ctx.strokeStyle = `rgba(96,225,189,${(1 - d / 130) * .22})`; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(pts[i].x, pts[i].y); ctx.lineTo(pts[j].x, pts[j].y); ctx.stroke(); }
            }
            ctx.fillStyle = i % 7 === 0 ? '#b99aff' : '#60e1bd88';
            ctx.beginPath(); ctx.arc(pts[i].x, pts[i].y, i % 7 === 0 ? 2.4 : 1.6, 0, Math.PI * 2); ctx.fill();
        }
        requestAnimationFrame(draw);
    })();
</script>
</body>
</html>
