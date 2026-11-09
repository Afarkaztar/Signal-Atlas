<?php

namespace App\Services;

use App\Models\InfrastructureProject;
use App\Models\Report;

/**
 * Generator kode dokumen berurutan, misal RPT-2611-0042 / PRJ-2026-007.
 */
class CodeGenerator
{
    public static function report(): string
    {
        $prefix = 'RPT-'.now()->format('ym').'-';
        $last = Report::where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function project(): string
    {
        $prefix = 'PRJ-'.now()->format('Y').'-';
        $last = InfrastructureProject::where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
