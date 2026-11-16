<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPrice extends Model
{
    protected $fillable = ['series_id', 'value', 'currency', 'unit', 'source_time', 'fetched_at', 'status', 'raw_ref', 'reviewed_by', 'reviewed_at', 'review_reason'];
    protected $casts = ['value' => 'decimal:8', 'source_time' => 'datetime', 'fetched_at' => 'datetime', 'raw_ref' => 'array', 'reviewed_at' => 'datetime'];
    public function series(): BelongsTo { return $this->belongsTo(MarketSeries::class, 'series_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
