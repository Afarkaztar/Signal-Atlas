@extends('layouts.auth')
@section('title', 'Masuk')
@section('art-eyebrow', 'Selamat datang kembali')
@section('art-title')
    Setiap laporan<br>mendekatkan <span class="grad-text">sinyal</span><br>ke rumah warga.
@endsection
@section('art-text', 'Masuk untuk memantau laporan Anda, meninjau antrean verifikasi, dan mengelola proyek infrastruktur di seluruh Asia Tenggara.')

@section('form')
    <span class="eyebrow">Masuk akun</span>
    <h2 class="page-title">Masuk ke SignalAtlas</h2>
    <p class="page-sub mb-3">Belum punya akun? <a href="{{ route('register') }}" style="color:var(--mint);font-weight:700">Daftar akun baru</a></p>

    <form method="POST" action="{{ route('login') }}" class="stack" style="gap:16px">
        @csrf
        <div class="field">
            <label class="label" for="email">Email</label>
            <div class="input-icon"><i data-lucide="mail"></i>
                <input id="email" class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="nama@apps.ipb.ac.id" required autofocus></div>
            @error('email')<span class="error-text"><i data-lucide="alert-circle" style="width:13px"></i>{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label class="label" for="password">Kata sandi</label>
            <div class="input-icon" style="position:relative"><i data-lucide="lock"></i>
                <input id="password" class="input" type="password" name="password" placeholder="••••••••" required>
                <button type="button" class="btn btn-ghost btn-xs" data-toggle-pass="password" style="position:absolute;right:6px;top:6px"><i data-lucide="eye"></i></button>
            </div>
        </div>
        <label class="check"><input type="checkbox" name="remember"> Ingat saya di perangkat ini</label>
        <button class="btn btn-primary btn-lg btn-block" type="submit"><i data-lucide="log-in"></i> Masuk</button>
    </form>
@endsection
