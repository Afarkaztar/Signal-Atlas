<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Indicator;
use App\Services\ActivityLogger;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndicatorController extends Controller
{
    public function index(Request $request): View
    {
        $indicators = Indicator::with('country')
            ->when($request->query('country'), fn ($q, $v) => $q->where('country_id', $v))
            ->when($request->query('year'), fn ($q, $v) => $q->where('year', $v))
            ->orderByDesc('year')->orderBy(Country::select('name')->whereColumn('countries.id', 'indicators.country_id'))
            ->paginate(15)->withQueryString();

        return view('indicators.index', [
            'indicators' => $indicators,
            'countries' => Country::orderBy('name')->get(),
            'years' => Indicator::distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    public function create(): View
    {
        return view('indicators.create', [
            'indicator' => new Indicator(['year' => now()->year - 1, 'data_source' => 'World Bank WDI · ITU · Ookla']),
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $indicator = Indicator::create($data);
        ActivityLogger::log('indicator.create', "Menambah indikator {$indicator->country->name} {$indicator->year}", $indicator);

        return redirect()->route('indicators.index')->with('toast', ['success', 'Indikator berhasil ditambahkan.']);
    }

    public function edit(Indicator $indicator): View
    {
        return view('indicators.edit', [
            'indicator' => $indicator,
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Indicator $indicator): RedirectResponse
    {
        $indicator->update($this->validated($request, $indicator));
        ActivityLogger::log('indicator.update', "Memperbarui indikator {$indicator->country->name} {$indicator->year}", $indicator);

        return redirect()->route('indicators.index')->with('toast', ['success', 'Indikator berhasil diperbarui.']);
    }

    public function destroy(Indicator $indicator): RedirectResponse
    {
        ActivityLogger::log('indicator.delete', "Menghapus indikator {$indicator->country->name} {$indicator->year}", $indicator);
        $indicator->delete();

        return back()->with('toast', ['success', 'Indikator dihapus.']);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = Indicator::with('country')
            ->when($request->query('country'), fn ($q, $v) => $q->where('country_id', $v))
            ->orderBy('year')->get();
        ActivityLogger::log('indicator.export', "Mengekspor {$rows->count()} indikator ke CSV");

        return CsvService::download('indikator-konektivitas-'.now()->format('Ymd').'.csv', self::CSV_HEADERS,
            $rows->map(fn (Indicator $i) => [
                $i->country->iso3, $i->year, $i->internet_users_pct, $i->mobile_broadband_per100, $i->fixed_broadband_per100,
                $i->avg_mobile_speed_mbps, $i->avg_fixed_speed_mbps, $i->data_price_usd_gb, $i->data_source,
            ]));
    }

    public const CSV_HEADERS = [
        'iso3', 'year', 'internet_users_pct', 'mobile_broadband_per100', 'fixed_broadband_per100',
        'avg_mobile_speed_mbps', 'avg_fixed_speed_mbps', 'data_price_usd_gb', 'data_source',
    ];

    public static function rules(?Indicator $ignore = null, $countryId = null, $year = null): array
    {
        return [
            'country_id' => ['required', 'exists:countries,id'],
            'year' => ['required', 'integer', 'between:2000,'.now()->year,
                Rule::unique('indicators', 'year')->where('country_id', $countryId)->ignore($ignore)],
            'internet_users_pct' => ['required', 'numeric', 'between:0,100'],
            'mobile_broadband_per100' => ['nullable', 'numeric', 'between:0,300'],
            'fixed_broadband_per100' => ['nullable', 'numeric', 'between:0,100'],
            'avg_mobile_speed_mbps' => ['nullable', 'numeric', 'between:0,2000'],
            'avg_fixed_speed_mbps' => ['nullable', 'numeric', 'between:0,5000'],
            'data_price_usd_gb' => ['nullable', 'numeric', 'between:0,100'],
            'data_source' => ['required', 'string', 'max:180'],
        ];
    }

    private function validated(Request $request, ?Indicator $ignore = null): array
    {
        return $request->validate(self::rules($ignore, $request->input('country_id')), [
            'year.unique' => 'Data negara ini untuk tahun tersebut sudah ada. Gunakan menu edit.',
        ]);
    }
}
