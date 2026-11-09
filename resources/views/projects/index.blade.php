@extends('layouts.app')
@section('title', 'Proyek Infrastruktur')

@section('content')
@php
    $user = auth()->user();
    $colColors = ['proposed' => '#f2cc72', 'approved' => '#76a9ff', 'in_progress' => '#b99aff', 'on_hold' => '#ffb27a', 'completed' => '#60e1bd', 'rejected' => '#ff8f83', 'cancelled' => '#5e6d84'];
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Infrastruktur konektivitas</span>
        <h1 class="page-title">Proyek Pembangunan</h1>
        <p class="page-sub">
            @if ($user->isStaff())
                Seret kartu antar kolom untuk mengubah status proyek sesuai state machine. Menyelesaikan proyek otomatis menyelesaikan laporan terkait.
            @else
                Pantau progres pembangunan menara BTS, fiber optik, dan internet satelit di berbagai wilayah.
            @endif
        </p>
    </div>
    <div class="head-actions">
        <a href="{{ route('projects.export') }}" class="btn"><i data-lucide="download"></i> Ekspor CSV</a>
        @if ($user->isStaff())
            <a href="{{ route('priorities.index') }}" class="btn"><i data-lucide="target"></i> Dari prioritas</a>
            <a href="{{ route('projects.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Usulkan Proyek</a>
        @endif
    </div>
</div>

<div class="grid g-4 stagger mb-3">
    <div class="card kpi" style="--c:var(--mint)"><div class="kpi-label">Total anggaran aktif</div><div class="kpi-value" data-count="{{ $summary['budget'] }}" data-prefix="$">$0</div><div class="kpi-foot">USD · tidak termasuk ditolak/batal</div></div>
    <div class="card kpi" style="--c:var(--blue)"><div class="kpi-label">Penerima manfaat</div><div class="kpi-value" data-count="{{ $summary['beneficiaries'] }}">0</div><div class="kpi-foot">Jiwa terjangkau (dikerjakan & selesai)</div></div>
    <div class="card kpi" style="--c:var(--violet)"><div class="kpi-label">Proyek selesai</div><div class="kpi-value" data-count="{{ $summary['completed'] }}">0</div><div class="kpi-foot">Beroperasi penuh</div></div>
    <div class="card kpi" style="--c:var(--red)"><div class="kpi-label">Melewati target</div><div class="kpi-value" data-count="{{ $summary['overdue'] }}">0</div><div class="kpi-foot">Perlu perhatian</div></div>
</div>

<div class="row between wrap mb-2">
    <div class="tabs" data-tabs="pv" style="margin:0">
        <button class="tab active" data-tab="kanban"><i data-lucide="columns-3"></i> Papan Kanban</button>
        <button class="tab" data-tab="tabel"><i data-lucide="table"></i> Tabel daftar</button>
    </div>
    <form class="row wrap" method="GET">
        <input class="input" style="width:200px;padding:8px 12px" name="q" value="{{ request('q') }}" placeholder="Cari kode / judul…">
        <select class="select" style="width:auto;padding:8px 30px 8px 12px" name="country" onchange="this.form.submit()"><option value="">Semua negara</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country') == $c->id)>{{ $c->flag() }} {{ $c->name }}</option>@endforeach</select>
        <select class="select" style="width:auto;padding:8px 30px 8px 12px" name="type" onchange="this.form.submit()"><option value="">Semua jenis</option>@foreach (config('signal.project_types') as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select>
    </form>
</div>

{{-- ================= KANBAN ================= --}}
<section class="tab-panel active" data-panel-group="pv" data-panel="kanban">
    <div class="kanban">
        @foreach ($board as $status => $items)
            <div class="k-col" data-status="{{ $status }}" style="--c:{{ $colColors[$status] }}">
                <div class="k-head"><span class="k-dot"></span>{{ config("signal.project_statuses.$status") }}<span class="count">{{ $items->count() }}</span></div>
                <div class="k-list">
                    @forelse ($items as $p)
                        <a href="{{ route('projects.show', $p) }}" class="k-card"
                           @if ($user->isStaff()) draggable="true" data-id="{{ $p->id }}" data-url="{{ route('projects.transition', $p) }}" data-allowed='@json($p->allowedTransitions())' @endif>
                            <div class="row between"><span class="k-code">{{ $p->code }}</span><span class="badge b-neutral no-dot" style="font-size:10px">{{ $p->region->country->flag() }} {{ $p->region->name }}</span></div>
                            <div class="k-title">{{ $p->title }}</div>
                            <div class="progress thin"><span style="width:{{ $p->progress }}%"></span></div>
                            <div class="row between mt-1" style="font-size:11px;color:var(--muted)">
                                <span>${{ number_format($p->budget_usd / 1000) }}k · {{ $p->progress }}%</span>
                                @if ($p->isOverdue())<span style="color:var(--red)">⚠ Terlambat</span>@else<span>Skor {{ $p->priority_score }}</span>@endif
                            </div>
                        </a>
                    @empty
                        <div class="k-empty">Tidak ada proyek</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= TABEL ================= --}}
