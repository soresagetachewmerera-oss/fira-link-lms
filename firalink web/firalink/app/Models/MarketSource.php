<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketSource extends Model
{
    protected $fillable = ['slug', 'name', 'kind', 'url', 'adapter', 'enabled', 'terms_note', 'attribution', 'refresh_minutes', 'max_age_minutes', 'configuration', 'last_attempt_at', 'last_success_at', 'last_error'];

    protected $casts = [
        'enabled' => 'boolean', 'configuration' => 'array', 'last_attempt_at' => 'datetime', 'last_success_at' => 'datetime',
    ];

    public function series(): HasMany
    {
        return $this->hasMany(MarketSeries::class, 'source_id');
    }
}
