<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Vetted by Fira Link" grant. Created only by DocumentReviewService::grantTier3 after the
 * config checklist is complete. Nothing is fillable.
 */
class VerificationTier3Grant extends Model
{
    protected $guarded = ['*'];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'checklist' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
