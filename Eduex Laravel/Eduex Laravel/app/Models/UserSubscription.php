<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * User Subscription Model
 *
 * @package App\Models
 */
class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'payment_id',
        'starts_at',
        'ends_at',
        'status',
        'courses_accessed_count',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'courses_accessed_count' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PENDING_APPROVAL = 'pending_approval';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->ends_at === null) {
            return true; // Lifetime
        }

        return $this->ends_at->isFuture();
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return true;
        }

        return false;
    }

    public function getRemainingDaysAttribute(): int
    {
        if ($this->ends_at === null) {
            return 99999; // Lifetime indicator
        }

        if ($this->ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->ends_at, false);
    }

    /**
     * Check if user has access to a specific course via this subscription
     */
    public function includesCourse(int $courseId): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->plan->courses()->where('courses.id', $courseId)->exists();
    }

    /**
     * Check if user has access to a specific bundle via this subscription
     */
    public function includesBundle(int $bundleId): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->plan->bundles()->where('bundles.id', $bundleId)->exists();
    }
}
