@extends('layouts.app')
@section('title', 'Pusat Data')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Interoperabilitas data</span>
        <h1 class="page-title">Pusat Impor & Ekspor Data</h1>
        <p class="page-sub">Impor massal dari CSV (dengan validasi per-baris & mode dry-run), unduh template, ekspor dataset, atau cetak ringkasan eksekutif PDF.</p>
    </div>
    <div class="head-actions">
        <a href="#db-inspector" class="btn"><i data-lucide="database-zap"></i> Inspektur Database</a>
        @if ($dbInfo['is_sqlite'])
            <a href="{{ route('data.sqlite') }}" class="btn"><i data-lucide="hard-drive-download"></i> Unduh .sqlite</a>
        @endif
        <a href="{{ route('data.print') }}" target="_blank" class="btn"><i data-lucide="printer"></i> Ringkasan Cetak / PDF</a>
        <a href="{{ route('data.backup') }}" class="btn btn-violet"><i data-lucide="archive"></i> Backup JSON</a>
    </div>
</div>

@include('partials.errors')

@if ($res = session('importResult'))
    <div class="card glow-border mb-3">
        <div class="row between wrap">
            <div>
                <span class="eyebrow">{{ $res['dry_run'] ? 'Hasil simulasi (dry-run)' : 'Hasil impor' }}</span>
                <h3 style="margin:6px 0 0">{{ $res['total'] }} baris diperiksa dari file CSV</h3>
            </div>
            <div class="row" style="gap:18px">
                <div><span class="muted">Ditambahkan</span><div style="font-size:24px;font-weight:800;color:var(--mint)">{{ $res['created'] }}</div></div>
                <div><span class="muted">Diperbarui</span><div style="font-size:24px;font-weight:800;color:var(--blue)">{{ $res['updated'] }}</div></div>
                <div><span class="muted">Dilewati</span><div style="font-size:24px;font-weight:800;color:var(--yellow)">{{ $res['skipped'] }}</div></div>
                <div><span class="muted">Gagal</span><div style="font-size:24px;font-weight:800;color:var(--red)">{{ count($res['errors']) }}</div></div>
            </div>
        </div>
        @if ($res['errors'])
            <div class="alert alert-danger mt-2" style="margin-bottom:0"><i data-lucide="alert-triangle"></i>
                <div><b>Detail baris yang gagal divalidasi:</b>
                    <ul>@foreach (array_slice($res['errors'], 0, 12) as $e)<li>Baris {{ $e['line'] }}: {{ $e['message'] }}</li>@endforeach</ul>
                </div>
            </div>
        @endif
    </div>
@endif

<div class="grid g-2 mb-3">
    {{-- Impor Indikator --}}
    <form class="card" method="POST" action="{{ route('data.import', 'indicators') }}" enctype="multipart/form-data">
        @csrf
        <div class="card-head">
            <div><h3 class="card-title"><i data-lucide="database"></i> Impor Indikator Tahunan</h3><div class="card-sub">{{ $counts['indicators'] }} catatan tersimpan</div></div>
            <a href="{{ route('data.template', 'indicators') }}" class="btn btn-xs"><i data-lucide="file-down"></i> Template CSV</a>
        </div>
        <label class="dropzone">
            <input type="file" name="file" accept=".csv,.txt" required>
            <div class="dz-icon"><i data-lucide="file-spreadsheet"></i></div>
            <b style="color:var(--text)">Pilih atau seret file CSV indikator</b>
            <div style="font-size:12px">Kolom: iso3, year, internet_users_pct, mobile_broadband_per100, …</div>
            <div class="dz-file"></div>
        </label>
        <div class="row between wrap mt-2">
            <select class="select" name="mode" style="width:auto">
                <option value="skip">Jika duplikat: lewati</option>
                <option value="upsert">Jika duplikat: timpa (upsert)</option>
            </select>
            <label class="check"><input type="checkbox" name="dry_run" value="1"> Mode dry-run (validasi saja)</label>
            <button class="btn btn-primary" type="submit"><i data-lucide="upload"></i> Proses CSV</button>
        </div>
    </form>

    {{-- Impor Laporan --}}
    <form class="card" method="POST" action="{{ route('data.import', 'reports') }}" enctype="multipart/form-data">
        @csrf
        <div class="card-head">
            <div><h3 class="card-title"><i data-lucide="radio-tower"></i> Impor Laporan Lapangan</h3><div class="card-sub">Skor keparahan dihitung otomatis untuk setiap baris</div></div>
            <a href="{{ route('data.template', 'reports') }}" class="btn btn-xs"><i data-lucide="file-down"></i> Template CSV</a>
        </div>
        <label class="dropzone">
            <input type="file" name="file" accept=".csv,.txt" required>
            <div class="dz-icon"><i data-lucide="file-up"></i></div>
            <b style="color:var(--text)">Pilih atau seret file CSV laporan</b>
            <div style="font-size:12px">Kolom: iso3, region, provider, technology, issue_type, …</div>
            <div class="dz-file"></div>
        </label>
        <div class="row between wrap mt-2">
            <label class="check"><input type="checkbox" name="dry_run" value="1"> Mode dry-run (validasi saja)</label>
            <button class="btn btn-primary" type="submit"><i data-lucide="upload"></i> Proses CSV</button>
        </div>
    </form>
