@extends('layouts.app')
@section('title', 'Peta Konektivitas')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Geospasial interaktif</span>
        <h1 class="page-title">Peta Konektivitas Asia Tenggara</h1>
        <p class="page-sub">Titik laporan warga, lingkaran kesenjangan per wilayah (Connectivity Gap Score), dan lokasi proyek infrastruktur.</p>
    </div>
    <div class="map-legend">
        <span><i style="background:#60e1bd"></i>Rendah</span>
        <span><i style="background:#f2cc72"></i>Sedang</span>
        <span><i style="background:#ffb27a"></i>Tinggi</span>
        <span><i style="background:#ff8f83"></i>Kritis</span>
        <span><i style="background:#b99aff"></i>Proyek</span>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <select class="select" id="fCountry"><option value="">🌏 Semua negara</option>@foreach ($countries as $c)<option value="{{ $c->id }}" data-lat="{{ $c->latitude }}" data-lng="{{ $c->longitude }}">{{ $c->flag() }} {{ $c->name }}</option>@endforeach</select>
        <select class="select" id="fIssue"><option value="">Semua masalah</option>@foreach (config('signal.issue_types') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
        <label class="chip active" id="tReports"><input type="checkbox" checked hidden><i data-lucide="radio-tower"></i> Laporan (<span id="cReports">0</span>)</label>
        <label class="chip active" id="tRegions"><input type="checkbox" checked hidden><i data-lucide="circle-dot"></i> Gap wilayah</label>
        <label class="chip active" id="tProjects"><input type="checkbox" checked hidden><i data-lucide="hard-hat"></i> Proyek (<span id="cProjects">0</span>)</label>
        <button class="btn btn-sm" id="resetView" style="margin-left:auto"><i data-lucide="compass"></i> Reset tampilan</button>
    </div>
    <div id="atlasMap" class="map"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = SA.initMap('atlasMap', [6, 112], 4);
    const layers = { reports: L.layerGroup().addTo(map), regions: L.layerGroup().addTo(map), projects: L.layerGroup().addTo(map) };

    const bindToggle = (id, layer) => {
        const el = document.getElementById(id);
        el.onclick = () => { el.classList.toggle('active'); el.classList.contains('active') ? layer.addTo(map) : map.removeLayer(layer); };
    };
    bindToggle('tReports', layers.reports); bindToggle('tRegions', layers.regions); bindToggle('tProjects', layers.projects);

    const load = async () => {
        const q = new URLSearchParams({ country: SA.$('#fCountry').value, issue: SA.$('#fIssue').value });
        const d = await SA.request(`{{ route('map.data') }}?${q}`);
        Object.values(layers).forEach(l => l.clearLayers());
        SA.$('#cReports').textContent = d.reports.length;
        SA.$('#cProjects').textContent = d.projects.length;

        d.regions.forEach(r => {
            if (!r.gap) return;
            const c = SA.levelColor[r.level];
            L.circleMarker([r.lat, r.lng], { radius: 8 + r.gap / 5, color: c, fillColor: c, fillOpacity: .16, weight: 1.5, dashArray: r.handled ? '4 4' : null })
                .bindPopup(`<b>${r.name}</b> <span class="muted">(${r.country})</span><br>Tipe: ${r.area}<br>Gap Score: <b style="color:${c}">${r.gap}/100</b> · ${r.reports} laporan aktif${r.handled ? '<br><span style="color:#b99aff">✓ Sudah ada proyek aktif</span>' : ''}`)
                .addTo(layers.regions);
        });

        d.reports.forEach(r => {
            const c = SA.levelColor[r.level];
            L.marker([r.lat, r.lng], { icon: L.divIcon({ className: '', html: `<div class="pulse-marker" style="--c:${c}"></div>`, iconSize: [14, 14] }) })
                .bindPopup(`<b class="mono" style="color:${c}">${r.code}</b> · ${r.status_label}<br><b>${r.region}</b><br>${r.issue} · ${r.provider} (${r.tech})<br>${r.download !== null ? `Unduh: <b>${r.download} Mbps</b> · ` : ''}Skor: <b>${r.score}</b><br><span class="muted">${r.date}</span>${r.url ? `<br><a href="${r.url}" style="color:#60e1bd;font-weight:700">Lihat detail →</a>` : ''}`)
                .addTo(layers.reports);
        });

        d.projects.forEach(p => {
            L.marker([p.lat, p.lng], { icon: L.divIcon({ className: '', html: `<div style="width:24px;height:24px;border-radius:8px;background:#b99aff;color:#120a24;display:grid;place-items:center;font-weight:900;box-shadow:0 0 16px #b99aff">★</div>`, iconSize: [24, 24] }) })
                .bindPopup(`<b style="color:#b99aff">${p.code}</b> · ${p.status}<br><b>${p.title}</b><br>${p.type} · Progres <b>${p.progress}%</b>`)
                .addTo(layers.projects);
        });
    };

    SA.$('#fCountry').onchange = (e) => {
        const o = e.target.selectedOptions[0];
        o?.dataset.lat ? map.flyTo([o.dataset.lat, o.dataset.lng], 6, { duration: 1.4 }) : map.flyTo([6, 112], 4, { duration: 1.4 });
        load();
    };
    SA.$('#fIssue').onchange = load;
    SA.$('#resetView').onclick = () => { SA.$('#fCountry').value = ''; SA.$('#fIssue').value = ''; map.flyTo([6, 112], 4); load(); };
    load();
});
</script>
@endpush
