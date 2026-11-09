<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'properties', 'ip_address', 'created_at'];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ikon lucide & warna untuk setiap jenis aksi. */
    public function visual(): array
    {
        return match (true) {
            str_contains($this->action, 'delete') => ['trash-2', '#ff8f83'],
            str_contains($this->action, 'verify') => ['badge-check', '#60e1bd'],
            str_contains($this->action, 'reject') => ['x-octagon', '#ff8f83'],
            str_contains($this->action, 'import') => ['upload', '#b99aff'],
            str_contains($this->action, 'export') => ['download', '#b99aff'],
            str_contains($this->action, 'login') || str_contains($this->action, 'logout') => ['log-in', '#76a9ff'],
            str_contains($this->action, 'project') => ['hard-hat', '#f2cc72'],
            str_contains($this->action, 'create') => ['plus-circle', '#60e1bd'],
            str_contains($this->action, 'update') => ['pencil', '#76a9ff'],
            default => ['activity', '#8c9bb1'],
        };
    }
}
