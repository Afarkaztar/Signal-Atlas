<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Indicator;
use Illuminate\Support\Collection;

/**
 * Digital Connectivity Index (DCI) — indeks komposit 0–100 per negara.
 *
 * Komponen (dinormalisasi 0–100) & bobot:
 *  - Pengguna internet (% populasi)            35%
 *  - Langganan mobile broadband (/100, cap 150) 20%
 *  - Langganan fixed broadband (/100, cap 40)   15%
 *  - Kecepatan rata-rata (Mbps, cap 100)        20%
 *  - Keterjangkauan harga data (USD/GB, ≤ $5)    10%
 *
 * Komponen kosong tidak dihitung dan bobotnya didistribusikan ulang.
 */
class ConnectivityIndexService
{
    public const WEIGHTS = ['users' => 0.35, 'mobile' => 0.20, 'fixed' => 0.15, 'speed' => 0.20, 'afford' => 0.10];

    public const LABELS = [
        'users' => 'Pengguna Internet',
        'mobile' => 'Mobile Broadband',
        'fixed' => 'Fixed Broadband',
        'speed' => 'Kecepatan',
        'afford' => 'Keterjangkauan',
    ];

    public function components(Indicator $i): array
    {
        $speeds = array_filter([$i->avg_mobile_speed_mbps, $i->avg_fixed_speed_mbps], fn ($v) => $v !== null);

        return [
            'users' => $i->internet_users_pct,
            'mobile' => $i->mobile_broadband_per100 !== null ? min($i->mobile_broadband_per100 / 150, 1) * 100 : null,
            'fixed' => $i->fixed_broadband_per100 !== null ? min($i->fixed_broadband_per100 / 40, 1) * 100 : null,
            'speed' => $speeds ? min((array_sum($speeds) / count($speeds)) / 100, 1) * 100 : null,
            'afford' => $i->data_price_usd_gb !== null ? (1 - min($i->data_price_usd_gb, 5) / 5) * 100 : null,
        ];
    }

    public function score(Indicator $i): float
    {
        $total = 0;
        $weight = 0;

        foreach ($this->components($i) as $key => $value) {
            if ($value === null) {
                continue;
            }
            $total += $value * self::WEIGHTS[$key];
            $weight += self::WEIGHTS[$key];
        }

        return $weight ? round($total / $weight, 1) : 0;
    }

    public static function grade(float $score): array
    {
        return match (true) {
            $score >= 80 => ['A', 'Maju', '#60e1bd'],
            $score >= 65 => ['B', 'Berkembang Pesat', '#76a9ff'],
            $score >= 50 => ['C', 'Berkembang', '#b99aff'],
            $score >= 35 => ['D', 'Tertinggal', '#f2cc72'],
            default => ['E', 'Sangat Tertinggal', '#ff8f83'],
        };
    }

    /** Peringkat seluruh negara untuk satu tahun (memakai data terbaru ≤ tahun tsb). */
    public function ranking(?int $year = null): Collection
    {
        $year ??= (int) Indicator::max('year');

        return Country::query()
            ->with(['indicators' => fn ($q) => $q->where('year', '<=', $year)->orderByDesc('year')])
            ->get()
            ->map(function (Country $country) {
                $indicator = $country->indicators->first();
                if (! $indicator) {
                    return null;
                }
                $previous = $country->indicators->firstWhere('year', $indicator->year - 1);
                $score = $this->score($indicator);

                return [
                    'country' => $country,
                    'indicator' => $indicator,
                    'components' => array_map(fn ($v) => $v === null ? null : round($v, 1), $this->components($indicator)),
                    'score' => $score,
                    'delta' => $previous ? round($score - $this->score($previous), 1) : null,
                    'grade' => self::grade($score),
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /** Seri waktu skor DCI per negara: [iso3 => [year => score]]. */
    public function series(): array
    {
        return Indicator::with('country')->orderBy('year')->get()
            ->groupBy(fn ($i) => $i->country->iso3)
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($i) => [$i->year => $this->score($i)]))
            ->toArray();
    }
}
