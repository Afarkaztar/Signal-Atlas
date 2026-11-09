@extends('layouts.app')
@section('title', $project->code)

@section('content')
@php
    $user = auth()->user();
    $allowed = $project->allowedTransitions();
    $btnStyle = ['approved' => 'btn-primary', 'in_progress' => 'btn-violet', 'completed' => 'btn-success', 'on_hold' => '', 'rejected' => 'btn-danger', 'cancelled' => 'btn-danger'];
@endphp

<div class="page-head">
    <div>
        <a href="{{ route('projects.index') }}" class="muted" style="font-size:12px;display:inline-flex;gap:6px;align-items:center"><i data-lucide="arrow-left" style="width:14px"></i> Semua proyek</a>
        <h1 class="page-title">{{ $project->title }}</h1>
        <div class="row wrap">
            <span class="mono muted">{{ $project->code }}</span>
            <span class="badge b-{{ $project->status }}">{{ $project->statusLabel() }}</span>
            <span class="badge b-neutral no-dot">{{ $project->typeLabel() }}</span>
            @if ($project->isOverdue())<span class="badge b-critical">Melewati target</span>@endif
        </div>
    </div>
    @if ($user->isStaff())
        <div class="head-actions">
            @foreach ($allowed as $to)
                @if (! in_array($to, config('signal.admin_only_transitions'), true) || $user->isAdmin())
                    <button class="btn {{ $btnStyle[$to] ?? '' }}" data-transition="{{ $to }}" data-label="{{ config("signal.project_statuses.$to") }}">
                        → {{ config("signal.project_statuses.$to") }}
                    </button>
                @endif
            @endforeach
            @unless (in_array($project->status, ['completed', 'cancelled', 'rejected'], true))
                <a href="{{ route('projects.edit', $project) }}" class="btn"><i data-lucide="pencil"></i> Ubah</a>
            @endunless
            @if ($user->isAdmin())
                <form method="POST" action="{{ route('projects.destroy', $project) }}" data-confirm="Hapus proyek {{ $project->code }}?">@csrf @method('DELETE')<button class="btn btn-danger" data-keep><i data-lucide="trash-2"></i></button></form>
            @endif
        </div>
    @endif
</div>

