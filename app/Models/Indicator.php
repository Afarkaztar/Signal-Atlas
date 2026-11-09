<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicator extends Model
{
    protected $fillable = [
        'country_id', 'year', 'internet_users_pct', 'mobile_broadband_per100', 'fixed_broadband_per100',
        'avg_mobile_speed_mbps', 'avg_fixed_speed_mbps', 'data_price_usd_gb', 'data_source', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'internet_users_pct' => 'float',
            'mobile_broadband_per100' => 'float',
            'fixed_broadband_per100' => 'float',
            'avg_mobile_speed_mbps' => 'float',
            'avg_fixed_speed_mbps' => 'float',
            'data_price_usd_gb' => 'float',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
