<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Region;
use App\Models\Report;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\ActivityLogger;
use App\Services\CodeGenerator;
use App\Services\CsvService;
use App\Services\SeverityCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private SeverityCalculator $severity) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $scope = $user->isStaff() ? 'all' : $request->query('scope', 'mine');
        $filters = $request->only(['q', 'status', 'issue_type', 'severity', 'technology', 'country', 'from', 'to']);
        $sort = in_array($request->query('sort'), ['created_at', 'severity_score', 'download_mbps', 'code'], true) ? $request->query('sort') : 'created_at';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $reports = $this->scoped($user, $scope)
            ->with(['region.country', 'user'])
            ->filter($filters)
            ->orderBy($sort, $dir)
            ->paginate(12)
            ->withQueryString();

        $summary = $this->scoped($user, $scope)->filter($filters)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('reports.index', [
            'reports' => $reports,
            'summary' => $summary,
            'filters' => $filters,
            'scope' => $scope,
            'sort' => $sort,
            'dir' => $dir,
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        // Prefill dari hasil speed test (query string)
        $prefill = $request->only(['download_mbps', 'upload_mbps', 'latency_ms', 'source']);

        return view('reports.create', [
            'report' => new Report(array_merge(['technology' => '4g', 'issue_type' => 'slow'], $prefill)),
            'countries' => Country::with('regions')->orderBy('name')->get(),
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data += $this->severity->calculate($data);
        unset($data['breakdown'], $data['photo']);

        $data['code'] = CodeGenerator::report();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';
        $data['source'] = $request->input('source') === 'speedtest' ? 'speedtest' : 'manual';

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('reports', 'public');
        }

        $report = Report::create($data);
        ActivityLogger::log('report.create', "Membuat laporan {$report->code}", $report);

        // Laporan kritis langsung memberi tahu seluruh staf
        if ($report->severity_level === 'critical') {
            Notification::send(
                User::whereIn('role', ['admin', 'analyst'])->where('id', '!=', $report->user_id)->get(),
                new SystemNotification('Laporan kritis baru', "{$report->code} — {$report->region->name} membutuhkan verifikasi segera.", route('verification.index'), 'siren', 'danger')
            );
        }

        return redirect()->route('reports.show', $report)
            ->with('toast', ['success', "Laporan {$report->code} terkirim! Skor keparahan: {$report->severity_score}/100."]);
    }

    public function show(Request $request, Report $report): View
    {
        $this->authorizeView($request->user(), $report);
        $report->load(['region.country', 'user', 'verifier', 'project']);

        $nearby = Report::with('region')
            ->where('region_id', $report->region_id)
            ->where('id', '!=', $report->id)
            ->whereIn('status', ['verified', 'resolved', 'pending'])
            ->latest()->take(5)->get();

        $breakdown = $this->severity->calculate($report->only(['download_mbps', 'latency_ms', 'issue_type', 'technology']))['breakdown'];

        return view('reports.show', compact('report', 'nearby', 'breakdown'));
    }

    public function edit(Request $request, Report $report): View
    {
        $this->authorizeEdit($request->user(), $report);

        return view('reports.edit', [
            'report' => $report,
            'countries' => Country::with('regions')->orderBy('name')->get(),
            'prefill' => [],
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeEdit($request->user(), $report);

        $data = $this->validated($request);
        $data += $this->severity->calculate($data);
        unset($data['breakdown'], $data['photo']);

        if ($request->hasFile('photo')) {
            if ($report->photo_path) {
                Storage::disk('public')->delete($report->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('reports', 'public');
        }

        $report->update($data);
        ActivityLogger::log('report.update', "Memperbarui laporan {$report->code}", $report);

        return redirect()->route('reports.show', $report)->with('toast', ['success', 'Laporan berhasil diperbarui.']);
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($report->user_id === $user->id && $report->isEditable()), 403, 'Laporan ini tidak dapat dihapus.');

        if ($report->photo_path) {
            Storage::disk('public')->delete($report->photo_path);
        }
        ActivityLogger::log('report.delete', "Menghapus laporan {$report->code}", $report);
        $report->delete();

        return redirect()->route('reports.index')->with('toast', ['success', "Laporan {$report->code} dihapus."]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $scope = $user->isStaff() ? 'all' : $request->query('scope', 'mine');
        $reports = $this->scoped($user, $scope)->with(['region.country', 'user'])
            ->filter($request->only(['q', 'status', 'issue_type', 'severity', 'technology', 'country', 'from', 'to']))
            ->orderByDesc('created_at')->get();

        ActivityLogger::log('report.export', "Mengekspor {$reports->count()} laporan ke CSV");

        return CsvService::download('laporan-signalatlas-'.now()->format('Ymd-His').'.csv',
            ['Kode', 'Tanggal', 'Negara', 'Wilayah', 'Provider', 'Teknologi', 'Masalah', 'Download (Mbps)', 'Upload (Mbps)', 'Latensi (ms)', 'Skor', 'Level', 'Status', 'Sumber', 'Pelapor', 'Latitude', 'Longitude', 'Deskripsi'],
            $reports->map(fn (Report $r) => [
                $r->code, $r->created_at->format('Y-m-d H:i'), $r->region->country->name, $r->region->name, $r->provider,
                $r->technologyLabel(), $r->issueLabel(), $r->download_mbps, $r->upload_mbps, $r->latency_ms,
                $r->severity_score, $r->severityLabel(), $r->statusLabel(), $r->source, $r->user?->name,
                $r->latitude, $r->longitude, $r->description,
            ])
        );
    }

    /** API: daftar wilayah untuk dropdown dinamis. */
    public function regions(Request $request): JsonResponse
    {
        return response()->json(
            Region::where('country_id', $request->integer('country_id'))->orderBy('name')
                ->get(['id', 'name', 'area_type', 'latitude', 'longitude'])
        );
    }

    /** API: pratinjau skor keparahan dari server. */
    public function severityPreview(Request $request): JsonResponse
    {
        return response()->json($this->severity->calculate($request->only(['download_mbps', 'latency_ms', 'issue_type', 'technology'])));
    }

    // ---------------------------------------------------------------------

    private function scoped(User $user, string $scope)
    {
        if ($user->isStaff()) {
            return Report::query();
        }

        return $scope === 'all'
            ? Report::where(fn ($q) => $q->whereIn('status', ['verified', 'resolved'])->orWhere('user_id', $user->id))
            : Report::where('user_id', $user->id);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'provider' => ['required', 'string', 'max:60'],
            'technology' => ['required', Rule::in(array_keys(config('signal.technologies')))],
            'issue_type' => ['required', Rule::in(array_keys(config('signal.issue_types')))],
            'download_mbps' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'upload_mbps' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'latency_ms' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'description' => ['required', 'string', 'min:15', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [], [
            'region_id' => 'wilayah',
            'issue_type' => 'jenis masalah',
            'technology' => 'teknologi',
            'description' => 'deskripsi',
            'download_mbps' => 'kecepatan unduh',
            'upload_mbps' => 'kecepatan unggah',
            'latency_ms' => 'latensi',
        ]);
    }

    private function authorizeView(User $user, Report $report): void
    {
        abort_unless(
            $user->isStaff() || $report->user_id === $user->id || in_array($report->status, ['verified', 'resolved'], true),
            403
        );
    }

    private function authorizeEdit(User $user, Report $report): void
    {
        abort_unless($report->isEditable() && ($user->isStaff() || $report->user_id === $user->id), 403,
            'Laporan hanya dapat diubah selama masih menunggu verifikasi.');
    }
}
