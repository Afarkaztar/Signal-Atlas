<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Country;
use App\Models\Indicator;
use App\Models\InfrastructureProject;
use App\Models\Region;
use App\Models\Report;
use App\Services\ActivityLogger;
use App\Services\CodeGenerator;
use App\Services\ConnectivityIndexService;
use App\Services\CsvService;
use App\Services\PriorityService;
use App\Services\SeverityCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pusat Data: impor CSV (dengan validasi per-baris & mode dry-run),
 * unduh template, ekspor, cetak ringkasan eksekutif, dan backup JSON.
 */
class DataCenterController extends Controller
{
    public const REPORT_HEADERS = ['iso3', 'region', 'provider', 'technology', 'issue_type', 'download_mbps', 'upload_mbps', 'latency_ms', 'latitude', 'longitude', 'description'];

    public function index(): View
    {
        return view('data.index', [
            'counts' => [
                'countries' => Country::count(),
                'regions' => Region::count(),
                'indicators' => Indicator::count(),
                'reports' => Report::count(),
                'projects' => InfrastructureProject::count(),
            ],
            'history' => ActivityLog::with('user')
                ->where(fn ($q) => $q->where('action', 'like', '%import%')->orWhere('action', 'like', '%export%')->orWhere('action', 'like', '%backup%'))
                ->latest()->take(10)->get(),
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function template(string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['indicators', 'reports'], true), 404);

        if ($type === 'indicators') {
            return CsvService::download('template-indikator.csv', IndicatorController::CSV_HEADERS, [
                ['IDN', 2025, 75.5, 120, 5.2, 30.1, 33.4, 0.45, 'World Bank WDI · ITU'],
                ['VNM', 2025, 86.2, 98, 23, 62, 155, 0.30, 'World Bank WDI · ITU'],
            ]);
        }

        return CsvService::download('template-laporan.csv', self::REPORT_HEADERS, [
            ['IDN', 'Papua', 'Telkomsel', 'none', 'blank_spot', '', '', '', -4.27, 138.08, 'Tidak ada sinyal sama sekali di distrik pegunungan'],
            ['PHL', 'Mindanao', 'Globe Telecom', '3g', 'slow', 1.8, 0.4, 210, 7.19, 125.45, 'Kecepatan sangat lambat pada jam sibuk'],
        ]);
    }

    public function import(Request $request, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['indicators', 'reports'], true), 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'mode' => ['nullable', 'in:skip,upsert'],
        ], ['file.mimes' => 'File harus berformat .csv']);

        $parsed = CsvService::parse($request->file('file'));
        $expected = $type === 'indicators' ? IndicatorController::CSV_HEADERS : self::REPORT_HEADERS;
        $missing = array_diff(array_slice($expected, 0, 5), $parsed['headers']);

        if ($missing) {
            return back()->withErrors(['file' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Gunakan template yang disediakan.']);
        }

        $dryRun = $request->boolean('dry_run');
        $result = $type === 'indicators'
            ? $this->importIndicators($parsed['rows'], $request->input('mode', 'skip'), $dryRun, $request->user()->id)
            : $this->importReports($parsed['rows'], $dryRun, $request->user()->id);

        if (! $dryRun) {
            ActivityLogger::log("{$type}.import", "Mengimpor {$result['created']} baru, {$result['updated']} diperbarui dari {$request->file('file')->getClientOriginalName()}", null, [
                'skipped' => $result['skipped'], 'errors' => count($result['errors']),
            ]);
        }

        return back()->with('importResult', $result + ['type' => $type, 'dry_run' => $dryRun, 'total' => count($parsed['rows'])])
            ->with('toast', [count($result['errors']) ? 'warning' : 'success', $dryRun ? 'Validasi selesai (dry-run, tidak ada data tersimpan).' : 'Impor selesai.']);
    }

    public function print(ConnectivityIndexService $dci, PriorityService $priority): View
    {
        ActivityLogger::log('summary.export', 'Membuka ringkasan eksekutif (cetak/PDF)');

        return view('data.print', [
            'ranking' => $dci->ranking(),
            'priorities' => $priority->rank()->where('gap_score', '>', 0)->take(10),
            'reportStatus' => Report::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'severity' => Report::selectRaw('severity_level, count(*) as total')->groupBy('severity_level')->pluck('total', 'severity_level'),
            'projects' => InfrastructureProject::with('region.country')->orderByDesc('priority_score')->get(),
        ]);
    }

    public function backup(): JsonResponse
    {
        ActivityLogger::log('data.backup', 'Mengunduh backup JSON seluruh data');

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'app' => config('signal.app_name'),
            'countries' => Country::with('regions')->get(),
            'indicators' => Indicator::all(),
            'reports' => Report::all(),
            'projects' => InfrastructureProject::with('updates')->get(),
        ], 200, ['Content-Disposition' => 'attachment; filename="signalatlas-backup-'.now()->format('Ymd-His').'.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ------------------------------------------------------------------

    private function importIndicators(array $rows, string $mode, bool $dryRun, int $userId): array
    {
        $countries = Country::pluck('id', 'iso3');
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        DB::beginTransaction();
        foreach ($rows as $row) {
            $countryId = $countries[strtoupper((string) $row['iso3'])] ?? null;
            $payload = array_merge(array_intersect_key($row, array_flip(IndicatorController::CSV_HEADERS)), ['country_id' => $countryId]);
            $payload['data_source'] ??= 'Impor CSV';

            $rules = IndicatorController::rules();
            $rules['year'] = ['required', 'integer', 'between:2000,'.now()->year];
            $validator = Validator::make($payload, $rules, ['country_id.required' => "Kode negara \"{$row['iso3']}\" tidak dikenal."]);

            if ($validator->fails()) {
                $result['errors'][] = ['line' => $row['__line'], 'message' => $validator->errors()->first()];

                continue;
            }

            $data = $validator->validated();
            $existing = Indicator::where('country_id', $countryId)->where('year', $data['year'])->first();

            if ($existing && $mode === 'skip') {
                $result['skipped']++;

                continue;
            }

            if ($existing) {
                $existing->update($data);
                $result['updated']++;
            } else {
                Indicator::create($data + ['created_by' => $userId]);
                $result['created']++;
            }
        }
        $dryRun ? DB::rollBack() : DB::commit();

        return $result;
    }

    private function importReports(array $rows, bool $dryRun, int $userId): array
    {
        $severity = app(SeverityCalculator::class);
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        DB::beginTransaction();
        foreach ($rows as $row) {
            $region = Region::whereHas('country', fn ($q) => $q->where('iso3', strtoupper((string) $row['iso3'])))
                ->where('name', $row['region'] ?? '')->first();

            $validator = Validator::make($row + ['region_id' => $region?->id], [
                'region_id' => ['required'],
                'provider' => ['required', 'string', 'max:60'],
                'technology' => ['required', Rule::in(array_keys(config('signal.technologies')))],
                'issue_type' => ['required', Rule::in(array_keys(config('signal.issue_types')))],
                'download_mbps' => ['nullable', 'numeric', 'min:0'],
                'upload_mbps' => ['nullable', 'numeric', 'min:0'],
                'latency_ms' => ['nullable', 'integer', 'min:0'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'description' => ['required', 'string', 'min:5'],
            ], ['region_id.required' => "Wilayah \"{$row['region']}\" ({$row['iso3']}) tidak ditemukan."]);

            if ($validator->fails()) {
                $result['errors'][] = ['line' => $row['__line'], 'message' => $validator->errors()->first()];

                continue;
            }

            $data = $validator->validated();
            $calc = $severity->calculate($data);
            Report::create($data + [
                'code' => CodeGenerator::report(),
                'user_id' => $userId,
                'severity_score' => $calc['severity_score'],
                'severity_level' => $calc['severity_level'],
                'status' => 'pending',
                'source' => 'import',
                'latitude' => $data['latitude'] ?? $region->latitude,
                'longitude' => $data['longitude'] ?? $region->longitude,
            ]);
            $result['created']++;
        }
        $dryRun ? DB::rollBack() : DB::commit();

        return $result;
    }
}
