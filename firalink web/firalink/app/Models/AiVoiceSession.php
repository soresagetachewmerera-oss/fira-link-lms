<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVoiceSession extends Model
{
    use HasFactory;

    protected $table = 'ai_voice_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'language',
        'model',
        'status',
        'token_expires_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'tool_calls_count',
        'tools_used',
        'error_code',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'tool_calls_count' => 'integer',
        'tools_used' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
