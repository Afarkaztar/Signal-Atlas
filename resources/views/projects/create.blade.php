@extends('layouts.app')
@section('title', 'Usulkan Proyek')
@section('content')
<div class="page-head"><div><span class="eyebrow">Perencanaan</span><h1 class="page-title">Usulkan Proyek Infrastruktur</h1><p class="page-sub">Skor prioritas dihitung otomatis dari Connectivity Gap Score wilayah sasaran.</p></div></div>
<form method="POST" action="{{ route('projects.store') }}">@csrf @include('projects._form')</form>
@endsection
