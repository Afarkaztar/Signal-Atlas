@extends('layouts.app')
@section('title', 'Manajemen Pengguna')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Administrasi</span>
        <h1 class="page-title">Manajemen Pengguna & Peran</h1>
        <p class="page-sub">Kelola akses Administrator, Analis/Verifikator, dan Kontributor Publik.</p>
    </div>
    <button class="btn btn-primary" data-modal="newUserModal"><i data-lucide="user-plus"></i> Tambah Pengguna</button>
</div>

@include('partials.errors')

<div class="filter-pills">
    <a href="{{ route('users.index') }}" class="chip {{ ! request('role') ? 'active' : '' }}">Semua <b>{{ $roleCounts->sum() }}</b></a>
    @foreach (config('signal.roles') as $k => $v)
        <a href="{{ route('users.index', ['role' => $k]) }}" class="chip {{ request('role') === $k ? 'active' : '' }}">{{ $v }} <b>{{ $roleCounts[$k] ?? 0 }}</b></a>
    @endforeach
</div>

<div class="card">
    <form class="toolbar" method="GET">
        <input type="hidden" name="role" value="{{ request('role') }}">
        <div class="input-icon grow"><i data-lucide="search"></i><input class="input" style="width:100%" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email…"></div>
        <button class="btn btn-sm" type="submit" data-keep>Cari</button>
    </form>

    <div class="table-wrap"><table class="table">
        <thead><tr><th>Pengguna</th><th>Instansi</th><th>Peran</th><th class="num">Laporan</th><th class="num">Poin</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
                <td><div class="row"><span class="avatar" style="width:32px;height:32px;font-size:11px">{{ $u->initials() }}</span><div><div class="cell-main">{{ $u->name }}</div><div class="cell-sub">{{ $u->email }}</div></div></div></td>
                <td>{{ $u->institution ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('users.update', $u) }}">@csrf @method('PUT')
                        <select class="select" name="role" style="width:auto;padding:6px 28px 6px 10px;font-size:12px" onchange="this.form.submit()" @disabled($u->id === auth()->id())>
                            @foreach (config('signal.roles') as $k => $v)<option value="{{ $k }}" @selected($u->role === $k)>{{ $v }}</option>@endforeach
                        </select>
                    </form>
                </td>
                <td class="num">{{ $u->reports_count }}</td>
                <td class="num">{{ $u->points }}</td>
                <td><span class="badge {{ $u->is_active ? 'b-resolved' : 'b-rejected' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td>
                    @if ($u->id !== auth()->id())
                        <div class="actions">
                            <form method="POST" action="{{ route('users.toggle', $u) }}">@csrf<button class="btn btn-xs" title="Aktif/nonaktif"><i data-lucide="power"></i></button></form>
                            <form method="POST" action="{{ route('users.destroy', $u) }}" data-confirm="Hapus akun {{ $u->name }}?">@csrf @method('DELETE')<button class="btn btn-xs btn-danger" data-keep><i data-lucide="trash-2"></i></button></form>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $users->links() }}
</div>

<div class="modal" id="newUserModal"><div class="modal-backdrop"></div>
    <form class="modal-box" method="POST" action="{{ route('users.store') }}">@csrf
        <button type="button" class="icon-btn modal-close" data-close><i data-lucide="x"></i></button>
        <h3 class="modal-title">Buat akun baru</h3>
        <div class="stack mt-2" style="gap:14px">
            <div class="field"><label class="label">Nama</label><input class="input" name="name" required></div>
            <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" required></div>
            <div class="form-grid">
                <div class="field"><label class="label">Peran</label><select class="select" name="role">@foreach (config('signal.roles') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label class="label">Instansi</label><input class="input" name="institution"></div>
            </div>
            <div class="field"><label class="label">Kata sandi awal</label><input class="input" type="password" name="password" required minlength="8"></div>
            <div class="row" style="justify-content:flex-end"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary" type="submit">Buat akun</button></div>
        </div>
    </form>
</div>
@endsection
