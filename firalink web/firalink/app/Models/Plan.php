<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    const KEY_STARTER = 'starter';
    const KEY_GROWTH = 'growth';
    const KEY_BUSINESS = 'business';

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_public',
        'is_active',
        'sort_order',
        'trial_days',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'trial_days' => 'integer',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function activePrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class)->where('is_active', true);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlement::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getPrice(string $currency = 'ETB', string $period = 'monthly'): ?PlanPrice
    {
        return $this->prices()
            ->where('currency', strtoupper($currency))
            ->where('billing_period', strtolower($period))
            ->where('is_active', true)
            ->first();
    }
}
