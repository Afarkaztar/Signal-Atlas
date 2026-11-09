<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUpdate extends Model
{
    protected $fillable = ['infrastructure_project_id', 'user_id', 'from_status', 'to_status', 'progress', 'note', 'created_at'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(InfrastructureProject::class, 'infrastructure_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
