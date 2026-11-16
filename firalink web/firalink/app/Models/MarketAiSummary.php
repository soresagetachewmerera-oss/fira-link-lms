<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketAiSummary extends Model
{
    protected $fillable = ['text', 'source_rows', 'data_fingerprint', 'status'];
    protected $casts = ['source_rows' => 'array'];
}
