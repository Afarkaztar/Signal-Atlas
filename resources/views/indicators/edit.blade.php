@extends('layouts.app')
@section('title', 'Ubah Indikator')
@section('content')
<div class="page-head"><div><span class="eyebrow">{{ $indicator->country->name }} · {{ $indicator->year }}</span><h1 class="page-title">Ubah indikator</h1></div></div>
<form method="POST" action="{{ route('indicators.update', $indicator) }}">@csrf @method('PUT') @include('indicators._form')</form>
@endsection
