@extends('layouts.app')
@section('title', 'Laporan Baru')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Formulir pelaporan</span>
        <h1 class="page-title">Laporkan masalah sinyal</h1>
        <p class="page-sub">Laporan Anda akan ditinjau analis. Setelah terverifikasi, laporan ikut dihitung dalam skor prioritas pembangunan wilayah.</p>
    </div>
</div>
<form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
    @csrf
    @include('reports._form')
</form>
@endsection
