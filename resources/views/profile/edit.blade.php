@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
@php [$badgeName, $badgeColor] = $user->badge(); @endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Pengaturan akun</span>
        <h1 class="page-title">Profil & Keamanan</h1>
    </div>
</div>

@include('partials.errors')

<div class="grid g-side">
    <div class="stack">
        <form class="card" method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PUT')
            <h3 class="card-title mb-2"><i data-lucide="user"></i> Informasi pribadi</h3>
            <div class="form-grid">
                <div class="field"><label class="label">Nama lengkap</label><input class="input" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
                <div class="field"><label class="label">Instansi / Kampus</label><input class="input" name="institution" value="{{ old('institution', $user->institution) }}"></div>
                <div class="field"><label class="label">Nomor telepon</label><input class="input" name="phone" value="{{ old('phone', $user->phone) }}"></div>
            </div>
            <div class="row mt-2" style="justify-content:flex-end"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Simpan profil</button></div>
        </form>

        <form class="card" method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')
            <h3 class="card-title mb-2"><i data-lucide="key-round"></i> Ganti kata sandi</h3>
            <div class="form-grid">
                <div class="field full"><label class="label">Kata sandi saat ini</label><input class="input" type="password" name="current_password" required></div>
                <div class="field"><label class="label">Kata sandi baru</label><input class="input" type="password" name="password" required minlength="8"></div>
                <div class="field"><label class="label">Konfirmasi kata sandi baru</label><input class="input" type="password" name="password_confirmation" required></div>
            </div>
            <div class="row mt-2" style="justify-content:flex-end"><button class="btn" type="submit"><i data-lucide="lock"></i> Perbarui kata sandi</button></div>
        </form>
    </div>

    <div class="card glow-border" style="text-align:center">
        <div class="avatar lg" style="margin:0 auto 12px">{{ $user->initials() }}</div>
        <h3 style="margin:0">{{ $user->name }}</h3>
        <div class="muted" style="font-size:12.5px">{{ $user->roleLabel() }}</div>
        <span class="badge no-dot mt-1" style="color:{{ $badgeColor }};border-color:{{ $badgeColor }}55">★ {{ $badgeName }} · {{ $user->points }} poin</span>
        <div class="grid g-3 mt-3">
            <div class="detail-item"><div class="k">Laporan</div><div class="v">{{ $stats['reports'] }}</div></div>
            <div class="detail-item"><div class="k">Valid</div><div class="v">{{ $stats['verified'] }}</div></div>
            <div class="detail-item"><div class="k">Selesai</div><div class="v">{{ $stats['resolved'] }}</div></div>
        </div>
    </div>
</div>
@endsection
