<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'iso3', 'iso2', 'capital', 'population', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'population' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function latestIndicator(): HasOne
    {
        return $this->hasOne(Indicator::class)->latestOfMany('year');
    }

    public function reports(): HasManyThrough
    {
        return $this->hasManyThrough(Report::class, Region::class);
    }

    /** Emoji bendera dari kode ISO2. */
    public function flag(): string
    {
        $code = strtoupper($this->iso2);

        return mb_chr(127397 + ord($code[0])).mb_chr(127397 + ord($code[1]));
    }
}
