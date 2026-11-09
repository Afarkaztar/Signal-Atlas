<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur bisnis laporan publik:
 *   pending ──verify──▶ verified ──(proyek selesai)──▶ resolved
 *      └─────reject───▶ rejected
 */
class ReportWorkflow
{
    public function verify(Report $report, User $reviewer, ?string $note = null): Report
    {
        $this->guard($report, $reviewer);

        return DB::transaction(function () use ($report, $reviewer, $note) {
            $report->update([
                'status' => 'verified',
                'verified_by' => $reviewer->id,
                'verified_at' => now(),
                'review_note' => $note,
            ]);

            if ($report->user) {
                $report->user->increment('points', config('signal.points.verified'));
                $report->user->notify(new SystemNotification(
                    'Laporan terverifikasi',
                    "Laporan {$report->code} telah diverifikasi. +".config('signal.points.verified').' poin kontributor!',
                    route('reports.show', $report),
                    'badge-check',
                    'success',
                ));
            }

            ActivityLogger::log('report.verify', "Memverifikasi laporan {$report->code}", $report);

            return $report;
        });
    }

    public function reject(Report $report, User $reviewer, string $note): Report
    {
        $this->guard($report, $reviewer);

        return DB::transaction(function () use ($report, $reviewer, $note) {
            $report->update([
                'status' => 'rejected',
                'verified_by' => $reviewer->id,
                'verified_at' => now(),
                'review_note' => $note,
            ]);

            $report->user?->notify(new SystemNotification(
                'Laporan ditolak',
                "Laporan {$report->code} ditolak: {$note}",
                route('reports.show', $report),
                'x-octagon',
                'danger',
            ));

            ActivityLogger::log('report.reject', "Menolak laporan {$report->code}", $report, ['note' => $note]);

            return $report;
        });
    }

    /**
     * Aturan: hanya laporan "pending" yang bisa ditinjau,
     * dan verifikator tidak boleh meninjau laporannya sendiri.
     */
    private function guard(Report $report, User $reviewer): void
    {
        if (! $reviewer->isStaff()) {
            throw ValidationException::withMessages(['report' => 'Hanya analis atau admin yang dapat meninjau laporan.']);
        }

        if ($report->status !== 'pending') {
            throw ValidationException::withMessages(['report' => "Laporan {$report->code} sudah ditinjau sebelumnya."]);
        }

        if ($report->user_id === $reviewer->id) {
            throw ValidationException::withMessages(['report' => "Anda tidak dapat meninjau laporan milik sendiri ({$report->code})."]);
        }
    }
}
