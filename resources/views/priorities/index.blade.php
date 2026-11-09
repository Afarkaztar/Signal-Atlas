@extends('layouts.app')
@section('title', 'Prioritas Wilayah')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Decision support system</span>
        <h1 class="page-title">Prioritas Pembangunan Wilayah</h1>
        <p class="page-sub">Connectivity Gap Score (0–100) menggabungkan keparahan laporan, jumlah laporan aktif, tipe wilayah, dan populasi. Wilayah yang sudah memiliki proyek aktif dikurangi bobotnya 50%.</p>
    </div>
</div>

{{-- Simulator anggaran --}}
<div class="card glow-border mb-3" data-reveal>
    <form class="row wrap between" method="GET">
        <div style="flex:1;min-width:260px">
            <h3 class="card-title"><i data-lucide="wallet"></i> Simulator alokasi anggaran</h3>
            <p class="card-sub">Geser anggaran untuk melihat wilayah mana saja yang dapat ditangani secara optimal (greedy berdasarkan skor efektif).</p>
            <div class="row mt-2">
                <input class="range" type="range" id="budgetRange" min="100000" max="5000000" step="50000" value="{{ $budget }}">
                <input type="hidden" name="budget" id="budgetInput" value="{{ $budget }}">
                <input type="hidden" name="country" value="{{ $countryId }}">
                <b class="mono" style="font-size:18px;min-width:130px;text-align:right;color:var(--mint)" id="budgetLabel">${{ number_format($budget) }}</b>
                <button class="btn btn-primary btn-sm" type="submit">Hitung alokasi</button>
            </div>
        </div>
        <div class="row" style="gap:24px">
            <div><span class="muted">Proyek terpilih</span><div style="font-size:28px;font-weight:800">{{ $allocation->count() }}</div></div>
            <div><span class="muted">Estimasi warga terjangkau</span><div style="font-size:28px;font-weight:800;color:var(--mint)">{{ number_format($allocation->sum('estimated_beneficiaries')) }}</div></div>
            <div><span class="muted">Sisa anggaran</span><div class="mono" style="font-size:22px;font-weight:700">${{ number_format($remaining) }}</div></div>
        </div>
    </form>
</div>

<div class="card">
    <div class="toolbar">
        <form method="GET">
            <input type="hidden" name="budget" value="{{ $budget }}">
            <select class="select" name="country" onchange="this.form.submit()"><option value="">Semua negara</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected($countryId == $c->id)>{{ $c->flag() }} {{ $c->name }}</option>@endforeach</select>
        </form>
        <span class="muted" style="margin-left:auto">{{ $noEvidence }} wilayah lain belum memiliki laporan terverifikasi</span>
    </div>

    <div class="table-wrap"><table class="table">
        <thead><tr><th>#</th><th>Wilayah</th><th>Gap Score</th><th class="num">Laporan aktif</th><th class="num">Blank spot</th><th>Rekomendasi infrastruktur</th><th class="num">Estimasi biaya</th><th></th></tr></thead>
        <tbody class="stagger">
        @foreach ($rows as $i => $r)
            @php
                $inPlan = $allocation->contains(fn ($a) => $a['region']->id === $r['region']->id);
                $color = ['low' => '#60e1bd', 'medium' => '#f2cc72', 'high' => '#ffb27a', 'critical' => '#ff8f83'][$r['level']];
            @endphp
            <tr style="{{ $inPlan ? 'background:rgba(96,225,189,.05)' : '' }}">
                <td class="mono" style="font-weight:700">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                    <div class="cell-main">{{ $r['region']->name }} @if ($inPlan)<span class="badge b-resolved" style="margin-left:6px">Masuk alokasi</span>@endif</div>
                    <div class="cell-sub">{{ $r['region']->country->flag() }} {{ $r['region']->country->name }} · {{ $r['region']->areaLabel() }} · {{ number_format($r['region']->population) }} jiwa</div>
                </td>
                <td style="min-width:170px">
                    <div class="row between"><b class="mono" style="color:{{ $color }}">{{ $r['effective_score'] }}</b>
                        @if ($r['handled'])<span class="badge b-in_progress" title="Skor dikurangi 50% (dari {{ $r['gap_score'] }}) karena sudah ada proyek aktif">Proyek aktif</span>@endif
                    </div>
                    <div class="progress thin mt-1"><span data-width="{{ $r['effective_score'] }}" style="--c:{{ $color }}"></span></div>
                </td>
                <td class="num">{{ $r['active_reports'] }}</td>
                <td class="num">{{ $r['blank_ratio'] }}%</td>
                <td>
                    <div class="cell-main">{{ config('signal.project_types.'.$r['recommended_type']) }}</div>
                    <div class="cell-sub" style="max-width:340px">{{ $r['reason'] }}</div>
                </td>
                <td class="num">${{ number_format($r['estimated_cost']) }}<div class="cell-sub">~{{ number_format($r['estimated_beneficiaries']) }} warga</div></td>
                <td>
                    <a href="{{ route('projects.create', ['region_id' => $r['region']->id, 'type' => $r['recommended_type'], 'budget' => $r['estimated_cost'], 'beneficiaries' => $r['estimated_beneficiaries']]) }}" class="btn btn-xs btn-primary">
                        <i data-lucide="hard-hat"></i> Usulkan
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@endsection

@push('scripts')
<script>
    const rng = document.getElementById('budgetRange'), inp = document.getElementById('budgetInput'), lbl = document.getElementById('budgetLabel');
    rng.addEventListener('input', () => { inp.value = rng.value; lbl.textContent = '$' + Number(rng.value).toLocaleString('en-US'); });
</script>
@endpush
