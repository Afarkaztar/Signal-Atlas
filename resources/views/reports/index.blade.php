@extends('layouts.app')
@section('title', 'Laporan Sinyal')

@section('content')
@php
    $user = auth()->user();
    $sortLink = fn ($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => $sort === $col && $dir === 'desc' ? 'asc' : 'desc']);
    $sortIcon = fn ($col) => $sort === $col ? ($dir === 'desc' ? 'arrow-down' : 'arrow-up') : 'arrow-up-down';
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Crowdsourced data</span>
        <h1 class="page-title">Laporan Sinyal</h1>
        <p class="page-sub">Cari, filter, urutkan, dan ekspor laporan kualitas konektivitas. Laporan dapat diubah selama masih menunggu verifikasi.</p>
    </div>
    <div class="head-actions">
        <a href="{{ route('reports.export', request()->query()) }}" class="btn"><i data-lucide="download"></i> Ekspor CSV</a>
        <a href="{{ route('reports.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Laporan Baru</a>
    </div>
</div>

@unless ($user->isStaff())
    <div class="tabs">
        <a href="{{ route('reports.index', ['scope' => 'mine']) }}" class="tab {{ $scope === 'mine' ? 'active' : '' }}" style="{{ $scope === 'mine' ? 'background:linear-gradient(120deg,#60e1bd,#6fd0f0)' : '' }}"><i data-lucide="user"></i> Laporan saya</a>
        <a href="{{ route('reports.index', ['scope' => 'all']) }}" class="tab {{ $scope === 'all' ? 'active' : '' }}" style="{{ $scope === 'all' ? 'background:linear-gradient(120deg,#60e1bd,#6fd0f0)' : '' }}"><i data-lucide="globe-2"></i> Semua laporan publik</a>
    </div>
@endunless

<div class="filter-pills">
    @php $total = $summary->sum(); @endphp
    <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="chip {{ empty($filters['status']) ? 'active' : '' }}">Semua <b>{{ $total }}</b></a>
    @foreach (config('signal.report_statuses') as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}" class="chip {{ ($filters['status'] ?? '') === $key ? 'active' : '' }}">{{ $label }} <b>{{ $summary[$key] ?? 0 }}</b></a>
    @endforeach
</div>

<div class="card">
    <form class="toolbar" method="GET" id="filterForm">
        <input type="hidden" name="scope" value="{{ $scope }}">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <div class="input-icon grow"><i data-lucide="search"></i><input class="input" style="width:100%" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari kode, provider, wilayah, deskripsi…"></div>
        <select class="select" name="country" data-auto><option value="">Semua negara</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(($filters['country'] ?? '') == $c->id)>{{ $c->flag() }} {{ $c->name }}</option>@endforeach</select>
        <select class="select" name="issue_type" data-auto><option value="">Semua masalah</option>@foreach (config('signal.issue_types') as $k => $v)<option value="{{ $k }}" @selected(($filters['issue_type'] ?? '') === $k)>{{ $v }}</option>@endforeach</select>
        <select class="select" name="severity" data-auto><option value="">Semua level</option>@foreach (config('signal.severity_levels') as $k => $v)<option value="{{ $k }}" @selected(($filters['severity'] ?? '') === $k)>{{ $v }}</option>@endforeach</select>
        <input class="input" type="date" name="from" value="{{ $filters['from'] ?? '' }}" title="Dari tanggal" data-auto>
        <input class="input" type="date" name="to" value="{{ $filters['to'] ?? '' }}" title="Sampai tanggal" data-auto>
        <button class="btn btn-sm" type="submit" data-keep><i data-lucide="filter"></i> Terapkan</button>
        @if (array_filter($filters))<a href="{{ route('reports.index', ['scope' => $scope]) }}" class="btn btn-sm btn-ghost"><i data-lucide="x"></i> Reset</a>@endif
    </form>

    @if ($reports->isEmpty())
        <div class="empty"><div class="empty-icon"><i data-lucide="search-x"></i></div><h3>Tidak ada laporan ditemukan</h3><p>Coba ubah filter atau buat laporan baru.</p></div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><a href="{{ $sortLink('code') }}">Kode <i data-lucide="{{ $sortIcon('code') }}" style="width:12px"></i></a></th>
                    <th>Wilayah</th><th>Provider · Teknologi</th><th>Masalah</th>
                    <th class="num"><a href="{{ $sortLink('download_mbps') }}">Unduh <i data-lucide="{{ $sortIcon('download_mbps') }}" style="width:12px"></i></a></th>
                    <th><a href="{{ $sortLink('severity_score') }}">Skor <i data-lucide="{{ $sortIcon('severity_score') }}" style="width:12px"></i></a></th>
                    <th>Status</th>
                    <th><a href="{{ $sortLink('created_at') }}">Tanggal <i data-lucide="{{ $sortIcon('created_at') }}" style="width:12px"></i></a></th>
                    <th></th>
                </tr></thead>
                <tbody class="stagger">
                @foreach ($reports as $r)
                    <tr>
                        <td><a href="{{ route('reports.show', $r) }}" class="mono" style="font-size:12px;color:var(--mint)">{{ $r->code }}</a>
                            @if ($r->source !== 'manual')<div class="cell-sub"><i data-lucide="{{ $r->source === 'speedtest' ? 'gauge' : 'upload' }}" style="width:11px;height:11px"></i> {{ $r->source }}</div>@endif</td>
                        <td><div class="cell-main">{{ $r->region->name }}</div><div class="cell-sub">{{ $r->region->country->flag() }} {{ $r->region->country->name }} · {{ $r->region->areaLabel() }}</div></td>
                        <td><div>{{ $r->provider }}</div><div class="cell-sub">{{ $r->technologyLabel() }}</div></td>
                        <td>{{ $r->issueLabel() }}</td>
                        <td class="num">{{ $r->download_mbps !== null ? number_format($r->download_mbps, 1, ',', '.') : '—' }}</td>
                        <td><span class="badge b-{{ $r->severity_level }}">{{ $r->severity_score }} · {{ $r->severityLabel() }}</span></td>
                        <td><span class="badge b-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                        <td><div style="font-size:12px">{{ $r->created_at->translatedFormat('d M Y') }}</div><div class="cell-sub">{{ $r->user?->name }}</div></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('reports.show', $r) }}" class="btn btn-xs" title="Detail"><i data-lucide="eye"></i></a>
                                @if ($r->isEditable() && ($user->isStaff() || $r->user_id === $user->id))
                                    <a href="{{ route('reports.edit', $r) }}" class="btn btn-xs" title="Ubah"><i data-lucide="pencil"></i></a>
                                @endif
                                @if ($user->isAdmin() || ($r->user_id === $user->id && $r->isEditable()))
                                    <form method="POST" action="{{ route('reports.destroy', $r) }}" data-confirm="Hapus laporan {{ $r->code }}?">@csrf @method('DELETE')
                                        <button class="btn btn-xs btn-danger" title="Hapus" data-keep><i data-lucide="trash-2"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $reports->links() }}
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-auto]').forEach(el => el.addEventListener('change', () => document.getElementById('filterForm').submit()));
</script>
@endpush
