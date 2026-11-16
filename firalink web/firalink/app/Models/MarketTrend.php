<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketTrend extends Model
{
    protected $fillable = ['series_id', 'computed_at', 'trend', 'last_close', 'average_20', 'slope_5', 'sample_size', 'inputs'];
    protected $casts = ['computed_at' => 'datetime', 'last_close' => 'decimal:8', 'average_20' => 'decimal:8', 'slope_5' => 'decimal:8', 'inputs' => 'array'];
    public function series(): BelongsTo { return $this->belongsTo(MarketSeries::class, 'series_id'); }
}
