<?php

namespace App\Services;

use App\Models\InfrastructureProject;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * State machine proyek infrastruktur.
 *
 *  proposed ─▶ approved ─▶ in_progress ─▶ completed
 *     │           │            │  ▲
 *     ▼           ▼            ▼  │
 *  rejected   cancelled ◀── on_hold
 *
 * Efek samping:
 *  - approved  : seluruh laporan terverifikasi di wilayah tsb ditautkan ke proyek
 *  - completed : laporan tertaut otomatis menjadi "resolved", pelapor mendapat poin & notifikasi
 */
class ProjectWorkflow
{
    public function transition(InfrastructureProject $project, string $to, User $actor, ?string $note = null): InfrastructureProject
    {
        $from = $project->status;

        if (! in_array($to, $project->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Transisi dari "%s" ke "%s" tidak diizinkan.',
                    config("signal.project_statuses.$from"),
                    config("signal.project_statuses.$to", $to)
                ),
            ]);
        }

        if (in_array($to, config('signal.admin_only_transitions'), true) && ! $actor->isAdmin()) {
            throw ValidationException::withMessages(['status' => 'Hanya Administrator yang dapat menyetujui, menolak, atau membatalkan proyek.']);
        }

        if ($to === 'in_progress' && ! $project->start_date) {
            $project->start_date = now();
        }

        return DB::transaction(function () use ($project, $from, $to, $actor, $note) {
            $attributes = ['status' => $to];

            if ($to === 'approved') {
                $attributes['approved_by'] = $actor->id;
                $linked = $project->region->reports()
                    ->where('status', 'verified')
                    ->whereNull('infrastructure_project_id')
                    ->update(['infrastructure_project_id' => $project->id]);
                $note = trim(($note ?? '')." [{$linked} laporan terverifikasi ditautkan otomatis]");
            }

            if ($to === 'completed') {
                $attributes['progress'] = 100;
                $attributes['completed_at'] = now();
                $this->resolveLinkedReports($project);
            }

            $project->fill($attributes)->save();

            ProjectUpdate::create([
                'infrastructure_project_id' => $project->id,
                'user_id' => $actor->id,
                'from_status' => $from,
                'to_status' => $to,
                'progress' => $project->progress,
                'note' => $note,
            ]);

            if ($project->proposer && $project->proposer->id !== $actor->id) {
                $project->proposer->notify(new SystemNotification(
                    'Status proyek berubah',
                    "{$project->code} kini berstatus \"{$project->statusLabel()}\".",
                    route('projects.show', $project),
                    'hard-hat',
                    in_array($to, ['rejected', 'cancelled'], true) ? 'danger' : 'success',
                ));
            }

            ActivityLogger::log('project.transition', "Mengubah status {$project->code}: {$from} → {$to}", $project, compact('from', 'to'));

            return $project;
        });
    }

    public function updateProgress(InfrastructureProject $project, int $progress, User $actor, ?string $note = null): InfrastructureProject
    {
        if ($project->status !== 'in_progress') {
            throw ValidationException::withMessages(['progress' => 'Progres hanya dapat diperbarui ketika proyek berstatus "Dalam Pengerjaan".']);
        }

        if ($progress < $project->progress) {
            throw ValidationException::withMessages(['progress' => "Progres tidak boleh mundur dari {$project->progress}%."]);
        }

        // Progres 100% otomatis menyelesaikan proyek
        if ($progress >= 100) {
            return $this->transition($project, 'completed', $actor, $note ?: 'Progres mencapai 100%.');
        }

        $project->update(['progress' => $progress]);

        ProjectUpdate::create([
            'infrastructure_project_id' => $project->id,
            'user_id' => $actor->id,
            'progress' => $progress,
            'note' => $note,
        ]);

        ActivityLogger::log('project.progress', "Memperbarui progres {$project->code} menjadi {$progress}%", $project);

        return $project;
    }

    private function resolveLinkedReports(InfrastructureProject $project): void
    {
        $reports = $project->reports()->where('status', 'verified')->with('user')->get();

        foreach ($reports as $report) {
            $report->update(['status' => 'resolved', 'resolved_at' => now()]);

            if ($report->user) {
                $report->user->increment('points', config('signal.points.resolved'));
                $report->user->notify(new SystemNotification(
                    'Masalah Anda terselesaikan 🎉',
                    "Laporan {$report->code} diselesaikan melalui proyek {$project->code}.",
                    route('reports.show', $report),
                    'party-popper',
                    'success',
                ));
            }
        }
    }
}
