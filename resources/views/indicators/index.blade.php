@extends('layouts.app')
@section('title', 'Data Indikator')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Dataset resmi</span>
        <h1 class="page-title">Data Indikator Tahunan</h1>
        <p class="page-sub">Kelola seri waktu indikator konektivitas yang menjadi dasar perhitungan Digital Connectivity Index.</p>
    </div>
    <div class="head-actions">
        <a href="{{ route('data.index') }}" class="btn"><i data-lucide="upload"></i> Impor CSV</a>
        <a href="{{ route('indicators.export', request()->query()) }}" class="btn"><i data-lucide="download"></i> Ekspor CSV</a>
        <a href="{{ route('indicators.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Tambah Data</a>
    </div>
</div>

<div class="card">
    <form class="toolbar" method="GET">
        <select class="select" name="country" onchange="this.form.submit()"><option value="">Semua negara</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country') == $c->id)>{{ $c->flag() }} {{ $c->name }}</option>@endforeach</select>
        <select class="select" name="year" onchange="this.form.submit()"><option value="">Semua tahun</option>@foreach ($years as $y)<option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>@endforeach</select>
        @if (request('country') || request('year'))<a href="{{ route('indicators.index') }}" class="btn btn-sm btn-ghost">Reset</a>@endif
    </form>

    <div class="table-wrap"><table class="table">
        <thead><tr><th>Negara</th><th>Tahun</th><th class="num">Internet (%)</th><th class="num">Mobile BB</th><th class="num">Fixed BB</th><th class="num">Speed Mobile</th><th class="num">Speed Fixed</th><th class="num">Harga/GB</th><th>Sumber</th><th></th></tr></thead>
        <tbody>
        @foreach ($indicators as $i)
            <tr>
                <td><b>{{ $i->country->flag() }} {{ $i->country->name }}</b></td>
                <td class="mono">{{ $i->year }}</td>
                <td class="num">{{ $i->internet_users_pct }}%</td>
                <td class="num">{{ $i->mobile_broadband_per100 ?? '—' }}</td>
                <td class="num">{{ $i->fixed_broadband_per100 ?? '—' }}</td>
                <td class="num">{{ $i->avg_mobile_speed_mbps ?? '—' }}</td>
                <td class="num">{{ $i->avg_fixed_speed_mbps ?? '—' }}</td>
                <td class="num">${{ $i->data_price_usd_gb ?? '—' }}</td>
                <td class="muted" style="font-size:11.5px;max-width:200px">{{ $i->data_source }}</td>
                <td>
                    <div class="actions">
                        <a href="{{ route('indicators.edit', $i) }}" class="btn btn-xs"><i data-lucide="pencil"></i></a>
                        <form method="POST" action="{{ route('indicators.destroy', $i) }}" data-confirm="Hapus data {{ $i->country->name }} {{ $i->year }}?">@csrf @method('DELETE')<button class="btn btn-xs btn-danger" data-keep><i data-lucide="trash-2"></i></button></form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $indicators->links() }}
</div>
@endsection
