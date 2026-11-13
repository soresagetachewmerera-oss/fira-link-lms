<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Coupon Model
 *
 * This model represents a discount coupon.
 *
 * @package App\Models
 */
class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'valid_from',
        'valid_to',
        'usage_limit',
        'used_count',
        'course_id',
        'instructor_id',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'used_count' => 'integer',
        'usage_limit' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    /**
     * Get the course restricted to this coupon.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the instructor who created this coupon.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get usages of this coupon.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is currently active and within valid dates.
     */
    public function isValidNow(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_to && $now->gt($this->valid_to)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