<div class="grid g-side">
    <div class="stack">
        <div class="card" data-reveal>
            <div class="row between mb-2"><span class="muted">Progres fisik</span><b class="mono" style="font-size:20px;color:var(--mint)">{{ $project->progress }}%</b></div>
            <div class="progress"><span data-width="{{ $project->progress }}"></span></div>
            <p class="mt-2" style="line-height:1.7">{{ $project->description ?: 'Tidak ada deskripsi tambahan.' }}</p>
            <div class="detail-grid mt-2">
                <div class="detail-item"><div class="k">Wilayah</div><div class="v">{{ $project->region->country->flag() }} {{ $project->region->name }}, {{ $project->region->country->name }}</div></div>
                <div class="detail-item"><div class="k">Anggaran</div><div class="v mono">${{ number_format($project->budget_usd) }}</div></div>
                <div class="detail-item"><div class="k">Penerima manfaat</div><div class="v">{{ number_format($project->beneficiaries) }} jiwa</div></div>
                <div class="detail-item"><div class="k">Jadwal</div><div class="v">{{ $project->start_date?->translatedFormat('d M Y') ?? 'Belum mulai' }} → {{ $project->target_date?->translatedFormat('d M Y') }}</div></div>
                <div class="detail-item"><div class="k">Diusulkan oleh</div><div class="v">{{ $project->proposer?->name ?? '—' }}</div></div>
                <div class="detail-item"><div class="k">Disetujui oleh</div><div class="v">{{ $project->approver?->name ?? '—' }}</div></div>
            </div>
        </div>

        @if ($user->isStaff() && $project->status === 'in_progress')
            <form class="card glow-border" method="POST" action="{{ route('projects.progress', $project) }}" data-reveal>@csrf
                <h3 class="card-title mb-2"><i data-lucide="sliders-horizontal"></i> Perbarui progres lapangan</h3>
                <div class="row">
                    <input class="range" type="range" name="progress" min="{{ $project->progress }}" max="100" value="{{ $project->progress }}" oninput="pVal.textContent=this.value+'%'">
                    <b class="mono" id="pVal" style="min-width:52px;text-align:right;font-size:16px">{{ $project->progress }}%</b>
                </div>
                <div class="row mt-2">
                    <input class="input" name="note" placeholder="Catatan progres (misal: Pemasangan antena selesai)…">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        @endif

        <div class="card" data-reveal>
            <div class="card-head"><div><h3 class="card-title"><i data-lucide="radio-tower"></i> Laporan warga yang ditautkan ({{ $project->reports->count() }})</h3><div class="card-sub">Otomatis ditandai terselesaikan ketika proyek mencapai 100%.</div></div></div>
            @if ($project->reports->isEmpty())
                <p class="muted">Laporan terverifikasi di wilayah ini akan ditautkan otomatis saat proyek disetujui.</p>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Kode</th><th>Masalah</th><th>Pelapor</th><th>Skor</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($project->reports as $r)
                        <tr onclick="location='{{ route('reports.show', $r) }}'" style="cursor:pointer">
                            <td class="mono" style="color:var(--mint)">{{ $r->code }}</td>
                            <td>{{ $r->issueLabel() }} · {{ $r->provider }}</td>
                            <td>{{ $r->user?->name }}</td>
                            <td><span class="badge b-{{ $r->severity_level }}">{{ $r->severity_score }}</span></td>
                            <td><span class="badge b-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </div>
    </div>

    <div class="stack">
        <div class="card" data-reveal>
            <h3 class="card-title"><i data-lucide="target"></i> Skor prioritas wilayah</h3>
            <div class="row mt-2" style="gap:16px">
                <div class="score-ring" data-ring="{{ $project->priority_score }}"><b>{{ $project->priority_score }}</b></div>
                <div class="muted" style="font-size:12.5px">Dihitung dari laporan terverifikasi dan karakteristik demografis {{ $project->region->name }}.</div>
            </div>
        </div>

        <div class="card" data-reveal>
            <h3 class="card-title mb-2"><i data-lucide="git-commit"></i> Riwayat proyek</h3>
            <div class="timeline">
                @foreach ($project->updates as $u)
                    <div class="tl-item">
                        <div class="tl-title">
                            @if ($u->to_status) Status → {{ config("signal.project_statuses.{$u->to_status}", $u->to_status) }}
                            @else Progres {{ $u->progress }}% @endif
                        </div>
                        <div class="tl-meta">{{ $u->user?->name ?? 'Sistem' }} · {{ $u->created_at->translatedFormat('d M Y, H:i') }}</div>
                        @if ($u->note)<div class="tl-note">{{ $u->note }}</div>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<form id="trForm" method="POST" action="{{ route('projects.transition', $project) }}" hidden>@csrf<input name="status" id="trStatus"><input name="note" id="trNote"></form>
@endsection

@push('scripts')
<script>
    SA.$$('[data-transition]').forEach(btn => btn.onclick = async () => {
        const to = btn.dataset.transition, label = btn.dataset.label;
        const note = await SA.confirm({ title: `Ubah status ke "${label}"?`, message: 'Tambahkan catatan transisi (opsional):', confirmText: label, tone: ['rejected', 'cancelled'].includes(to) ? 'danger' : 'info', input: 'Catatan…' });
        if (note === false) return;
        SA.$('#trStatus').value = to; SA.$('#trNote').value = note === true ? '' : note;
        SA.$('#trForm').dataset.confirmed = 1; SA.$('#trForm').submit();
    });
</script>
@endpush
