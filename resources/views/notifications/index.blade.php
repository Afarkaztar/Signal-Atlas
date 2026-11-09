@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
@php
    $toneColor = ['success' => 'var(--mint)', 'danger' => 'var(--red)', 'warning' => 'var(--yellow)', 'info' => 'var(--blue)'];
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Kotak masuk</span>
        <h1 class="page-title">Notifikasi</h1>
        <p class="page-sub">Pemberitahuan otomatis saat laporan Anda diverifikasi, ditolak, atau terselesaikan melalui proyek.</p>
    </div>
    <div class="head-actions">
        <form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn btn-sm"><i data-lucide="check-check"></i> Tandai semua dibaca</button></form>
        <form method="POST" action="{{ route('notifications.clear') }}" data-confirm="Hapus semua notifikasi yang sudah dibaca?">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" data-keep><i data-lucide="trash-2"></i> Bersihkan</button></form>
    </div>
</div>

<div class="filter-pills">
    <a href="{{ route('notifications.index') }}" class="chip {{ $filter === 'all' ? 'active' : '' }}">Semua</a>
    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="chip {{ $filter === 'unread' ? 'active' : '' }}">Belum dibaca</a>
</div>

<div class="card">
    @if ($notifications->isEmpty())
        <div class="empty"><div class="empty-icon"><i data-lucide="bell-off"></i></div><h3>Tidak ada notifikasi</h3></div>
    @else
        <div class="list">
            @foreach ($notifications as $n)
                @php $c = $toneColor[$n->data['tone'] ?? 'info'] ?? 'var(--blue)'; @endphp
                <div class="list-item notif {{ $n->read_at ? '' : 'unread' }}" style="{{ $n->read_at ? 'opacity:.72' : 'background:rgba(96,225,189,.04)' }}">
                    <span class="list-icon" style="color:{{ $c }}"><i data-lucide="{{ $n->data['icon'] ?? 'bell' }}"></i></span>
                    <div class="list-body">
                        <div class="list-title">{{ $n->data['title'] ?? 'Notifikasi' }} @unless ($n->read_at)<span class="badge b-resolved" style="margin-left:6px">Baru</span>@endunless</div>
                        <div class="list-sub">{{ $n->data['message'] ?? '' }} · {{ $n->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="row">
                        @if ($n->data['url'] ?? null)
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf<button class="btn btn-xs">Buka <i data-lucide="arrow-right"></i></button></form>
                        @elseif (! $n->read_at)
                            <button class="btn btn-xs" data-read-notif="{{ route('notifications.read', $n->id) }}"><i data-lucide="check"></i></button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        {{ $notifications->links() }}
    @endif
</div>
@endsection
