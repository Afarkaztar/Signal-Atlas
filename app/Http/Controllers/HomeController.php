<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\InfrastructureProject;
use App\Models\Report;
use App\Models\User;
use App\Services\ConnectivityIndexService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(ConnectivityIndexService $dci): View
    {
        $stats = [
            'reports' => Report::count(),
            'verified' => Report::whereIn('status', ['verified', 'resolved'])->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'projects' => InfrastructureProject::whereIn('status', ['approved', 'in_progress', 'completed'])->count(),
            'contributors' => User::where('role', 'contributor')->count(),
            'countries' => Country::count(),
        ];

        $ranking = $dci->ranking()->take(5);
        $latest = Report::with('region.country')
            ->whereIn('status', ['verified', 'resolved'])
            ->latest()->take(6)->get();

        return view('home', compact('stats', 'ranking', 'latest'));
    }
}
