@extends('layouts.app')
@section('title', 'Akses Ditolak')
@section('content')
<div class="card empty">
    <div class="empty-icon" style="background:#ff8f8318;color:var(--red)"><i data-lucide="shield-alert"></i></div>
    <h3>403 · Akses Ditolak</h3>
    <p>{{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-2"><i data-lucide="arrow-left"></i> Kembali ke Dashboard</a>
</div>
@endsection