</div>

<div class="grid g-main">
    <div class="card">
        <h3 class="card-title mb-2"><i data-lucide="download"></i> Ekspor dataset</h3>
        <div class="list">
            <div class="list-item"><span class="list-icon" style="color:var(--mint)"><i data-lucide="database"></i></span><div class="list-body"><div class="list-title">Indikator Konektivitas ({{ $counts['indicators'] }} baris)</div><div class="list-sub">Seluruh negara & tahun dalam format CSV UTF-8</div></div><a href="{{ route('indicators.export') }}" class="btn btn-sm">Unduh CSV</a></div>
            <div class="list-item"><span class="list-icon" style="color:var(--blue)"><i data-lucide="radio-tower"></i></span><div class="list-body"><div class="list-title">Laporan Sinyal ({{ $counts['reports'] }} baris)</div><div class="list-sub">Lengkap dengan koordinat, skor keparahan, dan status</div></div><a href="{{ route('reports.export') }}" class="btn btn-sm">Unduh CSV</a></div>
            <div class="list-item"><span class="list-icon" style="color:var(--violet)"><i data-lucide="hard-hat"></i></span><div class="list-body"><div class="list-title">Proyek Infrastruktur ({{ $counts['projects'] }} baris)</div><div class="list-sub">Anggaran, progres, dan penerima manfaat</div></div><a href="{{ route('projects.export') }}" class="btn btn-sm">Unduh CSV</a></div>
            <div class="list-item"><span class="list-icon" style="color:var(--yellow)"><i data-lucide="printer"></i></span><div class="list-body"><div class="list-title">Ringkasan Eksekutif (Siap Cetak / Simpan PDF)</div><div class="list-sub">Peringkat DCI, 10 wilayah prioritas, dan daftar proyek</div></div><a href="{{ route('data.print') }}" target="_blank" class="btn btn-sm">Buka</a></div>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title mb-2"><i data-lucide="history"></i> Riwayat impor & ekspor</h3>
        <div class="timeline">
            @forelse ($history as $h)
                <div class="tl-item"><div class="tl-title">{{ $h->description }}</div><div class="tl-meta">{{ $h->user?->name }} · {{ $h->created_at->diffForHumans() }}</div></div>
            @empty
                <p class="muted">Belum ada riwayat.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ================= INSPEKTUR DATABASE LIVE ================= --}}
<div class="card mt-3" id="db-inspector">
    <div class="card-head">
        <div>
            <h3 class="card-title"><i data-lucide="database-zap"></i> Inspektur Database Live</h3>
            <div class="card-sub">
                Driver aktif: <b class="mono" style="color:var(--mint)">{{ strtoupper($dbInfo['driver']) }}</b>
                · Database: <b class="mono">{{ $dbInfo['database'] }}</b>
                · Host: <span class="mono">{{ $dbInfo['host'] }}</span>
                — menampilkan 25 baris terbaru dari tabel yang dipilih.
            </div>
        </div>
    </div>

    <div class="filter-pills">
        @foreach ($allowedTables as $tbl => $lbl)
            <a href="{{ route('data.index', ['table' => $tbl]).'#db-inspector' }}" class="chip {{ $inspectTable === $tbl ? 'active' : '' }}">
                <span class="mono">{{ $tbl }}</span> <b>{{ $tableCounts[$tbl] ?? 0 }}</b>
            </a>
        @endforeach
    </div>

    @if ($previewRows->isEmpty())
        <div class="empty" style="padding:30px">
            <div class="empty-icon"><i data-lucide="table-2"></i></div>
            <h3>Tabel <span class="mono">{{ $inspectTable }}</span> masih kosong (0 baris)</h3>
            <p>Data yang ditambahkan melalui aplikasi akan langsung muncul di sini secara real-time.</p>
        </div>
    @else
        @php $columns = array_keys($previewRows->first()); @endphp
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($columns as $col)
                            <th class="mono">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previewRows as $row)
                        <tr>
                            @foreach ($columns as $col)
                                <td class="mono" style="font-size:11.5px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ is_scalar($row[$col]) ? $row[$col] : json_encode($row[$col]) }}">
                                    {{ is_scalar($row[$col]) || is_null($row[$col]) ? ($row[$col] ?? 'NULL') : json_encode($row[$col]) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