<section class="tab-panel" data-panel-group="pv" data-panel="tabel">
    <div class="card">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Kode</th><th>Proyek</th><th>Wilayah</th><th>Status</th><th>Progres</th><th class="num">Anggaran</th><th class="num">Target</th></tr></thead>
            <tbody>
            @foreach ($projects as $p)
                <tr onclick="location='{{ route('projects.show', $p) }}'" style="cursor:pointer">
                    <td class="mono" style="color:var(--mint)">{{ $p->code }}</td>
                    <td><div class="cell-main">{{ $p->title }}</div><div class="cell-sub">{{ $p->typeLabel() }} · Skor {{ $p->priority_score }}</div></td>
                    <td>{{ $p->region->country->flag() }} {{ $p->region->name }}</td>
                    <td><span class="badge b-{{ $p->status }}">{{ $p->statusLabel() }}</span></td>
                    <td style="min-width:140px"><div class="progress thin"><span data-width="{{ $p->progress }}"></span></div><span class="mono" style="font-size:11px">{{ $p->progress }}%</span></td>
                    <td class="num">${{ number_format($p->budget_usd) }}</td>
                    <td class="num">{{ $p->target_date?->translatedFormat('d M Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
</section>
@endsection

@if ($user->isStaff())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let dragging = null, allowed = [];
    SA.$$('.k-card[draggable]').forEach(card => {
        card.addEventListener('dragstart', (e) => {
            dragging = card; allowed = JSON.parse(card.dataset.allowed || '[]');
            card.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => { card.classList.remove('dragging'); SA.$$('.k-col').forEach(c => c.classList.remove('over', 'denied')); });
    });
    SA.$$('.k-col').forEach(col => {
        col.addEventListener('dragover', (e) => {
            if (!dragging) return;
            e.preventDefault();
            const ok = allowed.includes(col.dataset.status);
            col.classList.toggle('over', ok); col.classList.toggle('denied', !ok);
        });
        col.addEventListener('dragleave', () => col.classList.remove('over', 'denied'));
        col.addEventListener('drop', async (e) => {
            e.preventDefault(); col.classList.remove('over', 'denied');
            const to = col.dataset.status;
            if (!dragging || !allowed.includes(to)) return SA.toast('Transisi status tersebut tidak diizinkan oleh alur proyek.', 'warning');
            const card = dragging;
            try {
                const res = await SA.request(card.dataset.url, { method: 'POST', body: JSON.stringify({ status: to }) });
                col.querySelector('.k-empty')?.remove();
                col.querySelector('.k-list').prepend(card);
                SA.$$('.k-col').forEach(c => c.querySelector('.count').textContent = c.querySelectorAll('.k-card').length);
                SA.toast(res.message, 'success');
                setTimeout(() => location.reload(), 900);
            } catch (err) { SA.toast(err.message, 'danger'); }
        });
    });
});
</script>
@endpush
@endif
