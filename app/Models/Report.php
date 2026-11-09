<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'user_id', 'region_id', 'provider', 'technology', 'issue_type', 'download_mbps', 'upload_mbps',
        'latency_ms', 'description', 'latitude', 'longitude', 'photo_path', 'severity_score', 'severity_level',
        'status', 'source', 'verified_by', 'verified_at', 'review_note', 'infrastructure_project_id', 'resolved_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'download_mbps' => 'float',
            'upload_mbps' => 'float',
            'latency_ms' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'severity_score' => 'integer',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(InfrastructureProject::class, 'infrastructure_project_id');
    }

    /** Laporan yang dianggap "aktif" dalam perhitungan kesenjangan. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }

    /** Filter dinamis dari query string halaman daftar laporan. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, $term) {
                $q->where(function (Builder $w) use ($term) {
                    $w->where('code', 'like', "%{$term}%")
                        ->orWhere('provider', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('region', fn (Builder $r) => $r->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['issue_type'] ?? null, fn (Builder $q, $v) => $q->where('issue_type', $v))
            ->when($filters['severity'] ?? null, fn (Builder $q, $v) => $q->where('severity_level', $v))
            ->when($filters['technology'] ?? null, fn (Builder $q, $v) => $q->where('technology', $v))
            ->when($filters['country'] ?? null, fn (Builder $q, $v) => $q->whereHas('region', fn (Builder $r) => $r->where('country_id', $v)))
            ->when($filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    public function isEditable(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return config('signal.report_statuses.'.$this->status, $this->status);
    }

    public function issueLabel(): string
    {
        return config('signal.issue_types.'.$this->issue_type, $this->issue_type);
    }

    public function technologyLabel(): string
    {
        return config('signal.technologies.'.$this->technology, $this->technology);
    }

    public function severityLabel(): string
    {
        return config('signal.severity_levels.'.$this->severity_level, $this->severity_level);
    }
}
