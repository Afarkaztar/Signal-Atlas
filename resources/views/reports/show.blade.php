@extends('layouts.app')
@section('title', $report->code)

@section('content')
@php
    $user = auth()->user();
    $max = ['Kecepatan unduh' => 45, 'Latensi' => 15, 'Jenis masalah' => 25, 'Teknologi' => 15];
    $levelColor = ['low' => '#60e1bd', 'medium' => '#f2cc72', 'high' => '#ffb27a', 'critical' => '#ff8f83'][$report->severity_level];
    $steps = [
        ['send', 'Laporan dikirim', $report->created_at, true, 'var(--mint)'],
        [$report->status === 'rejected' ? 'x-octagon' : 'shield-check', $report->status === 'rejected' ? 'Ditolak analis' : 'Diverifikasi analis', $report->verified_at, (bool) $report->verified_at, $report->status === 'rejected' ? 'var(--red)' : 'var(--blue)'],
        ['hard-hat', 'Ditautkan ke proyek', $report->project?->created_at, (bool) $report->project, 'var(--violet)'],
        ['party-popper', 'Terselesaikan', $report->resolved_at, (bool) $report->resolved_at, 'var(--yellow)'],
    ];
@endphp

<div class="page-head">
    <div>
        <a href="{{ route('reports.index') }}" class="muted" style="font-size:12px;display:inline-flex;gap:6px;align-items:center"><i data-lucide="arrow-left" style="width:14px"></i> Kembali ke daftar</a>
        <h1 class="page-title"><span class="mono">{{ $report->code }}</span></h1>
        <div class="row wrap">
            <span class="badge b-{{ $report->status }}">{{ $report->statusLabel() }}</span>
            <span class="badge b-{{ $report->severity_level }}">{{ $report->severityLabel() }}</span>
            <span class="badge b-neutral no-dot"><i data-lucide="{{ $report->source === 'speedtest' ? 'gauge' : ($report->source === 'import' ? 'upload' : 'pen-line') }}" style="width:12px;height:12px"></i> {{ ucfirst($report->source) }}</span>
        </div>
    </div>
    <div class="head-actions">
        @if ($user->isStaff() && $report->status === 'pending' && $report->user_id !== $user->id)
            <form method="POST" action="{{ route('verification.verify', $report) }}">@csrf<button class="btn btn-success"><i data-lucide="check"></i> Verifikasi</button></form>
            <button class="btn btn-danger" data-modal="rejectModal"><i data-lucide="x"></i> Tolak</button>
        @endif
        @if ($report->isEditable() && ($user->isStaff() || $report->user_id === $user->id))
            <a href="{{ route('reports.edit', $report) }}" class="btn"><i data-lucide="pencil"></i> Ubah</a>
        @endif
        @if ($user->isAdmin() || ($report->user_id === $user->id && $report->isEditable()))
            <form method="POST" action="{{ route('reports.destroy', $report) }}" data-confirm="Hapus laporan {{ $report->code }}?">@csrf @method('DELETE')<button class="btn btn-danger" data-keep><i data-lucide="trash-2"></i></button></form>
        @endif
    </div>
</div>

