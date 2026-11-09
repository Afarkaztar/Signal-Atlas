<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
<div class="page-loader"><div class="loader-ring"><i></i><i></i><i></i></div></div>
<div class="aurora"><span></span><span></span><span></span></div>
<div class="auth-wrap">
    <div class="auth-art">
        <canvas id="authCanvas"></canvas>
        <a href="{{ route('home') }}" class="brand" style="padding:0">
            <span class="brand-mark"><i data-lucide="activity"></i></span>
            <span><div class="brand-name">SIGNALATLAS</div><div class="brand-sub">ASEAN Connectivity</div></span>
        </a>
        <div class="stagger">
            <span class="eyebrow">@yield('art-eyebrow')</span>
            <h1 style="font-size:clamp(34px,3.6vw,52px);letter-spacing:-.045em;line-height:1.08;margin:16px 0">@yield('art-title')</h1>
            <p class="muted" style="max-width:460px;font-size:15px">@yield('art-text')</p>
            <div class="grid g-3 mt-3" style="max-width:520px">
                @foreach ([['radio-tower', 'Lapor', 'var(--mint)'], ['shield-check', 'Verifikasi', 'var(--blue)'], ['hard-hat', 'Bangun', 'var(--violet)']] as [$i, $t, $c])
                    <div class="card" style="padding:16px;box-shadow:none;--c:{{ $c }}"><div class="kpi-icon" style="margin-bottom:8px"><i data-lucide="{{ $i }}"></i></div><b>{{ $t }}</b></div>
                @endforeach
            </div>
        </div>
        <span class="dim mono" style="font-size:11px">Kelompok 11 · Pemrograman Web · IPB University</span>
    </div>
    <div class="auth-form">
        <div class="auth-card stagger">
            @yield('form')
        </div>
    </div>
</div>
<div class="toasts"></div>
<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
<script>
    // Animasi sinyal radar di panel kiri
    const cv = document.getElementById('authCanvas'), ctx = cv.getContext('2d');
    const fit = () => { cv.width = cv.offsetWidth; cv.height = cv.offsetHeight; }; fit(); addEventListener('resize', fit);
    const nodes = Array.from({ length: 26 }, () => ({ x: Math.random(), y: Math.random(), p: Math.random() * 6 }));
    (function loop(t) {
        ctx.clearRect(0, 0, cv.width, cv.height);
        nodes.forEach((n, i) => {
            const x = n.x * cv.width, y = n.y * cv.height, r = ((t / 1000 + n.p) % 3) / 3;
            ctx.strokeStyle = `rgba(${i % 3 ? '96,225,189' : '185,154,255'},${(1 - r) * .4})`;
            ctx.beginPath(); ctx.arc(x, y, r * 46, 0, Math.PI * 2); ctx.stroke();
            ctx.fillStyle = i % 3 ? '#60e1bd' : '#b99aff'; ctx.beginPath(); ctx.arc(x, y, 2, 0, Math.PI * 2); ctx.fill();
        });
        requestAnimationFrame(loop);
    })(0);
    document.querySelectorAll('[data-toggle-pass]').forEach(b => b.onclick = () => { const i = document.getElementById(b.dataset.togglePass); i.type = i.type === 'password' ? 'text' : 'password'; });
</script>
@stack('scripts')
</body>
</html>
