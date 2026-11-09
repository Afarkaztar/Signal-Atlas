@php
    $selectedRegion = old('region_id', $report->region_id);
    $selectedCountry = old('country_id', optional($countries->first(fn ($c) => $c->regions->contains('id', $selectedRegion)))->id);
    $regionsJson = $countries->mapWithKeys(fn ($c) => [$c->id => $c->regions->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'area' => $r->areaLabel(), 'lat' => $r->latitude, 'lng' => $r->longitude])]);
    $techIcons = ['none' => 'wifi-off', '2g' => 'signal-low', '3g' => 'signal-medium', '4g' => 'signal-high', '5g' => 'signal', 'fiber' => 'cable'];
    $issueIcons = ['blank_spot' => 'wifi-off', 'outage' => 'zap-off', 'unstable' => 'activity', 'slow' => 'snail', 'expensive' => 'wallet'];
@endphp

@include('partials.errors')

<div class="grid g-side">
    <div class="stack">
        {{-- Langkah 1: lokasi --}}
        <div class="card" data-reveal>
            <div class="card-head"><div><h3 class="card-title"><span class="badge b-neutral no-dot">1</span> Lokasi masalah</h3><div class="card-sub">Pilih negara & wilayah, lalu klik peta untuk menandai titik tepatnya.</div></div>
                <button type="button" class="btn btn-sm" id="geoBtn"><i data-lucide="locate-fixed"></i> Lokasi saya</button></div>
            <div class="form-grid">
                <div class="field">
                    <label class="label">Negara</label>
                    <select class="select" id="countrySelect" name="country_id" required>
                        <option value="">— Pilih negara —</option>
                        @foreach ($countries as $c)<option value="{{ $c->id }}" @selected($selectedCountry == $c->id)>{{ $c->flag() }} {{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label">Wilayah</label>
                    <select class="select @error('region_id') is-invalid @enderror" id="regionSelect" name="region_id" required><option value="">— Pilih negara dahulu —</option></select>
                </div>
                <div class="field full"><div id="pickMap" class="map sm"></div></div>
                <div class="field"><label class="label">Latitude</label><input class="input mono" id="lat" name="latitude" value="{{ old('latitude', $report->latitude) }}" readonly placeholder="Klik peta"></div>
                <div class="field"><label class="label">Longitude</label><input class="input mono" id="lng" name="longitude" value="{{ old('longitude', $report->longitude) }}" readonly placeholder="Klik peta"></div>
            </div>
        </div>

        {{-- Langkah 2: jaringan --}}
        <div class="card" data-reveal>
            <div class="card-head"><div><h3 class="card-title"><span class="badge b-neutral no-dot">2</span> Kondisi jaringan</h3><div class="card-sub">Informasi teknis menentukan skor keparahan laporan.</div></div>
                @if (($prefill['source'] ?? null) === 'speedtest')<span class="badge b-resolved"><i data-lucide="gauge" style="width:12px"></i> Dari speed test</span>@endif</div>
            <input type="hidden" name="source" value="{{ $prefill['source'] ?? 'manual' }}">
            <div class="field mb-2">
                <label class="label">Jenis masalah</label>
                <div class="choice-grid">
                    @foreach (config('signal.issue_types') as $k => $v)
                        <label class="choice"><input type="radio" name="issue_type" value="{{ $k }}" @checked(old('issue_type', $report->issue_type) === $k) data-calc><span><i data-lucide="{{ $issueIcons[$k] }}"></i>{{ $v }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="field mb-2">
                <label class="label">Teknologi yang tersedia</label>
                <div class="choice-grid" style="grid-template-columns:repeat(auto-fill,minmax(110px,1fr))">
                    @foreach (config('signal.technologies') as $k => $v)
                        <label class="choice"><input type="radio" name="technology" value="{{ $k }}" @checked(old('technology', $report->technology) === $k) data-calc><span><i data-lucide="{{ $techIcons[$k] }}"></i>{{ $v }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label class="label">Provider / operator</label>
                    <input class="input" name="provider" list="providers" value="{{ old('provider', $report->provider) }}" required placeholder="Contoh: Telkomsel">
                    <datalist id="providers">@foreach (config('signal.providers') as $p)<option value="{{ $p }}">@endforeach</datalist>
                </div>
                <div class="field"><label class="label">Kecepatan unduh <span class="hint">Mbps</span></label><input class="input" type="number" step="0.01" min="0" name="download_mbps" value="{{ old('download_mbps', $report->download_mbps) }}" placeholder="Kosongkan jika tidak ada sinyal" data-calc></div>
                <div class="field"><label class="label">Kecepatan unggah <span class="hint">Mbps</span></label><input class="input" type="number" step="0.01" min="0" name="upload_mbps" value="{{ old('upload_mbps', $report->upload_mbps) }}" placeholder="opsional"></div>
                <div class="field"><label class="label">Latensi / ping <span class="hint">ms</span></label><input class="input" type="number" min="0" name="latency_ms" value="{{ old('latency_ms', $report->latency_ms) }}" placeholder="opsional" data-calc></div>
            </div>
            <a href="{{ route('speedtest') }}" class="muted" style="font-size:12px;display:inline-flex;gap:6px;margin-top:12px;align-items:center"><i data-lucide="gauge" style="width:14px"></i> Belum tahu kecepatannya? Jalankan speed test</a>
        </div>

        {{-- Langkah 3: deskripsi --}}
        <div class="card" data-reveal>
            <div class="card-head"><div><h3 class="card-title"><span class="badge b-neutral no-dot">3</span> Deskripsi & bukti</h3></div></div>
            <div class="field mb-2">
                <label class="label">Ceritakan kondisinya <span class="hint"><span id="charCount">0</span>/2000</span></label>
                <textarea class="textarea @error('description') is-invalid @enderror" name="description" id="desc" maxlength="2000" required placeholder="Contoh: Sejak sebulan terakhir sinyal hilang setiap sore di sekitar pasar desa…">{{ old('description', $report->description) }}</textarea>
            </div>
            <label class="dropzone" data-preview="photoPreview">
                <input type="file" name="photo" accept="image/*">
                <div class="dz-icon"><i data-lucide="image-plus"></i></div>
                <b style="color:var(--text)">Seret foto ke sini atau klik untuk memilih</b>
                <div style="font-size:12px">Tangkapan layar speed test / kondisi lapangan · maks 2 MB</div>
                <div class="dz-file"></div>
            </label>
            <img id="photoPreview" class="photo mt-2" style="display:none" alt="Pratinjau">
            @if ($report->photo_path)<img src="{{ asset('storage/'.$report->photo_path) }}" class="photo mt-2" alt="Foto saat ini">@endif
        </div>
    </div>

    {{-- Panel skor langsung --}}
    <div>
        <div class="card glow-border" style="position:sticky;top:90px">
            <h3 class="card-title"><i data-lucide="calculator"></i> Pratinjau skor keparahan</h3>
            <p class="card-sub">Dihitung langsung oleh server saat Anda mengisi formulir.</p>
            <div style="display:grid;place-items:center;margin:20px 0">
                <div class="score-ring lg" id="sevRing" style="--v:0;--c:var(--mint)"><b id="sevScore">0</b></div>
                <span class="badge b-low mt-2" id="sevLevel">Rendah</span>
            </div>
            <div id="sevBreak"></div>
            <div class="callout mt-2"><i data-lucide="lightbulb"></i><span>Laporan dengan skor <b>≥ 75 (Kritis)</b> langsung dinotifikasikan ke seluruh analis untuk verifikasi segera.</span></div>
            <button class="btn btn-primary btn-lg btn-block mt-2" type="submit"><i data-lucide="send"></i> {{ $report->exists ? 'Simpan perubahan' : 'Kirim laporan' }}</button>
            <a href="{{ $report->exists ? route('reports.show', $report) : route('reports.index') }}" class="btn btn-ghost btn-block mt-1">Batal</a>
        </div>
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const regions = @json($regionsJson);
    const selectedRegion = @json((int) $selectedRegion);
    const cSel = document.getElementById('countrySelect'), rSel = document.getElementById('regionSelect');
    const lat = document.getElementById('lat'), lng = document.getElementById('lng');

    const map = SA.initMap('pickMap', [5, 112], 4);
    let marker = null;
    const setPoint = (la, ln, zoom) => {
        lat.value = (+la).toFixed(6); lng.value = (+ln).toFixed(6);
        const icon = L.divIcon({ className: '', html: '<div class="pulse-marker" style="--c:#60e1bd"></div>', iconSize: [14, 14] });
        marker ? marker.setLatLng([la, ln]) : (marker = L.marker([la, ln], { icon, draggable: true }).addTo(map).on('dragend', e => setPoint(e.target.getLatLng().lat, e.target.getLatLng().lng)));
        if (zoom) map.flyTo([la, ln], zoom, { duration: 1.2 });
    };
    map.on('click', e => setPoint(e.latlng.lat, e.latlng.lng));
    if (lat.value && lng.value) setPoint(lat.value, lng.value, 8);

    const fillRegions = (cid, keep) => {
        rSel.innerHTML = '<option value="">— Pilih wilayah —</option>' + (regions[cid] || []).map(r => `<option value="${r.id}" data-lat="${r.lat}" data-lng="${r.lng}" ${keep === r.id ? 'selected' : ''}>${r.name} · ${r.area}</option>`).join('');
    };
    cSel.addEventListener('change', () => { fillRegions(cSel.value); });
    rSel.addEventListener('change', () => { const o = rSel.selectedOptions[0]; if (o?.dataset.lat) setPoint(o.dataset.lat, o.dataset.lng, 8); });
    if (cSel.value) fillRegions(cSel.value, selectedRegion);

    document.getElementById('geoBtn').onclick = () => {
        if (!navigator.geolocation) return SA.toast('Browser tidak mendukung geolokasi', 'warning');
        navigator.geolocation.getCurrentPosition(p => { setPoint(p.coords.latitude, p.coords.longitude, 12); SA.toast('Lokasi Anda berhasil ditandai', 'success'); }, () => SA.toast('Izin lokasi ditolak', 'danger'));
    };

    // Penghitung karakter
    const desc = document.getElementById('desc'), cc = document.getElementById('charCount');
    const count = () => cc.textContent = desc.value.length; desc.addEventListener('input', count); count();

    // Pratinjau skor (AJAX ke server)
    const form = desc.closest('form'), ring = document.getElementById('sevRing');
    const labels = @json(config('signal.severity_levels'));
    let timer;
    const calc = async () => {
        const fd = new FormData(form);
        const body = { download_mbps: fd.get('download_mbps') || null, latency_ms: fd.get('latency_ms') || null, issue_type: fd.get('issue_type'), technology: fd.get('technology') };
        try {
            const r = await SA.request(@json(route('api.severity')), { method: 'POST', body: JSON.stringify(body) });
            ring.style.setProperty('--v', r.severity_score);
            ring.style.setProperty('--c', SA.levelColor[r.severity_level]);
            document.getElementById('sevScore').textContent = r.severity_score;
            const lv = document.getElementById('sevLevel'); lv.className = 'badge mt-2 b-' + r.severity_level; lv.textContent = labels[r.severity_level];
            const max = { 'Kecepatan unduh': 45, 'Latensi': 15, 'Jenis masalah': 25, 'Teknologi': 15 };
            document.getElementById('sevBreak').innerHTML = Object.entries(r.breakdown).map(([k, v]) =>
                `<div class="bar-row" style="grid-template-columns:110px 1fr 40px"><span class="muted">${k}</span><div class="progress thin"><span style="width:${v / max[k] * 100}%;--c:${SA.levelColor[r.severity_level]}"></span></div><b class="mono" style="text-align:right">${v}</b></div>`).join('');
        } catch (e) { /* abaikan */ }
    };
    form.querySelectorAll('[data-calc]').forEach(el => el.addEventListener(el.type === 'radio' ? 'change' : 'input', () => { clearTimeout(timer); timer = setTimeout(calc, 250); }));
    calc();
});
</script>
@endpush
