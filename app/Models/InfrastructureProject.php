<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfrastructureProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'region_id', 'title', 'type', 'description', 'budget_usd', 'status', 'progress', 'priority_score',
        'beneficiaries', 'start_date', 'target_date', 'completed_at', 'proposed_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'budget_usd' => 'float',
            'progress' => 'integer',
            'priority_score' => 'float',
            'beneficiaries' => 'integer',
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function statusLabel(): string
    {
        return config('signal.project_statuses.'.$this->status, $this->status);
    }

    public function typeLabel(): string
    {
        return config('signal.project_types.'.$this->type, $this->type);
    }

    /** Status tujuan yang diizinkan dari status saat ini. */
    public function allowedTransitions(): array
    {
        return config('signal.project_transitions.'.$this->status, []);
    }

    public function isOverdue(): bool
    {
        return $this->target_date
            && ! in_array($this->status, ['completed', 'cancelled', 'rejected'], true)
            && $this->target_date->isPast();
    }
}
