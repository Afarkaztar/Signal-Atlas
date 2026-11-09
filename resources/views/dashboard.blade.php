@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    [$badgeName, $badgeColor] = $user->badge();
    $hour = now()->hour;
    $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    $nextLevel = collect([40, 100, 200])->first(fn ($p) => $p > $user->points);
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">{{ $user->roleLabel() }}</span>
        <h1 class="page-title">{{ $greet }}, {{ explode(' ', $user->name)[0] }} 👋</h1>
        <p class="page-sub">
            @if ($user->isStaff())
                Ada <b style="color:var(--yellow)">{{ $kpi['pending'] }} laporan</b> menunggu verifikasi dan <b style="color:var(--red)">{{ $kpi['critical'] }} laporan kritis</b> yang belum terselesaikan.
            @else
                Pantau status laporan Anda dan lihat bagaimana kontribusi Anda berdampak pada pembangunan infrastruktur.
            @endif
        </p>
    </div>
    <div class="head-actions">
        @if ($user->isStaff())
            <a href="{{ route('verification.index') }}" class="btn"><i data-lucide="shield-check"></i> Verifikasi</a>
            <a href="{{ route('projects.create') }}" class="btn btn-violet"><i data-lucide="hard-hat"></i> Usulkan Proyek</a>
        @else
            <a href="{{ route('speedtest') }}" class="btn"><i data-lucide="gauge"></i> Speed Test</a>
            <a href="{{ route('reports.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Laporan Baru</a>
        @endif
    </div>
</div>

<div class="grid g-4 stagger mb-3">
    @foreach ([
        [$user->isStaff() ? 'Total laporan' : 'Laporan saya', $kpi['total'], 'radio-tower', 'var(--mint)', 'Semua status'],
        ['Menunggu verifikasi', $kpi['pending'], 'hourglass', 'var(--yellow)', 'Perlu ditinjau analis'],
        ['Terverifikasi', $kpi['verified'], 'badge-check', 'var(--blue)', 'Masuk analisis prioritas'],
        ['Terselesaikan', $kpi['resolved'], 'party-popper', 'var(--violet)', 'Lewat proyek infrastruktur'],
    ] as [$label, $value, $icon, $color, $foot])
        <div class="card kpi hover spotlight" style="--c:{{ $color }}">
            <div class="kpi-icon"><i data-lucide="{{ $icon }}"></i></div>
            <div class="kpi-label">{{ $label }}</div>
            <div class="kpi-value" data-count="{{ $value }}">0</div>
            <div class="kpi-foot">{{ $foot }}</div>
        </div>
    @endforeach
</div>

<div class="grid g-main mb-3">
    <div class="card" data-reveal>
        <div class="card-head">
            <div><h3 class="card-title"><i data-lucide="trending-up"></i> Tren laporan 6 bulan</h3><div class="card-sub">Laporan masuk vs. terselesaikan</div></div>
            <a href="{{ route('reports.index') }}" class="btn btn-sm">Semua laporan <i data-lucide="arrow-right"></i></a>
        </div>
        <div class="chart-box"><canvas id="trendChart"></canvas></div>
    </div>

    @if ($user->isStaff())
        <div class="card" data-reveal style="--d:.1s">
            <div class="card-head"><div><h3 class="card-title"><i data-lucide="pie-chart"></i> Status proyek</h3><div class="card-sub">Distribusi pipeline infrastruktur</div></div></div>
            <div class="chart-box sm"><canvas id="projectChart"></canvas></div>
        </div>
    @else
        <div class="card glow-border" data-reveal style="--d:.1s">
            <div class="card-head"><div><h3 class="card-title"><i data-lucide="award"></i> Reputasi kontributor</h3><div class="card-sub">Peringkat #{{ $rank }} dari seluruh kontributor</div></div></div>
            <div class="row" style="gap:20px">
                <div class="score-ring lg" data-ring="{{ $nextLevel ? min(100, $user->points / $nextLevel * 100) : 100 }}" style="--c:{{ $badgeColor }}"><b data-count="{{ $user->points }}">0</b></div>
                <div>
                    <span class="badge no-dot" style="color:{{ $badgeColor }};border-color:{{ $badgeColor }}55;font-size:13px">★ {{ $badgeName }}</span>
                    <p class="muted" style="font-size:12.5px;margin:10px 0 0">
                        @if ($nextLevel) <b style="color:var(--text)">{{ $nextLevel - $user->points }} poin lagi</b> menuju level berikutnya. @else Level tertinggi tercapai! @endif
                        <br>+{{ config('signal.points.verified') }} poin per laporan terverifikasi, +{{ config('signal.points.resolved') }} saat terselesaikan.
                    </p>
                </div>
            </div>
            <div class="chart-box sm mt-2" style="height:150px"><canvas id="issueChart"></canvas></div>
        </div>
    @endif
