@extends('layouts.app')
@section('title', 'Halaman Tidak Ditemukan')
@section('content')
<div class="card empty">
    <div class="empty-icon"><i data-lucide="compass"></i></div>
    <h3>404 · Halaman Tidak Ditemukan</h3>
    <p>Alamat yang Anda tuju tidak tersedia atau telah dipindahkan.</p>
    <a href="{{ route('home') }}" class="btn btn-primary mt-2"><i data-lucide="home"></i> Ke Beranda</a>
</div>
@endsection
