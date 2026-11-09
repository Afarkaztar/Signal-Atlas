@extends('layouts.app')
@section('title', 'Analitik & Indeks')

@section('content')
@php $user = auth()->user(); @endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Digital Connectivity Index (DCI)</span>
        <h1 class="page-title">Analitik Konektivitas Asia Tenggara</h1>
        <p class="page-sub">Menggabungkan 5 indikator resmi (World Bank · ITU · Ookla) dengan laporan publik terverifikasi.</p>
    </div>
    <div class="head-actions">
        @if ($user?->isStaff())
            <a href="{{ route('indicators.index') }}" class="btn"><i data-lucide="database"></i> Kelola indikator</a>
        @endif
        <a href="{{ route('map') }}" class="btn btn-primary"><i data-lucide="map"></i> Lihat di peta</a>
    </div>
</div>

<div class="tabs" data-tabs="an" data-hash>
    <button class="tab active" data-tab="peringkat"><i data-lucide="trophy"></i> Peringkat DCI</button>
    <button class="tab" data-tab="tren"><i data-lucide="trending-up"></i> Tren multi-indikator</button>
    <button class="tab" data-tab="banding"><i data-lucide="git-compare"></i> Bandingkan negara</button>
    <button class="tab" data-tab="publik"><i data-lucide="radio-tower"></i> Laporan publik</button>
</div>

