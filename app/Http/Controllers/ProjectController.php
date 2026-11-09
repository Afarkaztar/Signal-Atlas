<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\InfrastructureProject;
use App\Models\ProjectUpdate;
use App\Models\Region;
use App\Services\ActivityLogger;
use App\Services\CodeGenerator;
use App\Services\CsvService;
use App\Services\PriorityService;
use App\Services\ProjectWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function __construct(private ProjectWorkflow $workflow, private PriorityService $priority) {}

    public function index(Request $request): View
    {
        $query = InfrastructureProject::with(['region.country', 'proposer'])
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('title', 'like', "%$v%")->orWhere('code', 'like', "%$v%")))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('country'), fn ($q, $v) => $q->whereHas('region', fn ($r) => $r->where('country_id', $v)));

        $projects = (clone $query)->orderByDesc('priority_score')->get();

        $summary = [
            'budget' => $projects->whereNotIn('status', ['rejected', 'cancelled'])->sum('budget_usd'),
            'beneficiaries' => $projects->whereIn('status', ['in_progress', 'completed'])->sum('beneficiaries'),
            'completed' => $projects->where('status', 'completed')->count(),
            'overdue' => $projects->filter->isOverdue()->count(),
        ];

        return view('projects.index', [
            'projects' => $projects,
            'board' => collect(config('signal.project_statuses'))->map(fn ($label, $key) => $projects->where('status', $key)),
            'summary' => $summary,
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $project = new InfrastructureProject([
            'region_id' => $request->integer('region_id') ?: null,
            'type' => $request->query('type', 'bts'),
            'budget_usd' => $request->query('budget'),
            'beneficiaries' => $request->query('beneficiaries'),
            'target_date' => now()->addMonths(9),
        ]);

        if ($project->region_id && $region = Region::find($project->region_id)) {
            $project->title = config('signal.project_types.'.$project->type).' — '.$region->name;
        }

        return view('projects.create', [
            'project' => $project,
            'countries' => Country::with('regions')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $region = Region::findOrFail($data['region_id']);
        $analysis = $this->priority->forRegion($region);

        $project = InfrastructureProject::create($data + [
            'code' => CodeGenerator::project(),
            'status' => 'proposed',
            'priority_score' => $analysis['gap_score'] ?? 0,
            'proposed_by' => $request->user()->id,
        ]);

        ProjectUpdate::create([
            'infrastructure_project_id' => $project->id,
            'user_id' => $request->user()->id,
            'to_status' => 'proposed',
            'progress' => 0,
            'note' => 'Proyek diusulkan dengan skor prioritas '.$project->priority_score,
        ]);
        ActivityLogger::log('project.create', "Mengusulkan proyek {$project->code}", $project);

        return redirect()->route('projects.show', $project)->with('toast', ['success', "Proyek {$project->code} berhasil diusulkan."]);
    }

    public function show(InfrastructureProject $project): View
    {
        $project->load(['region.country', 'proposer', 'approver', 'updates.user', 'reports.user']);
        $analysis = $this->priority->forRegion($project->region);

        return view('projects.show', compact('project', 'analysis'));
    }

    public function edit(InfrastructureProject $project): View
    {
        abort_if(in_array($project->status, ['completed', 'cancelled', 'rejected'], true), 403, 'Proyek yang sudah final tidak dapat diubah.');

        return view('projects.edit', [
            'project' => $project,
            'countries' => Country::with('regions')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, InfrastructureProject $project): RedirectResponse
    {
        abort_if(in_array($project->status, ['completed', 'cancelled', 'rejected'], true), 403);
        $project->update($this->validated($request));
        ActivityLogger::log('project.update', "Memperbarui detail proyek {$project->code}", $project);

        return redirect()->route('projects.show', $project)->with('toast', ['success', 'Detail proyek diperbarui.']);
    }

    public function transition(Request $request, InfrastructureProject $project): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('signal.project_statuses')))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->workflow->transition($project, $data['status'], $request->user(), $data['note'] ?? null);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }
            throw $e;
        }

        $message = "Status {$project->code} → {$project->statusLabel()}";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'status' => $project->status, 'progress' => $project->progress])
            : back()->with('toast', ['success', $message]);
    }

    public function progress(Request $request, InfrastructureProject $project): RedirectResponse
    {
        $data = $request->validate([
            'progress' => ['required', 'integer', 'between:0,100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $this->workflow->updateProgress($project, $data['progress'], $request->user(), $data['note'] ?? null);

        return back()->with('toast', ['success', $project->status === 'completed'
            ? "🎉 Proyek {$project->code} selesai! Laporan terkait otomatis ditandai terselesaikan."
            : "Progres diperbarui menjadi {$data['progress']}%."]);
    }

    public function destroy(InfrastructureProject $project): RedirectResponse
    {
        ActivityLogger::log('project.delete', "Menghapus proyek {$project->code}", $project);
        $project->delete();

        return redirect()->route('projects.index')->with('toast', ['success', 'Proyek dihapus.']);
    }

    public function export(): StreamedResponse
    {
        $rows = InfrastructureProject::with(['region.country', 'proposer'])->orderBy('code')->get();
        ActivityLogger::log('project.export', "Mengekspor {$rows->count()} proyek ke CSV");

        return CsvService::download('proyek-infrastruktur-'.now()->format('Ymd').'.csv',
            ['Kode', 'Judul', 'Jenis', 'Negara', 'Wilayah', 'Status', 'Progres (%)', 'Anggaran (USD)', 'Penerima Manfaat', 'Skor Prioritas', 'Mulai', 'Target', 'Pengusul'],
            $rows->map(fn ($p) => [
                $p->code, $p->title, $p->typeLabel(), $p->region->country->name, $p->region->name, $p->statusLabel(),
                $p->progress, $p->budget_usd, $p->beneficiaries, $p->priority_score,
                $p->start_date?->format('Y-m-d'), $p->target_date?->format('Y-m-d'), $p->proposer?->name,
            ]));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(array_keys(config('signal.project_types')))],
            'description' => ['nullable', 'string', 'max:3000'],
            'budget_usd' => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'beneficiaries' => ['required', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [], ['budget_usd' => 'anggaran', 'beneficiaries' => 'penerima manfaat', 'target_date' => 'target selesai']);
    }
}