{{-- Progress alur bisnis --}}
<div class="card mb-3" data-reveal>
    <div class="grid g-4">
        @foreach ($steps as $i => [$icon, $label, $at, $done, $color])
            <div class="row" style="opacity:{{ $done ? 1 : .4 }}">
                <span class="kpi-icon" style="--c:{{ $color }};margin:0;{{ $done ? '' : 'filter:grayscale(1)' }}"><i data-lucide="{{ $done ? $icon : 'circle-dashed' }}"></i></span>
                <div><b style="font-size:13px">{{ $label }}</b><div class="dim" style="font-size:11.5px">{{ $at ? $at->translatedFormat('d M Y, H:i') : 'Menunggu' }}</div></div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid g-side">
    <div class="stack">
        <div class="card" data-reveal>
            <h3 class="card-title mb-2"><i data-lucide="file-text"></i> Detail laporan</h3>
            <p style="font-size:14.5px;line-height:1.7;margin:0 0 18px">{{ $report->description }}</p>
            <div class="detail-grid">
                <div class="detail-item"><div class="k">Wilayah</div><div class="v">{{ $report->region->name }}</div><div class="dim" style="font-size:12px">{{ $report->region->country->flag() }} {{ $report->region->country->name }} · {{ $report->region->areaLabel() }}</div></div>
                <div class="detail-item"><div class="k">Provider</div><div class="v">{{ $report->provider }}</div><div class="dim" style="font-size:12px">{{ $report->technologyLabel() }}</div></div>
                <div class="detail-item"><div class="k">Jenis masalah</div><div class="v">{{ $report->issueLabel() }}</div></div>
                <div class="detail-item"><div class="k">Pelapor</div><div class="v">{{ $report->user?->name ?? '—' }}</div><div class="dim" style="font-size:12px">{{ $report->created_at->translatedFormat('d M Y, H:i') }}</div></div>
            </div>
            <div class="grid g-3 mt-2">
                <div class="detail-item metric"><div class="m-label"><i data-lucide="arrow-down-circle" style="width:14px"></i> Unduh</div><div class="m-value">{{ $report->download_mbps ?? '—' }} <small>Mbps</small></div></div>
                <div class="detail-item metric"><div class="m-label"><i data-lucide="arrow-up-circle" style="width:14px"></i> Unggah</div><div class="m-value">{{ $report->upload_mbps ?? '—' }} <small>Mbps</small></div></div>
                <div class="detail-item metric"><div class="m-label"><i data-lucide="timer" style="width:14px"></i> Latensi</div><div class="m-value">{{ $report->latency_ms ?? '—' }} <small>ms</small></div></div>
            </div>
            @if ($report->photo_path)<img src="{{ asset('storage/'.$report->photo_path) }}" class="photo mt-2" alt="Foto bukti">@endif
        </div>

        @if ($report->latitude)
            <div class="card" data-reveal><h3 class="card-title mb-2"><i data-lucide="map-pin"></i> Titik lokasi</h3><div id="detailMap" class="map sm"></div></div>
        @endif

        @if ($report->review_note)
            <div class="alert {{ $report->status === 'rejected' ? 'alert-danger' : 'alert-info' }}">
                <i data-lucide="message-square-quote"></i>
                <div><b>Catatan peninjau — {{ $report->verifier?->name }}</b><br>{{ $report->review_note }}</div>
            </div>
        @endif
    </div>

    <div class="stack">
        <div class="card glow-border" data-reveal>
            <h3 class="card-title"><i data-lucide="calculator"></i> Skor keparahan</h3>
            <div style="display:grid;place-items:center;margin:18px 0">
                <div class="score-ring lg" data-ring="{{ $report->severity_score }}" style="--c:{{ $levelColor }}"><b data-count="{{ $report->severity_score }}">0</b></div>
            </div>
            @foreach ($breakdown as $k => $v)
                <div class="bar-row" style="grid-template-columns:110px 1fr 36px"><span class="muted">{{ $k }}</span><div class="progress thin"><span data-width="{{ $v / $max[$k] * 100 }}" style="--c:{{ $levelColor }}"></span></div><b class="mono" style="text-align:right">{{ $v }}</b></div>
            @endforeach
        </div>

        @if ($report->project)
            <a href="{{ route('projects.show', $report->project) }}" class="card hover" data-reveal style="display:block">
                <h3 class="card-title"><i data-lucide="hard-hat"></i> Proyek terkait</h3>
                <div class="mt-1" style="font-weight:700">{{ $report->project->title }}</div>
                <div class="row between mt-1"><span class="badge b-{{ $report->project->status }}">{{ $report->project->statusLabel() }}</span><b class="mono">{{ $report->project->progress }}%</b></div>
                <div class="progress mt-1"><span data-width="{{ $report->project->progress }}"></span></div>
            </a>
        @endif

        <div class="card" data-reveal>
            <h3 class="card-title mb-2"><i data-lucide="map"></i> Laporan lain di wilayah ini</h3>
            <div class="list">
                @forelse ($nearby as $n)
                    <a href="{{ route('reports.show', $n) }}" class="list-item">
                        <span class="badge b-{{ $n->severity_level }}">{{ $n->severity_score }}</span>
                        <div class="list-body"><div class="list-title">{{ $n->issueLabel() }}</div><div class="list-sub">{{ $n->code }} · {{ $n->created_at->diffForHumans() }}</div></div>
                    </a>
                @empty
                    <p class="muted" style="font-size:12.5px">Belum ada laporan lain.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if ($user->isStaff())
    <div class="modal" id="rejectModal"><div class="modal-backdrop"></div>
        <form class="modal-box" method="POST" action="{{ route('verification.reject', $report) }}">@csrf
            <button type="button" class="icon-btn modal-close" data-close><i data-lucide="x"></i></button>
            <div class="modal-icon" style="background:#ff8f8318;color:var(--red)"><i data-lucide="x-octagon"></i></div>
            <h3 class="modal-title">Tolak laporan {{ $report->code }}</h3>
            <p class="muted">Alasan penolakan akan dikirim ke pelapor sebagai notifikasi.</p>
            <textarea class="textarea" name="note" required minlength="5" placeholder="Contoh: Lokasi tidak sesuai / duplikat laporan RPT-…"></textarea>
            <div class="row mt-2" style="justify-content:flex-end"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-danger"><i data-lucide="x"></i> Tolak laporan</button></div>
        </form>
    </div>
@endif
@endsection

@if ($report->latitude)
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const p = [{{ $report->latitude }}, {{ $report->longitude }}];
            const map = SA.initMap('detailMap', p, 4, { scrollWheelZoom: false });
            L.marker(p, { icon: L.divIcon({ className: '', html: '<div class="pulse-marker" style="--c:{{ $levelColor }}"></div>', iconSize: [14, 14] }) }).addTo(map);
            setTimeout(() => map.flyTo(p, 10, { duration: 2 }), 400);
        });
    </script>
    @endpush
@endif
