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
    <p class="page-sub mb-3">Belum punya akun? <a href="{{ route('register') }}" style="color:var(--mint);font-weight:700">Daftar gratis</a></p>

    <form method="POST" action="{{ route('login') }}" class="stack" style="gap:16px">
        @csrf
        <div class="field">
            <label class="label" for="email">Email</label>
            <div class="input-icon"><i data-lucide="mail"></i>
                <input id="email" class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus></div>
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

    <div class="mt-3">
        <div class="nav-label" style="padding-left:0">Akun demo (klik untuk mengisi · sandi: password)</div>
        <div class="demo-accounts">
            @foreach ([['admin@signalatlas.id', 'Administrator', 'crown', 'var(--yellow)'], ['analis@signalatlas.id', 'Analis / Verifikator', 'shield-check', 'var(--blue)'], ['kontributor@signalatlas.id', 'Kontributor Publik', 'user', 'var(--mint)']] as [$email, $role, $icon, $c])
                <button type="button" class="demo-acc" data-email="{{ $email }}">
                    <i data-lucide="{{ $icon }}" style="color:{{ $c }}"></i>
                    <span><b>{{ $role }}</b><br>{{ $email }}</span>
                    <i data-lucide="arrow-right" style="margin-left:auto;width:15px"></i>
                </button>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.demo-acc').forEach(b => b.onclick = () => {
        document.getElementById('email').value = b.dataset.email;
        document.getElementById('password').value = 'password';
        SA.toast('Akun demo terisi — klik Masuk', 'info');
    });
</script>
@endpush
