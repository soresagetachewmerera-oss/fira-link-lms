<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketSeries extends Model
{
    protected $table = 'market_series';
    protected $fillable = ['source_id', 'symbol', 'name', 'short_name', 'price_type', 'currency', 'unit', 'decimals', 'supports_intraday', 'minimum_value', 'maximum_value', 'maximum_change_percent', 'is_hero', 'on_home', 'enabled', 'sort_order', 'configuration'];
    protected $casts = ['minimum_value' => 'decimal:8', 'maximum_value' => 'decimal:8', 'maximum_change_percent' => 'decimal:4', 'decimals' => 'integer', 'supports_intraday' => 'boolean', 'is_hero' => 'boolean', 'on_home' => 'boolean', 'enabled' => 'boolean', 'configuration' => 'array'];

    public function source(): BelongsTo { return $this->belongsTo(MarketSource::class, 'source_id'); }
    public function prices(): HasMany { return $this->hasMany(MarketPrice::class, 'series_id'); }
    public function trend(): HasOne { return $this->hasOne(MarketTrend::class, 'series_id'); }
}
