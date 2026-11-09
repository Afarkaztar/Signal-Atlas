<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InfrastructureProject;
use App\Models\Report;
use App\Models\User;
use App\Services\PriorityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, PriorityService $priority): View
    {
        $user = $request->user();
        $scope = $user->isStaff() ? Report::query() : $user->reports();

        $kpi = [
            'total' => (clone $scope)->count(),
            'pending' => (clone $scope)->where('status', 'pending')->count(),
            'verified' => (clone $scope)->where('status', 'verified')->count(),
            'resolved' => (clone $scope)->where('status', 'resolved')->count(),
            'rejected' => (clone $scope)->where('status', 'rejected')->count(),
            'critical' => (clone $scope)->where('severity_level', 'critical')->whereIn('status', ['pending', 'verified'])->count(),
        ];

        // Tren 6 bulan terakhir
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $trend = [
            'labels' => $months->map(fn (Carbon $m) => $m->translatedFormat('M Y'))->all(),
            'submitted' => $months->map(fn (Carbon $m) => (clone $scope)->whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count())->all(),
            'resolved' => $months->map(fn (Carbon $m) => (clone $scope)->whereBetween('resolved_at', [$m, $m->copy()->endOfMonth()])->count())->all(),
        ];

        $issueBreakdown = (clone $scope)->selectRaw('issue_type, count(*) as total')->groupBy('issue_type')->pluck('total', 'issue_type');

        $projectStatus = InfrastructureProject::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $activeProjects = InfrastructureProject::with('region.country')
            ->whereIn('status', ['in_progress', 'approved', 'on_hold'])
            ->orderByDesc('priority_score')->take(4)->get();

        $recentReports = (clone $scope)->with('region.country')->latest()->take(6)->get();
        $leaderboard = User::where('role', 'contributor')->orderByDesc('points')->take(5)->get();
        $activities = ActivityLog::with('user')->when(! $user->isStaff(), fn ($q) => $q->where('user_id', $user->id))->latest()->take(7)->get();
        $topPriorities = $user->isStaff() ? $priority->rank()->where('gap_score', '>', 0)->take(5) : collect();

        $rank = User::where('role', 'contributor')->where('points', '>', $user->points)->count() + 1;

        return view('dashboard', compact(
            'kpi', 'trend', 'issueBreakdown', 'projectStatus', 'activeProjects', 'recentReports',
            'leaderboard', 'activities', 'topPriorities', 'rank'
        ));
    }
}
