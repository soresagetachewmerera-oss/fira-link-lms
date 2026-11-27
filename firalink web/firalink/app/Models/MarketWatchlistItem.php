<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketWatchlistItem extends Model
{
    protected $fillable = [];

    public function series(): BelongsTo { return $this->belongsTo(MarketSeries::class, 'series_id'); }
}
