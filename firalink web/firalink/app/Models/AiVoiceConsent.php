<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVoiceConsent extends Model
{
    use HasFactory;

    protected $table = 'ai_voice_consents';

    protected $fillable = [
        'user_id',
        'ip_address',
        'consented',
        'consent_version',
        'store_transcripts',
        'consented_at',
    ];

    protected $casts = [
        'consented' => 'boolean',
        'store_transcripts' => 'boolean',
        'consented_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
