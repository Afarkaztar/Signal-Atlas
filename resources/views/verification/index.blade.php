@extends('layouts.app')
@section('title', 'Antrean Verifikasi')

@section('content')
@php $user = auth()->user(); @endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Alur kerja analis</span>
        <h1 class="page-title">Antrean Verifikasi</h1>
        <p class="page-sub">Tinjau laporan warga. Laporan yang disetujui memberi +{{ config('signal.points.verified') }} poin kepada pelapor dan masuk perhitungan Connectivity Gap Score.</p>
    </div>
    <div class="row">
        <span class="chip"><i data-lucide="check-circle-2" style="color:var(--mint)"></i> Hari ini disetujui: <b>{{ $todayStats['verified'] }}</b></span>
        <span class="chip"><i data-lucide="x-octagon" style="color:var(--red)"></i> Hari ini ditolak: <b>{{ $todayStats['rejected'] }}</b></span>
        <span class="chip"><i data-lucide="award" style="color:var(--violet)"></i> Total tinjauan Anda: <b>{{ $todayStats['mine'] }}</b></span>
    </div>
</div>

<div class="grid g-side">
    <div class="card">
        <form class="toolbar" method="GET">
            <select class="select" name="country" onchange="this.form.submit()"><option value="">Semua negara</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country') == $c->id)>{{ $c->flag() }} {{ $c->name }}</option>@endforeach</select>
            <select class="select" name="severity" onchange="this.form.submit()"><option value="">Semua level</option>@foreach (config('signal.severity_levels') as $k => $v)<option value="{{ $k }}" @selected(request('severity') === $k)>{{ $v }}</option>@endforeach</select>
            <div style="margin-left:auto" class="row">
                <button type="button" class="btn btn-sm btn-success" id="bulkVerify" disabled><i data-lucide="check-check"></i> Verifikasi terpilih</button>
                <button type="button" class="btn btn-sm btn-danger" id="bulkReject" disabled><i data-lucide="x"></i> Tolak terpilih</button>
            </div>
        </form>

        <form id="bulkForm" method="POST" action="{{ route('verification.bulk') }}">@csrf
            <input type="hidden" name="action" id="bulkAction">
            <input type="hidden" name="note" id="bulkNote">

            @if ($reports->isEmpty())
                <div class="empty"><div class="empty-icon"><i data-lucide="sparkles"></i></div><h3>Antrean bersih!</h3><p>Semua laporan sudah ditinjau.</p></div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr>
                        <th style="width:32px"><input type="checkbox" id="checkAll" style="accent-color:var(--mint)"></th>
                        <th>Skor</th><th>Laporan</th><th>Teknis</th><th>Pelapor</th><th></th>
                    </tr></thead>
                    <tbody class="stagger">
                    @foreach ($reports as $r)
                        @php $own = $r->user_id === $user->id; @endphp
                        <tr data-row="{{ $r->id }}" style="transition:all .4s var(--ease)">
                            <td><input type="checkbox" name="ids[]" value="{{ $r->id }}" class="row-check" @disabled($own) style="accent-color:var(--mint)"></td>
                            <td><span class="badge b-{{ $r->severity_level }}">{{ $r->severity_score }}</span></td>
                            <td>
                                <a href="{{ route('reports.show', $r) }}" class="cell-main" style="color:var(--mint)">{{ $r->code }}</a>
                                · <b>{{ $r->region->name }}</b> <span class="muted">({{ $r->region->country->name }})</span>
                                <div class="cell-sub" style="max-width:420px">{{ Str::limit($r->description, 110) }}</div>
                            </td>
                            <td>
                                <div>{{ $r->issueLabel() }}</div>
                                <div class="cell-sub">{{ $r->provider }} · {{ $r->technologyLabel() }} @if ($r->download_mbps !== null)· {{ $r->download_mbps }} Mbps @endif</div>
                            </td>
                            <td><div>{{ $r->user?->name }}</div><div class="cell-sub">{{ $r->created_at->diffForHumans() }}</div></td>
                            <td>
                                @if ($own)
                                    <span class="badge b-neutral no-dot" title="Anda tidak dapat meninjau laporan milik sendiri">Laporan sendiri</span>
                                @else
                                    <div class="actions">
                                        <button type="button" class="btn btn-xs btn-success" data-quick-verify="{{ route('verification.verify', $r) }}"><i data-lucide="check"></i> Setujui</button>
                                        <button type="button" class="btn btn-xs btn-danger" data-quick-reject="{{ route('verification.reject', $r) }}" data-code="{{ $r->code }}"><i data-lucide="x"></i></button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                {{ $reports->links() }}
            @endif
        </form>
    </div>

    <div class="card" data-reveal>
        <h3 class="card-title mb-2"><i data-lucide="history"></i> Riwayat tinjauan terakhir</h3>
        <div class="timeline">
            @foreach ($history as $h)
                <div class="tl-item" style="--c:{{ $h->status === 'rejected' ? 'var(--red)' : 'var(--mint)' }}">
                    <div class="tl-title"><a href="{{ route('reports.show', $h) }}">{{ $h->code }}</a> · {{ $h->statusLabel() }}</div>
                    <div class="tl-meta">{{ $h->verifier?->name }} · {{ $h->verified_at?->diffForHumans() }}</div>
                    @if ($h->review_note)<div class="tl-note">“{{ $h->review_note }}”</div>@endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checks = () => SA.$$('.row-check:checked');
    const sync = () => { const n = checks().length; SA.$('#bulkVerify').disabled = SA.$('#bulkReject').disabled = !n; };
    SA.$('#checkAll')?.addEventListener('change', (e) => { SA.$$('.row-check:not(:disabled)').forEach(c => c.checked = e.target.checked); sync(); });
    SA.$$('.row-check').forEach(c => c.addEventListener('change', sync));

    SA.$('#bulkVerify')?.addEventListener('click', async () => {
        if (!await SA.confirm({ title: `Verifikasi ${checks().length} laporan?`, message: 'Pelapor akan menerima poin dan notifikasi.', confirmText: 'Verifikasi', tone: 'info' })) return;
        SA.$('#bulkAction').value = 'verify'; SA.$('#bulkForm').dataset.confirmed = 1; SA.$('#bulkForm').submit();
    });
    SA.$('#bulkReject')?.addEventListener('click', async () => {
        const note = await SA.confirm({ title: `Tolak ${checks().length} laporan`, message: 'Masukkan alasan penolakan massal:', confirmText: 'Tolak', input: 'Contoh: Data duplikat / tidak valid' });
        if (!note || note === true) return SA.toast('Alasan wajib diisi', 'warning');
        SA.$('#bulkAction').value = 'reject'; SA.$('#bulkNote').value = note; SA.$('#bulkForm').dataset.confirmed = 1; SA.$('#bulkForm').submit();
    });

    const fadeRow = (btn, res) => {
        const tr = btn.closest('tr');
        tr.style.transform = 'translateX(40px)'; tr.style.opacity = 0;
        setTimeout(() => tr.remove(), 380);
        const badge = document.querySelector('.nav-link[href*="verifikasi"] .nav-badge');
        if (badge) res.pending ? (badge.textContent = res.pending) : badge.remove();
    };
    SA.$$('[data-quick-verify]').forEach(btn => btn.onclick = async () => {
        btn.disabled = true;
        try { const r = await SA.request(btn.dataset.quickVerify, { method: 'POST', body: '{}' }); SA.toast(r.message, 'success'); fadeRow(btn, r); }
        catch (e) { btn.disabled = false; SA.toast(e.message, 'danger'); }
    });
    SA.$$('[data-quick-reject]').forEach(btn => btn.onclick = async () => {
        const note = await SA.confirm({ title: `Tolak ${btn.dataset.code}`, message: 'Alasan penolakan akan dikirim ke pelapor:', confirmText: 'Tolak laporan', input: 'Tulis alasan singkat…' });
        if (!note || note === true) return;
        try { const r = await SA.request(btn.dataset.quickReject, { method: 'POST', body: JSON.stringify({ note }) }); SA.toast(r.message, 'warning'); fadeRow(btn, r); }
        catch (e) { SA.toast(e.message, 'danger'); }
    });
});
</script>
@endpush
