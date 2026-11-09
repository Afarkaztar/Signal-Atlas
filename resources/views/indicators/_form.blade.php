@include('partials.errors')
<div class="card">
    <div class="form-grid">
        <div class="field"><label class="label">Negara</label><select class="select" name="country_id" required>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(old('country_id', $indicator->country_id) == $c->id)>{{ $c->flag() }} {{ $c->name }} ({{ $c->iso3 }})</option>@endforeach</select></div>
        <div class="field"><label class="label">Tahun</label><input class="input" type="number" min="2000" max="{{ now()->year }}" name="year" value="{{ old('year', $indicator->year) }}" required></div>
        <div class="field"><label class="label">Pengguna internet <span class="hint">% populasi · wajib</span></label><input class="input" type="number" step="0.01" min="0" max="100" name="internet_users_pct" value="{{ old('internet_users_pct', $indicator->internet_users_pct) }}" required></div>
        <div class="field"><label class="label">Mobile broadband <span class="hint">/ 100 penduduk</span></label><input class="input" type="number" step="0.01" min="0" name="mobile_broadband_per100" value="{{ old('mobile_broadband_per100', $indicator->mobile_broadband_per100) }}"></div>
        <div class="field"><label class="label">Fixed broadband <span class="hint">/ 100 penduduk</span></label><input class="input" type="number" step="0.01" min="0" name="fixed_broadband_per100" value="{{ old('fixed_broadband_per100', $indicator->fixed_broadband_per100) }}"></div>
        <div class="field"><label class="label">Kecepatan mobile rata-rata <span class="hint">Mbps</span></label><input class="input" type="number" step="0.01" min="0" name="avg_mobile_speed_mbps" value="{{ old('avg_mobile_speed_mbps', $indicator->avg_mobile_speed_mbps) }}"></div>
        <div class="field"><label class="label">Kecepatan fixed rata-rata <span class="hint">Mbps</span></label><input class="input" type="number" step="0.01" min="0" name="avg_fixed_speed_mbps" value="{{ old('avg_fixed_speed_mbps', $indicator->avg_fixed_speed_mbps) }}"></div>
        <div class="field"><label class="label">Harga data seluler <span class="hint">USD / GB</span></label><input class="input" type="number" step="0.01" min="0" name="data_price_usd_gb" value="{{ old('data_price_usd_gb', $indicator->data_price_usd_gb) }}"></div>
        <div class="field full"><label class="label">Sumber data</label><input class="input" name="data_source" value="{{ old('data_source', $indicator->data_source) }}" required></div>
    </div>
    <div class="row mt-2" style="justify-content:flex-end">
        <a href="{{ route('indicators.index') }}" class="btn">Batal</a>
        <button class="btn btn-primary" type="submit"><i data-lucide="check"></i> Simpan</button>
    </div>
</div>
