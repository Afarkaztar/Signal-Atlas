@include('partials.errors')

<div class="card">
    <div class="form-grid">
        <div class="field">
            <label class="label">Wilayah sasaran</label>
            <select class="select" name="region_id" id="pRegion" required>
                <option value="">— Pilih wilayah —</option>
                @foreach ($countries as $c)
                    <optgroup label="{{ $c->flag() }} {{ $c->name }}">
                        @foreach ($c->regions as $r)
                            <option value="{{ $r->id }}" data-name="{{ $r->name }}" @selected(old('region_id', $project->region_id) == $r->id)>{{ $r->name }} ({{ $r->areaLabel() }})</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label class="label">Jenis infrastruktur</label>
            <select class="select" name="type" id="pType" required>
                @foreach (config('signal.project_types') as $k => $v)
                    <option value="{{ $k }}" data-cost="{{ config("signal.unit_costs.$k") }}" @selected(old('type', $project->type) === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="field full">
            <label class="label">Judul proyek</label>
            <input class="input" name="title" id="pTitle" value="{{ old('title', $project->title) }}" required placeholder="Contoh: Menara BTS 4G/5G — Papua">
        </div>
        <div class="field">
            <label class="label">Anggaran <span class="hint">USD</span></label>
            <input class="input" type="number" step="100" min="1000" name="budget_usd" id="pBudget" value="{{ old('budget_usd', $project->budget_usd) }}" required>
        </div>
        <div class="field">
            <label class="label">Estimasi penerima manfaat <span class="hint">jiwa</span></label>
            <input class="input" type="number" min="1" name="beneficiaries" value="{{ old('beneficiaries', $project->beneficiaries ?? 5000) }}" required>
        </div>
        <div class="field">
            <label class="label">Tanggal mulai <span class="hint">opsional</span></label>
            <input class="input" type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
        </div>
        <div class="field">
            <label class="label">Target selesai</label>
            <input class="input" type="date" name="target_date" value="{{ old('target_date', $project->target_date?->format('Y-m-d')) }}" required>
        </div>
        <div class="field full">
            <label class="label">Deskripsi & justifikasi kebutuhan</label>
            <textarea class="textarea" name="description" placeholder="Jelaskan kondisi konektivitas wilayah dan cakupan pekerjaan…">{{ old('description', $project->description) }}</textarea>
        </div>
    </div>
    <div class="row mt-2" style="justify-content:flex-end">
        <a href="{{ $project->exists ? route('projects.show', $project) : route('projects.index') }}" class="btn">Batal</a>
        <button class="btn btn-primary" type="submit"><i data-lucide="check"></i> {{ $project->exists ? 'Simpan perubahan' : 'Usulkan proyek' }}</button>
    </div>
</div>

@push('scripts')
<script>
    const r = document.getElementById('pRegion'), t = document.getElementById('pType'), title = document.getElementById('pTitle'), budget = document.getElementById('pBudget');
    const auto = () => {
        const rn = r.selectedOptions[0]?.dataset.name, tn = t.selectedOptions[0]?.textContent;
        if (rn && tn && !title.dataset.touched) title.value = `${tn} — ${rn}`;
        if (!budget.value) budget.value = t.selectedOptions[0]?.dataset.cost || '';
    };
    title.addEventListener('input', () => title.dataset.touched = 1);
    r.addEventListener('change', auto); t.addEventListener('change', () => { budget.value = t.selectedOptions[0]?.dataset.cost; auto(); });
</script>
@endpush
