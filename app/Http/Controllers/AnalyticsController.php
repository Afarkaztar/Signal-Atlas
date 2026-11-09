<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Indicator;
use App\Models\Report;
use App\Services\ConnectivityIndexService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request, ConnectivityIndexService $dci): View
    {
        $years = Indicator::distinct()->orderByDesc('year')->pluck('year');
        $year = $request->integer('year') ?: (int) $years->first();
        $ranking = $dci->ranking($year);

        $countries = Country::orderBy('name')->get();
        $indicators = Indicator::with('country')->orderBy('year')->get()
            ->groupBy(fn ($i) => $i->country->iso3)
            ->map(fn ($rows) => $rows->map(fn ($i) => [
                'year' => $i->year,
                'users' => $i->internet_users_pct,
                'mobile' => $i->mobile_broadband_per100,
                'fixed' => $i->fixed_broadband_per100,
                'mspeed' => $i->avg_mobile_speed_mbps,
                'fspeed' => $i->avg_fixed_speed_mbps,
                'price' => $i->data_price_usd_gb,
                'dci' => $dci->score($i),
            ])->values());

        // Analitik laporan publik (crowdsourced)
        $publicReports = Report::whereIn('status', ['verified', 'resolved']);
        $reportStats = [
            'issues' => (clone $publicReports)->selectRaw('issue_type as k, count(*) as v')->groupBy('issue_type')->pluck('v', 'k'),
            'tech' => (clone $publicReports)->selectRaw('technology as k, count(*) as v')->groupBy('technology')->pluck('v', 'k'),
            'severity' => (clone $publicReports)->selectRaw('severity_level as k, count(*) as v')->groupBy('severity_level')->pluck('v', 'k'),
            'providers' => (clone $publicReports)->whereNotNull('download_mbps')
                ->selectRaw('provider as k, avg(download_mbps) as v, count(*) as n')
                ->groupBy('provider')->having('n', '>=', 2)->orderByDesc('v')->take(10)->get(),
            'byCountry' => Report::join('regions', 'regions.id', '=', 'reports.region_id')
                ->join('countries', 'countries.id', '=', 'regions.country_id')
                ->whereIn('reports.status', ['verified', 'resolved'])
                ->selectRaw('countries.name as k, count(*) as v, avg(reports.severity_score) as s')
                ->groupBy('countries.name')->orderByDesc('v')->get(),
        ];

        return view('analytics.index', [
            'years' => $years,
            'year' => $year,
            'ranking' => $ranking,
            'countries' => $countries,
            'indicators' => $indicators,
            'reportStats' => $reportStats,
            'weights' => ConnectivityIndexService::WEIGHTS,
            'labels' => ConnectivityIndexService::LABELS,
        ]);
    }
}
