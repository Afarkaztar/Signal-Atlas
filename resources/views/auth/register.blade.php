@extends('layouts.auth')
@section('title', 'Daftar')
@section('art-eyebrow', 'Bergabung sebagai kontributor')
@section('art-title')
    Jadilah <span class="grad-text">mata & telinga</span><br>konektivitas<br>di daerahmu.
@endsection
@section('art-text', 'Kirim laporan, kumpulkan poin reputasi, naik level dari Scout hingga Sentinel, dan lihat bagaimana laporanmu menjadi proyek nyata.')

@section('form')
    <span class="eyebrow">Akun baru</span>
    <h2 class="page-title">Buat akun kontributor</h2>
    <p class="page-sub mb-3">Sudah punya akun? <a href="{{ route('login') }}" style="color:var(--mint);font-weight:700">Masuk</a></p>

    <form method="POST" action="{{ route('register') }}" class="stack" style="gap:15px">
        @csrf
        <div class="field">
            <label class="label">Nama lengkap</label>
            <div class="input-icon"><i data-lucide="user"></i><input class="input @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="Nama Anda"></div>
            @error('name')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label class="label">Email</label>
            <div class="input-icon"><i data-lucide="mail"></i><input class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required placeholder="nama@email.com"></div>
            @error('email')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label class="label">Instansi / Kampus <span class="hint">opsional</span></label>
            <div class="input-icon"><i data-lucide="building-2"></i><input class="input" name="institution" value="{{ old('institution') }}" placeholder="IPB University"></div>
        </div>
        <div class="form-grid">
            <div class="field">
                <label class="label">Kata sandi</label>
                <input id="pw" class="input @error('password') is-invalid @enderror" type="password" name="password" required placeholder="Min. 8 karakter">
            </div>
            <div class="field">
                <label class="label">Konfirmasi</label>
                <input class="input" type="password" name="password_confirmation" required placeholder="Ulangi sandi">
            </div>
        </div>
        <div>
            <div class="progress thin"><span id="pwBar" style="width:0;animation:none"></span></div>
            <div class="row between mt-1" style="font-size:11.5px"><span class="muted">Kekuatan sandi</span><b id="pwLabel" class="dim">—</b></div>
            @error('password')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <label class="check"><input type="checkbox" name="terms" {{ old('terms') ? 'checked' : '' }}> Saya menyetujui pedoman pelaporan yang jujur & bertanggung jawab</label>
        @error('terms')<span class="error-text">{{ $message }}</span>@enderror
        <button class="btn btn-primary btn-lg btn-block" type="submit"><i data-lucide="sparkles"></i> Daftar & mulai</button>
    </form>
@endsection

@push('scripts')
<script>
    const pw = document.getElementById('pw'), bar = document.getElementById('pwBar'), lbl = document.getElementById('pwLabel');
    pw.addEventListener('input', () => {
        const v = pw.value; let s = 0;
        if (v.length >= 8) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
        const map = [['—', '#5e6d84'], ['Lemah', '#ff8f83'], ['Cukup', '#f2cc72'], ['Kuat', '#76a9ff'], ['Sangat kuat', '#60e1bd']];
        bar.style.width = (s / 4 * 100) + '%'; bar.style.background = map[s][1]; lbl.textContent = map[s][0]; lbl.style.color = map[s][1];
    });
</script>
@endpush
