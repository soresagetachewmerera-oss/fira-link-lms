<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPriceAlert extends Model
{
    public const DIRECTIONS = ['above', 'below'];
    public const CHANNELS = ['push', 'mail'];
    public const MAX_PER_USER = 20;

    // user_id, series_id and the evaluation state are set by MarketAlertService, never from a request.
    protected $fillable = ['direction', 'threshold', 'channels'];
    protected $casts = ['threshold' => 'decimal:8', 'channels' => 'array', 'active' => 'boolean', 'triggered_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function series(): BelongsTo { return $this->belongsTo(MarketSeries::class, 'series_id'); }
}
