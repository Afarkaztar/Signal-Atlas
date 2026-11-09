@extends('layouts.app')
@section('title', 'Tambah Indikator')
@section('content')
<div class="page-head"><div><span class="eyebrow">Dataset</span><h1 class="page-title">Tambah indikator tahunan</h1></div></div>
<form method="POST" action="{{ route('indicators.store') }}">@csrf @include('indicators._form')</form>
@endsection
