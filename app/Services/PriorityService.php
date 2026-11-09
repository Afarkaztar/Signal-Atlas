<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Support\Collection;

/**
 * Analisis kesenjangan konektivitas per wilayah (Connectivity Gap Score)
 * dan rekomendasi jenis infrastruktur yang paling sesuai.
 *
 * Gap Score (0–100) =
 *     0.5 × rata-rata keparahan laporan terverifikasi        (maks 50)
 *   + min(4 × jumlah laporan aktif, 25)                       (maks 25)
 *   + bobot tipe wilayah (terpencil 15, desa 10, pinggiran 5) (maks 15)
 *   + faktor populasi (skala logaritmik)                      (maks 10)
 *
 * Jika wilayah sudah memiliki proyek aktif, skor efektif dikalikan 0.5
 * agar wilayah yang belum tertangani naik ke atas antrean prioritas.
 */
class PriorityService
{
    public const AREA_WEIGHTS = ['remote' => 15, 'rural' => 10, 'suburban' => 5, 'urban' => 0];

    public const COVERAGE = ['bts' => 0.15, 'satellite' => 0.06, 'fiber' => 0.25, 'public_wifi' => 0.03, 'microwave' => 0.12];

    public function rank(?int $countryId = null): Collection
    {
        $verified = fn ($q) => $q->where('status', 'verified');

        return Region::query()
            ->with('country')
            ->when($countryId, fn ($q) => $q->where('country_id', $countryId))
            ->withCount(['reports as active_reports' => $verified])
            ->withCount(['reports as blank_reports' => fn ($q) => $q->where('status', 'verified')
                ->where(fn ($w) => $w->where('issue_type', 'blank_spot')->orWhere('technology', 'none'))])
            ->withAvg(['reports as avg_severity' => $verified], 'severity_score')
            ->withAvg(['reports as avg_download' => $verified], 'download_mbps')
            ->withCount(['projects as active_projects' => fn ($q) => $q->whereIn('status', ['approved', 'in_progress', 'on_hold'])])
            ->get()
            ->map(fn (Region $region) => $this->analyse($region))
            ->sortByDesc('effective_score')
            ->values();
    }

    public function forRegion(Region $region): array
    {
        $region = $this->rank($region->country_id)->firstWhere('region.id', $region->id);

        return $region ?? ['gap_score' => 0, 'effective_score' => 0];
    }

    public function analyse(Region $region): array
    {
        $active = (int) $region->active_reports;
        $avgSeverity = (float) ($region->avg_severity ?? 0);
        $blankRatio = $active ? $region->blank_reports / $active : 0;

        $popFactor = $region->population > 0 ? min(10, max(0, (log10($region->population) - 4) * 3.3)) : 0;

        $gap = $active === 0 ? 0 : ($avgSeverity * 0.5)
            + min($active * 4, 25)
            + (self::AREA_WEIGHTS[$region->area_type] ?? 0)
            + $popFactor;

        $gap = round(min(100, $gap), 1);
        $effective = $region->active_projects > 0 ? round($gap * 0.5, 1) : $gap;
        $type = $this->recommendType($region->area_type, $blankRatio, $region->avg_download);

        return [
            'region' => $region,
            'active_reports' => $active,
            'avg_severity' => round($avgSeverity, 1),
            'avg_download' => $region->avg_download !== null ? round((float) $region->avg_download, 1) : null,
            'blank_ratio' => round($blankRatio * 100),
            'gap_score' => $gap,
            'effective_score' => $effective,
            'level' => SeverityCalculator::levelFor($gap),
            'handled' => $region->active_projects > 0,
            'recommended_type' => $type,
            'estimated_cost' => round(config("signal.unit_costs.$type") * (1 + $active / 20), -3),
            'estimated_beneficiaries' => (int) round($region->population * self::COVERAGE[$type]),
            'reason' => $this->reason($type, $region->area_type, $blankRatio, $region->avg_download),
        ];
    }

    private function recommendType(string $area, float $blankRatio, $avgDownload): string
    {
        return match (true) {
            $blankRatio >= 0.5 && $area === 'remote' => 'satellite',
            $blankRatio >= 0.5 => 'bts',
            in_array($area, ['urban', 'suburban'], true) && ($avgDownload ?? 0) < 25 => 'fiber',
            $area === 'rural' => 'microwave',
            $area === 'remote' => 'satellite',
            default => 'public_wifi',
        };
    }

    private function reason(string $type, string $area, float $blankRatio, $avgDownload): string
    {
        $pct = round($blankRatio * 100);

        return match ($type) {
            'satellite' => "{$pct}% laporan berupa blank spot di wilayah terpencil — satelit LEO paling cepat dan ekonomis digelar.",
            'bts' => "{$pct}% laporan menunjukkan tidak ada sinyal — perlu menara BTS baru untuk perluasan cakupan.",
            'fiber' => 'Wilayah padat dengan kecepatan rata-rata '.round((float) $avgDownload, 1).' Mbps — upgrade ke fiber optik menaikkan kapasitas.',
            'microwave' => 'Sinyal tersedia namun lambat/tidak stabil — perkuat backhaul microwave menara eksisting.',
            default => 'Masalah relatif ringan — Wi-Fi publik di titik layanan cukup untuk menjangkau warga.',
        };
    }
}
