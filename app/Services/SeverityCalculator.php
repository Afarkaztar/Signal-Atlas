<?php

namespace App\Services;

/**
 * Menghitung skor keparahan (0–100) sebuah laporan konektivitas.
 *
 * Komponen skor:
 *  - Kecepatan unduh  (maks 45) : makin lambat makin parah, mengacu ambang broadband 10/25 Mbps
 *  - Latensi          (maks 15) : > 200 ms dianggap buruk
 *  - Jenis masalah    (maks 25) : blank spot paling berat
 *  - Teknologi        (maks 15) : tidak ada sinyal / 2G paling berat
 *
 * Level: >= 75 kritis, >= 50 tinggi, >= 25 sedang, sisanya rendah.
 * Rumus yang sama dicerminkan di JavaScript (pratinjau langsung di form).
 */
class SeverityCalculator
{
    public const ISSUE_WEIGHTS = ['blank_spot' => 25, 'outage' => 20, 'unstable' => 15, 'slow' => 10, 'expensive' => 8];

    public const TECH_WEIGHTS = ['none' => 15, '2g' => 11, '3g' => 7, '4g' => 3, '5g' => 0, 'fiber' => 0];

    public function calculate(array $data): array
    {
        $download = $data['download_mbps'] ?? null;
        $latency = $data['latency_ms'] ?? null;
        $issue = $data['issue_type'] ?? 'slow';
        $tech = $data['technology'] ?? '4g';

        $speedScore = match (true) {
            $download === null || $download === '' => $tech === 'none' || $issue === 'blank_spot' ? 45 : 20,
            (float) $download <= 0.5 => 45,
            (float) $download < 3 => 36,
            (float) $download < 10 => 26,
            (float) $download < 25 => 14,
            (float) $download < 50 => 6,
            default => 0,
        };

        $latencyScore = match (true) {
            $latency === null || $latency === '' => $issue === 'blank_spot' ? 15 : 6,
            (int) $latency > 300 => 15,
            (int) $latency > 150 => 11,
            (int) $latency > 80 => 6,
            (int) $latency > 40 => 2,
            default => 0,
        };

        $score = $speedScore + $latencyScore
            + (self::ISSUE_WEIGHTS[$issue] ?? 10)
            + (self::TECH_WEIGHTS[$tech] ?? 5);

        $score = (int) max(0, min(100, $score));

        return [
            'severity_score' => $score,
            'severity_level' => self::levelFor($score),
            'breakdown' => [
                'Kecepatan unduh' => $speedScore,
                'Latensi' => $latencyScore,
                'Jenis masalah' => self::ISSUE_WEIGHTS[$issue] ?? 10,
                'Teknologi' => self::TECH_WEIGHTS[$tech] ?? 5,
            ],
        ];
    }

    public static function levelFor(int|float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };
    }
}
