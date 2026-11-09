<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Eksekutif · SignalAtlas</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body style="background:#fff;color:#111">
<div class="print-page">
    <div class="no-print" style="display:flex;justify-content:space-between;margin-bottom:20px;padding:12px 16px;background:#0f1828;color:#fff;border-radius:12px">
        <span>Gunakan dialog cetak browser (Ctrl+P) → <b>Save as PDF</b></span>
        <button onclick="window.print()" style="background:#60e1bd;color:#04140f;border:0;padding:8px 14px;border-radius:8px;font-weight:700">Cetak / Simpan PDF</button>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2px solid #111;padding-bottom:12px;margin-bottom:20px">
        <div>
            <div style="font-size:11px;letter-spacing:.15em;text-transform:uppercase;color:#555">SignalAtlas · Laporan Eksekutif</div>
            <h1>Lanskap & Prioritas Konektivitas Asia Tenggara</h1>
        </div>
        <div style="text-align:right;font-size:12px;color:#555">Dicetak: {{ now()->translatedFormat('d F Y, H:i') }}</div>
    </div>

    <h3>1. Peringkat Digital Connectivity Index (DCI)</h3>
    <table>
        <thead><tr><th>#</th><th>Negara</th><th>Internet (%)</th><th>Mobile BB</th><th>Fixed BB</th><th>Speed (M/F Mbps)</th><th>Harga/GB</th><th>Skor DCI</th><th>Grade</th></tr></thead>
        <tbody>
        @foreach ($ranking as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td><td>{{ $r['country']->name }}</td><td>{{ $r['indicator']->internet_users_pct }}%</td>
                <td>{{ $r['indicator']->mobile_broadband_per100 }}</td><td>{{ $r['indicator']->fixed_broadband_per100 }}</td>
                <td>{{ $r['indicator']->avg_mobile_speed_mbps }} / {{ $r['indicator']->avg_fixed_speed_mbps }}</td>
                <td>${{ $r['indicator']->data_price_usd_gb }}</td><td><b>{{ $r['score'] }}</b></td><td>{{ $r['grade'][0] }} ({{ $r['grade'][1] }})</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>2. 10 Wilayah Prioritas Pembangunan (Connectivity Gap Score)</h3>
    <table>
        <thead><tr><th>#</th><th>Wilayah</th><th>Negara</th><th>Tipe</th><th>Laporan Aktif</th><th>Gap Score</th><th>Rekomendasi</th><th>Estimasi Biaya</th></tr></thead>
        <tbody>
        @foreach ($priorities as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td><td>{{ $p['region']->name }}</td><td>{{ $p['region']->country->name }}</td><td>{{ $p['region']->areaLabel() }}</td>
                <td>{{ $p['active_reports'] }}</td><td><b>{{ $p['effective_score'] }}</b></td>
                <td>{{ config('signal.project_types.'.$p['recommended_type']) }}</td><td>${{ number_format($p['estimated_cost']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>3. Daftar Proyek Infrastruktur</h3>
    <table>
        <thead><tr><th>Kode</th><th>Judul</th><th>Wilayah</th><th>Status</th><th>Progres</th><th>Anggaran (USD)</th><th>Penerima Manfaat</th></tr></thead>
        <tbody>
        @foreach ($projects as $p)
            <tr>
                <td>{{ $p->code }}</td><td>{{ $p->title }}</td><td>{{ $p->region->name }}, {{ $p->region->country->name }}</td>
                <td>{{ $p->statusLabel() }}</td><td>{{ $p->progress }}%</td><td>${{ number_format($p->budget_usd) }}</td><td>{{ number_format($p->beneficiaries) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
