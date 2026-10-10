<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CouponUsage Model
 *
 * This model tracks the usage of a coupon by a user.
 *
 * @package App\Models
 */
class CouponUsage extends Model
{
    protected $table = 'coupon_usages';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'enrollment_id',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Get the coupon that was used.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the user who used the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the enrollment associated with this usage.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