{{-- ================= TAB 1: PERINGKAT DCI ================= --}}
<section class="tab-panel active" data-panel-group="an" data-panel="peringkat">
    <div class="toolbar">
        <span class="muted">Tahun data:</span>
        <form method="GET"><select class="select" name="year" onchange="this.form.submit()">@foreach ($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach</select></form>
        <div class="row wrap" style="margin-left:auto">
            @foreach ($weights as $k => $w)
                <span class="chip" style="font-size:11px">{{ $labels[$k] }} <b>{{ $w * 100 }}%</b></span>
            @endforeach
        </div>
    </div>

    <div class="grid g-main">
        <div class="card">
            <div class="table-wrap"><table class="table">
                <thead><tr><th>#</th><th>Negara</th><th class="num">Internet</th><th class="num">Mobile BB</th><th class="num">Fixed BB</th><th class="num">Kecepatan</th><th class="num">Harga/GB</th><th>Skor DCI</th></tr></thead>
                <tbody class="stagger">
                @foreach ($ranking as $idx => $row)
                    @php [$g, $gLabel, $gColor] = $row['grade']; $ind = $row['indicator']; @endphp
                    <tr>
                        <td class="mono {{ $idx < 3 ? 'trend-up' : 'dim' }}" style="font-weight:700">0{{ $idx + 1 }}</td>
                        <td><div class="cell-main">{{ $row['country']->flag() }} {{ $row['country']->name }}</div><div class="cell-sub">{{ $row['country']->capital }}</div></td>
                        <td class="num">{{ $ind->internet_users_pct }}%</td>
                        <td class="num">{{ $ind->mobile_broadband_per100 ?? '—' }}</td>
                        <td class="num">{{ $ind->fixed_broadband_per100 ?? '—' }}</td>
                        <td class="num">{{ $ind->avg_mobile_speed_mbps ?? '—' }} / {{ $ind->avg_fixed_speed_mbps ?? '—' }}</td>
                        <td class="num">${{ $ind->data_price_usd_gb ?? '—' }}</td>
                        <td style="min-width:190px">
                            <div class="row between"><b class="mono" style="color:{{ $gColor }}">{{ $row['score'] }}</b>
                                @if ($row['delta'] !== null)<span class="{{ $row['delta'] >= 0 ? 'trend-up' : 'trend-down' }}" style="font-size:11px">{{ $row['delta'] >= 0 ? '▲ +' : '▼ ' }}{{ $row['delta'] }}</span>@endif
                                <span class="badge no-dot" style="color:{{ $gColor }};border-color:{{ $gColor }}44">{{ $g }} · {{ $gLabel }}</span></div>
                            <div class="progress thin mt-1"><span data-width="{{ $row['score'] }}" style="--c:{{ $gColor }}"></span></div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        <div class="card">
            <div class="card-head"><div><h3 class="card-title"><i data-lucide="bar-chart-horizontal"></i> Skor DCI {{ $year }}</h3><div class="card-sub">Perbandingan antarnegara</div></div></div>
            <div class="chart-box lg"><canvas id="dciBar"></canvas></div>
        </div>
    </div>
</section>

{{-- ================= TAB 2: TREN ================= --}}
<section class="tab-panel" data-panel-group="an" data-panel="tren">
    <div class="card">
        <div class="toolbar">
            <select class="select" id="metricSelect">
                <option value="dci">Skor Digital Connectivity Index (0–100)</option>
                <option value="users">Pengguna Internet (% populasi)</option>
                <option value="mobile">Langganan Mobile Broadband (/100 penduduk)</option>
                <option value="fixed">Langganan Fixed Broadband (/100 penduduk)</option>
                <option value="mspeed">Kecepatan Mobile Rata-rata (Mbps)</option>
                <option value="fspeed">Kecepatan Fixed Rata-rata (Mbps)</option>
                <option value="price">Harga Data Seluler (USD / GB)</option>
            </select>
            <div class="row wrap" id="countryToggles">
                @foreach ($countries as $i => $c)
                    <button type="button" class="chip {{ $i < 6 ? 'active' : '' }}" data-iso="{{ $c->iso3 }}">{{ $c->flag() }} {{ $c->iso3 }}</button>
                @endforeach
            </div>
        </div>
        <div class="chart-box lg"><canvas id="multiTrend"></canvas></div>
    </div>
</section>

{{-- ================= TAB 3: BANDINGKAN ================= --}}
<section class="tab-panel" data-panel-group="an" data-panel="banding">
    <div class="grid g-main">
        <div class="card">
            <div class="toolbar">
                <select class="select" id="cmpA">@foreach ($ranking as $r)<option value="{{ $r['country']->iso3 }}" @selected($r['country']->iso3 === 'IDN')>{{ $r['country']->flag() }} {{ $r['country']->name }}</option>@endforeach</select>
                <span class="muted">vs.</span>
                <select class="select" id="cmpB">@foreach ($ranking as $r)<option value="{{ $r['country']->iso3 }}" @selected($r['country']->iso3 === 'MYS')>{{ $r['country']->flag() }} {{ $r['country']->name }}</option>@endforeach</select>
            </div>
            <div class="chart-box lg"><canvas id="radarCmp"></canvas></div>
        </div>
        <div class="card" id="cmpCards"></div>
    </div>
</section>

{{-- ================= TAB 4: LAPORAN PUBLIK ================= --}}
<section class="tab-panel" data-panel-group="an" data-panel="publik">
    <div class="grid g-2">
        <div class="card"><div class="card-head"><h3 class="card-title"><i data-lucide="alert-triangle"></i> Distribusi jenis masalah</h3></div><div class="chart-box"><canvas id="pubIssue"></canvas></div></div>
        <div class="card"><div class="card-head"><h3 class="card-title"><i data-lucide="radio"></i> Distribusi teknologi</h3></div><div class="chart-box"><canvas id="pubTech"></canvas></div></div>
        <div class="card"><div class="card-head"><h3 class="card-title"><i data-lucide="gauge"></i> Kecepatan unduh rata-rata per provider</h3></div><div class="chart-box"><canvas id="pubProv"></canvas></div></div>
        <div class="card"><div class="card-head"><h3 class="card-title"><i data-lucide="flag"></i> Jumlah laporan & rata-rata keparahan per negara</h3></div><div class="chart-box"><canvas id="pubCountry"></canvas></div></div>
    </div>
</section>
@endsection

@php
    $rankingJson = $ranking->map(fn ($r) => ['iso' => $r['country']->iso3, 'name' => $r['country']->name, 'flag' => $r['country']->flag(), 'score' => $r['score'], 'grade' => $r['grade'], 'comp' => $r['components'], 'ind' => $r['indicator']]);
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ranking = @json($rankingJson);
    const series = @json($indicators);
    const names = Object.fromEntries(ranking.map(r => [r.iso, r.name]));
    const byIso = Object.fromEntries(ranking.map(r => [r.iso, r]));
    const compLabels = @json($labels);

    // Tab 1: bar
    new Chart(document.getElementById('dciBar'), {
        type: 'bar',
        data: { labels: ranking.map(r => `${r.flag} ${r.name}`), datasets: [{ data: ranking.map(r => r.score), backgroundColor: ranking.map(r => r.grade[2] + 'cc'), borderRadius: 8, barThickness: 18 }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { min: 0, max: 100 }, y: { grid: { display: false } } } },
    });

    // Tab 2: multi-indicator line
    const years = [...new Set(Object.values(series).flat().map(x => x.year))].sort();
    const trendChart = new Chart(document.getElementById('multiTrend'), {
        type: 'line', data: { labels: years, datasets: [] },
        options: { interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' } }, scales: { x: { grid: { display: false } } } },
    });
    const updateTrend = () => {
        const metric = SA.$('#metricSelect').value;
        const active = SA.$$('#countryToggles .chip.active').map(b => b.dataset.iso);
        trendChart.data.datasets = active.map((iso, i) => ({
            label: names[iso] || iso,
            data: years.map(y => series[iso]?.find(r => r.year === y)?.[metric] ?? null),
            borderColor: SA.series[i % SA.series.length],
            backgroundColor: SA.series[i % SA.series.length],
            borderWidth: iso === 'IDN' ? 3 : 1.8, tension: .35, pointRadius: 3,
        }));
        trendChart.update();
    };
    SA.$('#metricSelect').onchange = updateTrend;
    SA.$$('#countryToggles .chip').forEach(b => b.onclick = () => { b.classList.toggle('active'); updateTrend(); });
    updateTrend();

    // Tab 3: radar comparison
    const keys = Object.keys(compLabels);
    const radar = new Chart(document.getElementById('radarCmp'), {
        type: 'radar',
        data: { labels: Object.values(compLabels), datasets: [] },
        options: { scales: { r: { min: 0, max: 100, angleLines: { color: 'rgba(120,150,190,.15)' }, grid: { color: 'rgba(120,150,190,.15)' }, pointLabels: { color: '#b5c3d6', font: { size: 11, weight: 600 } }, ticks: { display: false } } } },
    });
    const updateCmp = () => {
        const a = byIso[SA.$('#cmpA').value], b = byIso[SA.$('#cmpB').value];
        if (!a || !b) return;
        radar.data.datasets = [
            { label: a.name, data: keys.map(k => a.comp[k] ?? 0), borderColor: SA.colors.mint, backgroundColor: '#60e1bd26', borderWidth: 2.5 },
            { label: b.name, data: keys.map(k => b.comp[k] ?? 0), borderColor: SA.colors.violet, backgroundColor: '#b99aff26', borderWidth: 2.5 },
        ];
        radar.update();
        SA.$('#cmpCards').innerHTML = `<h3 class="card-title mb-2"><i data-lucide="scale"></i> Head-to-head</h3>` + [
            ['Skor DCI', a.score, b.score, '', true],
            ['Pengguna Internet', a.ind.internet_users_pct, b.ind.internet_users_pct, '%', true],
            ['Mobile Broadband (/100)', a.ind.mobile_broadband_per100, b.ind.mobile_broadband_per100, '', true],
            ['Fixed Broadband (/100)', a.ind.fixed_broadband_per100, b.ind.fixed_broadband_per100, '', true],
            ['Kecepatan Mobile', a.ind.avg_mobile_speed_mbps, b.ind.avg_mobile_speed_mbps, ' Mbps', true],
            ['Kecepatan Fixed', a.ind.avg_fixed_speed_mbps, b.ind.avg_fixed_speed_mbps, ' Mbps', true],
            ['Harga Data / GB', a.ind.data_price_usd_gb, b.ind.data_price_usd_gb, ' USD', false],
        ].map(([lbl, va, vb, u, hi]) => {
            const winA = hi ? va >= vb : va <= vb;
            return `<div class="list-item"><span class="mono" style="width:90px;color:${winA ? 'var(--mint)' : 'var(--muted)'};font-weight:700">${va ?? '—'}${u}</span><div class="list-body" style="text-align:center;font-size:12px;color:var(--muted)">${lbl}</div><span class="mono" style="width:90px;text-align:right;color:${!winA ? 'var(--violet)' : 'var(--muted)'};font-weight:700">${vb ?? '—'}${u}</span></div>`;
        }).join('');
        SA.icons();
    };
    SA.$('#cmpA').onchange = SA.$('#cmpB').onchange = updateCmp;
    updateCmp();

    // Tab 4: public report charts
    const rs = @json($reportStats), il = @json(config('signal.issue_types')), tl = @json(config('signal.technologies'));
    new Chart(document.getElementById('pubIssue'), {
        type: 'doughnut',
        data: { labels: Object.keys(il).map(k => il[k]), datasets: [{ data: Object.keys(il).map(k => rs.issues[k] || 0), backgroundColor: SA.series, borderColor: '#0b1222', borderWidth: 3 }] },
        options: { cutout: '64%', plugins: { legend: { position: 'right' } } },
    });
    new Chart(document.getElementById('pubTech'), {
        type: 'polarArea',
        data: { labels: Object.keys(tl).map(k => tl[k]), datasets: [{ data: Object.keys(tl).map(k => rs.tech[k] || 0), backgroundColor: SA.series.map(c => c + '88'), borderColor: SA.series }] },
        options: { scales: { r: { grid: { color: 'rgba(120,150,190,.12)' }, ticks: { display: false } } }, plugins: { legend: { position: 'right' } } },
    });
    new Chart(document.getElementById('pubProv'), {
        type: 'bar',
        data: { labels: rs.providers.map(x => x.k), datasets: [{ label: 'Mbps', data: rs.providers.map(x => (+x.v).toFixed(1)), backgroundColor: SA.colors.blue + 'bb', borderRadius: 6 }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { y: { grid: { display: false } } } },
    });
    new Chart(document.getElementById('pubCountry'), {
        type: 'bar',
        data: { labels: rs.byCountry.map(x => x.k), datasets: [
            { label: 'Jumlah laporan', data: rs.byCountry.map(x => x.v), backgroundColor: SA.colors.mint + 'bb', borderRadius: 6 },
            { label: 'Rata-rata keparahan', data: rs.byCountry.map(x => Math.round(x.s)), backgroundColor: SA.colors.red + 'bb', borderRadius: 6 },
        ] },
        options: { scales: { x: { grid: { display: false } } } },
    });
});
</script>
@endpush
