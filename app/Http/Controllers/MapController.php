<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\InfrastructureProject;
use App\Models\Report;
use App\Services\PriorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(): View
    {
        return view('map.index', ['countries' => Country::orderBy('name')->get()]);
    }

    /** GeoJSON-like payload untuk peta Leaflet. */
    public function data(Request $request, PriorityService $priority): JsonResponse
    {
        $reports = Report::with('region.country')
            ->whereNotNull('latitude')
            ->whereIn('status', $request->user()?->isStaff() ? ['pending', 'verified', 'resolved'] : ['verified', 'resolved'])
            ->when($request->query('country'), fn ($q, $v) => $q->whereHas('region', fn ($r) => $r->where('country_id', $v)))
            ->when($request->query('issue'), fn ($q, $v) => $q->where('issue_type', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()->take(800)->get()
            ->map(fn (Report $r) => [
                'id' => $r->id,
                'code' => $r->code,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'level' => $r->severity_level,
                'score' => $r->severity_score,
                'status' => $r->status,
                'status_label' => $r->statusLabel(),
                'issue' => $r->issueLabel(),
                'provider' => $r->provider,
                'tech' => $r->technologyLabel(),
                'download' => $r->download_mbps,
                'region' => $r->region->name.', '.$r->region->country->name,
                'date' => $r->created_at->translatedFormat('d M Y'),
                'url' => $request->user() ? route('reports.show', $r) : null,
            ]);

        $regions = $priority->rank($request->integer('country') ?: null)
            ->map(fn ($row) => [
                'name' => $row['region']->name,
                'country' => $row['region']->country->name,
                'lat' => $row['region']->latitude,
                'lng' => $row['region']->longitude,
                'gap' => $row['gap_score'],
                'level' => $row['level'],
                'reports' => $row['active_reports'],
                'handled' => $row['handled'],
                'area' => $row['region']->areaLabel(),
            ]);

        $projects = InfrastructureProject::with('region')
            ->whereIn('status', ['approved', 'in_progress', 'completed'])
            ->get()
            ->map(fn ($p) => [
                'code' => $p->code,
                'title' => $p->title,
                'lat' => $p->region->latitude,
                'lng' => $p->region->longitude,
                'status' => $p->statusLabel(),
                'progress' => $p->progress,
                'type' => $p->typeLabel(),
            ]);

        return response()->json(compact('reports', 'regions', 'projects'));
    }
}
