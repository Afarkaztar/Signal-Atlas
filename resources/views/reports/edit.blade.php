@extends('layouts.app')
@section('title', 'Ubah '.$report->code)

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">{{ $report->code }}</span>
        <h1 class="page-title">Ubah laporan</h1>
        <p class="page-sub">Skor keparahan akan dihitung ulang otomatis setelah disimpan.</p>
    </div>
    <span class="badge b-{{ $report->status }}">{{ $report->statusLabel() }}</span>
</div>
<form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('reports._form')
</form>
@endsection
