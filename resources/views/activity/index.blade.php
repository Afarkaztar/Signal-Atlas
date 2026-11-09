@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Audit trail</span>
        <h1 class="page-title">Log Aktivitas Sistem</h1>
        <p class="page-sub">Catatan lengkap seluruh aksi pengguna untuk akuntabilitas dan pelacakan perubahan.</p>
    </div>
</div>

<div class="card">
    <form class="toolbar" method="GET">
        <div class="input-icon grow"><i data-lucide="search"></i><input class="input" style="width:100%" name="q" value="{{ request('q') }}" placeholder="Cari deskripsi aksi…"></div>
        <select class="select" name="action" onchange="this.form.submit()"><option value="">Semua kategori</option>@foreach ($actions as $k => $v)<option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>@endforeach</select>
        <select class="select" name="user" onchange="this.form.submit()"><option value="">Semua pengguna</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>@endforeach</select>
        @if (request()->anyFilled(['q', 'action', 'user']))<a href="{{ route('activity.index') }}" class="btn btn-sm btn-ghost">Reset</a>@endif
    </form>

    <div class="list">
        @foreach ($logs as $l)
            @php [$icon, $color] = $l->visual(); @endphp
            <div class="list-item">
                <span class="list-icon" style="color:{{ $color }}"><i data-lucide="{{ $icon }}"></i></span>
                <div class="list-body">
                    <div class="list-title">{{ $l->description }}</div>
                    <div class="list-sub">{{ $l->user?->name ?? 'Sistem' }} · <span class="mono">{{ $l->action }}</span> · IP {{ $l->ip_address }}</div>
                </div>
                <span class="mono muted" style="font-size:11.5px">{{ $l->created_at->translatedFormat('d M Y H:i') }}</span>
            </div>
        @endforeach
    </div>
    {{ $logs->links() }}
</div>
@endsection
