@extends('layouts.app')
@section('title', 'Ubah '.$project->code)
@section('content')
<div class="page-head"><div><span class="eyebrow">{{ $project->code }}</span><h1 class="page-title">Ubah detail proyek</h1></div></div>
<form method="POST" action="{{ route('projects.update', $project) }}">@csrf @method('PUT') @include('projects._form')</form>
@endsection
