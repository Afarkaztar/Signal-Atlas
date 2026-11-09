<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Report;
use App\Services\ReportWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct(private ReportWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $reports = Report::with(['region.country', 'user'])
            ->where('status', 'pending')
            ->when($request->query('country'), fn ($q, $v) => $q->whereHas('region', fn ($r) => $r->where('country_id', $v)))
            ->when($request->query('severity'), fn ($q, $v) => $q->where('severity_level', $v))
            ->orderByDesc('severity_score')
            ->orderBy('created_at')
            ->paginate(10)->withQueryString();

        $history = Report::with(['region', 'verifier'])
            ->whereNotNull('verified_at')
            ->latest('verified_at')->take(8)->get();

        $todayStats = [
            'verified' => Report::where('status', 'verified')->whereDate('verified_at', today())->count(),
            'rejected' => Report::where('status', 'rejected')->whereDate('verified_at', today())->count(),
            'mine' => Report::where('verified_by', $request->user()->id)->count(),
        ];

        return view('verification.index', [
            'reports' => $reports,
            'history' => $history,
            'todayStats' => $todayStats,
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function verify(Request $request, Report $report): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $this->workflow->verify($report, $request->user(), $data['note'] ?? null);

        return $this->respond($request, "Laporan {$report->code} diverifikasi.");
    }

    public function reject(Request $request, Report $report): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:500']], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);
        $this->workflow->reject($report, $request->user(), $data['note']);

        return $this->respond($request, "Laporan {$report->code} ditolak.", 'warning');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:reports,id'],
            'action' => ['required', 'in:verify,reject'],
            'note' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ], ['ids.required' => 'Pilih minimal satu laporan.', 'note.required_if' => 'Alasan wajib diisi untuk penolakan massal.']);

        $ok = 0;
        $skipped = [];
        foreach (Report::whereIn('id', $data['ids'])->get() as $report) {
            try {
                $data['action'] === 'verify'
                    ? $this->workflow->verify($report, $request->user(), $data['note'] ?? null)
                    : $this->workflow->reject($report, $request->user(), $data['note']);
                $ok++;
            } catch (ValidationException $e) {
                $skipped[] = $report->code;
            }
        }

        $message = "{$ok} laporan berhasil diproses.".($skipped ? ' Dilewati: '.implode(', ', $skipped).' (milik sendiri / sudah ditinjau).' : '');

        return back()->with('toast', [$skipped ? 'warning' : 'success', $message]);
    }

    private function respond(Request $request, string $message, string $tone = 'success'): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'pending' => Report::where('status', 'pending')->count()]);
        }

        return back()->with('toast', [$tone, $message]);
    }
}
