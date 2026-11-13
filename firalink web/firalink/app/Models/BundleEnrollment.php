<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BundleEnrollment Model
 *
 * Tracks user purchases of course bundles.
 */
class BundleEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'bundle_id',
        'payment_id',
        'status',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
