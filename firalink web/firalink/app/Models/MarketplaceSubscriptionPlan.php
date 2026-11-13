<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MarketplaceSubscriptionPlan Model
 *
 * Defines seller and buyer tiers (Starter, Pro Exporter, Enterprise Trader).
 */
class MarketplaceSubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'marketplace_subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'role_target',
        'description',
        'price_etb',
        'price_usd',
        'billing_cycle',
        'active_listings_limit',
        'rfq_response_limit',
        'featured_slots',
        'verified_badge_included',
        'analytics_access',
        'priority_support',
        'features',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_etb' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'active_listings_limit' => 'integer',
        'rfq_response_limit' => 'integer',
        'featured_slots' => 'integer',
        'verified_badge_included' => 'boolean',
        'analytics_access' => 'boolean',
        'priority_support' => 'boolean',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
    ];

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserMarketplaceSubscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    public function scopeForSellers($query)
    {
        return $query->where('role_target', 'seller');
    }

    public function scopeForBuyers($query)
    {
        return $query->where('role_target', 'buyer');
    }
}

