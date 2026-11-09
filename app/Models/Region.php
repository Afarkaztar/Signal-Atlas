<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['country_id', 'name', 'area_type', 'population', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'population' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(InfrastructureProject::class);
    }

    public function areaLabel(): string
    {
        return config('signal.area_types.'.$this->area_type, $this->area_type);
    }
}