</div>

<div class="grid g-3 mb-3">
    <div class="card span-2" data-reveal>
        <div class="card-head">
            <div><h3 class="card-title"><i data-lucide="list"></i> {{ $user->isStaff() ? 'Laporan terbaru' : 'Laporan terakhir saya' }}</h3></div>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-ghost">Lihat semua</a>
        </div>
        @if ($recentReports->isEmpty())
            <div class="empty"><div class="empty-icon"><i data-lucide="inbox"></i></div><h3>Belum ada laporan</h3><p>Mulai dengan menjalankan speed test atau membuat laporan.</p><a href="{{ route('reports.create') }}" class="btn btn-primary mt-1">Buat laporan</a></div>
        @else
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Kode</th><th>Wilayah</th><th>Masalah</th><th>Skor</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($recentReports as $r)
                    <tr onclick="location='{{ route('reports.show', $r) }}'" style="cursor:pointer">
                        <td class="mono" style="font-size:12px">{{ $r->code }}</td>
                        <td><div class="cell-main">{{ $r->region->name }}</div><div class="cell-sub">{{ $r->region->country->flag() }} {{ $r->region->country->name }}</div></td>
                        <td>{{ $r->issueLabel() }}</td>
                        <td><span class="badge b-{{ $r->severity_level }}">{{ $r->severity_score }}</span></td>
                        <td><span class="badge b-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div>

    <div class="card" data-reveal style="--d:.1s">
        <div class="card-head"><div><h3 class="card-title"><i data-lucide="trophy"></i> Papan peringkat</h3><div class="card-sub">Kontributor teratas</div></div></div>
        <div class="list">
            @foreach ($leaderboard as $i => $c)
                @php [$bn, $bc] = $c->badge(); @endphp
                <div class="list-item">
                    <span class="rank-no {{ $i < 3 ? 'top' : '' }}">{{ ['🥇', '🥈', '🥉'][$i] ?? '0'.($i + 1) }}</span>
                    <span class="avatar" style="width:32px;height:32px;font-size:11px">{{ $c->initials() }}</span>
                    <div class="list-body"><div class="list-title">{{ $c->name }} @if ($c->id === $user->id)<span class="dim">(Anda)</span>@endif</div><div class="list-sub" style="color:{{ $bc }}">{{ $bn }}</div></div>
                    <b class="mono">{{ $c->points }}</b>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid g-2">
    @if ($user->isStaff())
        <div class="card" data-reveal>
            <div class="card-head">
                <div><h3 class="card-title"><i data-lucide="target"></i> Wilayah prioritas</h3><div class="card-sub">Connectivity Gap Score tertinggi</div></div>
                <a href="{{ route('priorities.index') }}" class="btn btn-sm">Analisis <i data-lucide="arrow-right"></i></a>
            </div>
            @foreach ($topPriorities as $p)
                <div class="bar-row" style="grid-template-columns:150px 1fr 48px">
                    <span><b>{{ $p['region']->name }}</b><br><span class="dim" style="font-size:11px">{{ $p['region']->country->name }}</span></span>
                    <div class="progress"><span data-width="{{ $p['gap_score'] }}" style="--c:{{ ['low' => '#60e1bd', 'medium' => '#f2cc72', 'high' => '#ffb27a', 'critical' => '#ff8f83'][$p['level']] }}"></span></div>
                    <b class="mono" style="text-align:right">{{ $p['gap_score'] }}</b>
                </div>
            @endforeach
        </div>
    @else
        <div class="card" data-reveal>
            <div class="card-head"><div><h3 class="card-title"><i data-lucide="hard-hat"></i> Proyek aktif</h3><div class="card-sub">Pembangunan yang sedang berjalan</div></div><a href="{{ route('projects.index') }}" class="btn btn-sm">Semua</a></div>
            @foreach ($activeProjects as $p)
                <a href="{{ route('projects.show', $p) }}" class="list-item">
                    <span class="list-icon" style="color:var(--violet)"><i data-lucide="construction"></i></span>
                    <div class="list-body"><div class="list-title">{{ $p->title }}</div><div class="progress thin mt-1"><span data-width="{{ $p->progress }}"></span></div></div>
                    <b class="mono">{{ $p->progress }}%</b>
                </a>
            @endforeach
        </div>
    @endif

    <div class="card" data-reveal style="--d:.1s">
        <div class="card-head"><div><h3 class="card-title"><i data-lucide="activity"></i> Aktivitas terbaru</h3></div>@if ($user->isStaff())<a href="{{ route('activity.index') }}" class="btn btn-sm btn-ghost">Log lengkap</a>@endif</div>
        <div class="timeline">
            @forelse ($activities as $a)
                @php [$icon, $color] = $a->visual(); @endphp
                <div class="tl-item" style="--c:{{ $color }}">
                    <div class="tl-title">{{ $a->description }}</div>
                    <div class="tl-meta">{{ $a->user?->name ?? 'Sistem' }} · {{ $a->created_at->diffForHumans() }}</div>
                </div>
            @empty
                <p class="muted">Belum ada aktivitas.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const t = @json($trend);
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: { labels: t.labels, datasets: [
            { label: 'Laporan masuk', data: t.submitted, borderColor: SA.colors.mint, backgroundColor: SA.gradient(ctx, SA.colors.mint), fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#070c17', pointBorderWidth: 2 },
            { label: 'Terselesaikan', data: t.resolved, borderColor: SA.colors.violet, backgroundColor: SA.gradient(ctx, SA.colors.violet), fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#070c17', pointBorderWidth: 2 },
        ] },
        options: { interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    @if ($user->isStaff())
        const ps = @json($projectStatus), labels = @json(config('signal.project_statuses'));
        const order = ['proposed', 'approved', 'in_progress', 'on_hold', 'completed', 'rejected', 'cancelled'];
        const colors = { proposed: SA.colors.yellow, approved: SA.colors.blue, in_progress: SA.colors.violet, on_hold: SA.colors.orange, completed: SA.colors.mint, rejected: SA.colors.red, cancelled: '#5e6d84' };
        new Chart(document.getElementById('projectChart'), {
            type: 'doughnut',
            data: { labels: order.map(k => labels[k]), datasets: [{ data: order.map(k => ps[k] || 0), backgroundColor: order.map(k => colors[k]), borderColor: '#0b1222', borderWidth: 3, hoverOffset: 10 }] },
            options: { cutout: '68%', plugins: { legend: { position: 'right' } } },
        });
    @else
        const ib = @json($issueBreakdown), il = @json(config('signal.issue_types'));
        new Chart(document.getElementById('issueChart'), {
            type: 'bar',
            data: { labels: Object.keys(il).map(k => il[k].split(' (')[0]), datasets: [{ data: Object.keys(il).map(k => ib[k] || 0), backgroundColor: SA.series.map(c => c + 'bb'), borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 9 } } }, y: { display: false } } },
        });
    @endif
});
</script>
@endpush
