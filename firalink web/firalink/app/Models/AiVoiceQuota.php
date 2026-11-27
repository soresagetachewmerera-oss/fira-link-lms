<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVoiceQuota extends Model
{
    use HasFactory;

    protected $table = 'ai_voice_quotas';

    protected $fillable = [
        'user_id',
        'ip_address',
        'usage_date',
        'seconds_used',
        'seconds_limit',
        'sessions_count',
        'cost_estimated_usd',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'seconds_used' => 'integer',
        'seconds_limit' => 'integer',
        'sessions_count' => 'integer',
        'cost_estimated_usd' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->seconds_limit - $this->seconds_used);
    }

    public function hasExceededLimit(): bool
    {
        return $this->seconds_used >= $this->seconds_limit;
    }
}
