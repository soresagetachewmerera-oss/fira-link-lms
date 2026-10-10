<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Subscription Plan Model
 *
 * @package App\Models
 */
class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'billing_period',
        'duration_days',
        'course_limit',
        'features',
        'is_featured',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'duration_days' => 'integer',
        'course_limit' => 'integer',
        'features' => 'array',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'subscription_plan_courses', 'subscription_plan_id', 'course_id')
            ->withTimestamps();
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'subscription_plan_bundles', 'subscription_plan_id', 'bundle_id')
            ->withTimestamps();
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->discount_price !== null && $this->discount_price > 0 && $this->discount_price < $this->price) {
            return (float) $this->discount_price;
        }

        return (float) $this->price;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null && $this->discount_price > 0 && $this->discount_price < $this->price;
    }

    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->has_discount || $this->price <= 0) {
            return 0;
        }

        return (int) round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    public function getIsLifetimeAttribute(): bool
    {
        return $this->billing_period === 'lifetime' || $this->duration_days <= 0;
    }
}
