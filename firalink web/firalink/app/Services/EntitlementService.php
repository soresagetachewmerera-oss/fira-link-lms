<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\Feature;
use App\Models\Organization;
use App\Models\OrganizationEntitlementOverride;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\UsageCounter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntitlementService
{
    protected const CACHE_PREFIX = 'org_entitlements_';
    protected const CACHE_TTL_SECONDS = 300; // 5 minutes

    /**
     * Check if an organization can perform an action or has access to a feature.
     */
    public function can(Organization $org, string $featureKey): bool
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return false;
        }

        // 1. Resolve raw value via hierarchy: Override > Plan > Default Deny
        $val = $this->resolveRawValue($org, $featureKey);
        if ($val === null) {
            return false;
        }

        if ($feature->type === Feature::TYPE_BOOLEAN) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1';
        }

        if ($feature->type === Feature::TYPE_LIMIT) {
            $limit = (int) $val;
            if ($limit === -1) {
                return true; // Unlimited
            }
            if ($limit <= 0) {
                return false;
            }

            // For limit features, can() checks if remaining quota > 0
            return $this->remaining($org, $featureKey) > 0;
        }

        if ($feature->type === Feature::TYPE_TIER) {
            return !empty($val) && $val !== 'none';
        }

        return !empty($val);
    }

    /**
     * Get the limit integer for a feature (-1 = unlimited, 0 = none).
     */
    public function limit(Organization $org, string $featureKey): int
    {
        $val = $this->resolveRawValue($org, $featureKey);
        if ($val === null) {
            return 0;
        }

        return (int) $val;
    }

    /**
     * Get current usage count for a feature in the appropriate period.
     */
    public function used(Organization $org, string $featureKey, ?string $periodKey = null): int
    {
        $period = $periodKey ?? $this->defaultPeriodKeyFor($featureKey);

        // Special dynamic counters that inspect live database state
        if ($featureKey === 'seats') {
            return $org->memberships()->where('is_active', true)->count();
        }

        if ($featureKey === 'active_lots') {
            // Check lots owned by this organization's members
            $memberUserIds = $org->memberships()->pluck('user_id');
            return CoffeeListing::whereIn('seller_id', $memberUserIds)->where('status', 'active')->count();
        }

        if ($featureKey === 'active_deal_rooms') {
            return DealRoom::where('organization_id', $org->id)
                ->whereIn('status', [DealRoom::STATUS_ACTIVE, DealRoom::STATUS_UNDER_CONTRACT])
                ->count();
        }

        // Standard usage counter table
        $counter = UsageCounter::where('organization_id', $org->id)
            ->where('feature_key', $featureKey)
            ->where('period_key', $period)
            ->first();

        return $counter ? $counter->count : 0;
    }

    /**
     * Calculate remaining quota for a limit feature (-1 = unlimited).
     */
    public function remaining(Organization $org, string $featureKey, ?string $periodKey = null): int
    {
        $limit = $this->limit($org, $featureKey);
        if ($limit === -1) {
            return -1; // Unlimited
        }

        $used = $this->used($org, $featureKey, $periodKey);
        return max(0, $limit - $used);
    }

    /**
     * Atomically consume quota for an organization.
     * Race-safe and thread-safe using database locks.
     */
    public function consume(Organization $org, string $featureKey, int $amount = 1, ?string $periodKey = null): bool
    {
        $limit = $this->limit($org, $featureKey);
        if ($limit === 0) {
            return false;
        }

        $period = $periodKey ?? $this->defaultPeriodKeyFor($featureKey);

        return DB::transaction(function () use ($org, $featureKey, $limit, $amount, $period) {
            if ($featureKey === 'active_lots' || $featureKey === 'seats' || $featureKey === 'active_deal_rooms') {
                // Live state bounded features
                $currentUsed = $this->used($org, $featureKey, $period);
                if ($limit !== -1 && ($currentUsed + $amount) > $limit) {
                    return false;
                }
                return true;
            }

            // Periodic usage counters with atomic increment
            $counter = UsageCounter::where('organization_id', $org->id)
                ->where('feature_key', $featureKey)
                ->where('period_key', $period)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = UsageCounter::create([
                    'organization_id' => $org->id,
                    'feature_key' => $featureKey,
                    'period_key' => $period,
                    'count' => 0,
                ]);
            }

            if ($limit !== -1 && ($counter->count + $amount) > $limit) {
                return false;
            }

            $counter->increment('count', $amount);
            $this->invalidate($org);

            return true;
        });
    }

    /**
     * Release quota (e.g. on lot archiving, cancellation, or rejection).
     */
    public function release(Organization $org, string $featureKey, int $amount = 1, ?string $periodKey = null): bool
    {
        $period = $periodKey ?? $this->defaultPeriodKeyFor($featureKey);

        return DB::transaction(function () use ($org, $featureKey, $amount, $period) {
            $counter = UsageCounter::where('organization_id', $org->id)
                ->where('feature_key', $featureKey)
                ->where('period_key', $period)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                return true;
            }

            $newCount = max(0, $counter->count - $amount);
            $counter->update(['count' => $newCount]);
            $this->invalidate($org);

            return true;
        });
    }

    /**
     * Generate an entitlement snapshot for an organization (cached with short TTL).
     */
    public function snapshot(Organization $org): array
    {
        $cacheKey = self::CACHE_PREFIX . $org->id;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($org) {
            $subscription = $org->activeSubscription()->first() ?? $org->activeSubscription;
            $plan = $subscription?->plan ?? Plan::where('key', Plan::KEY_STARTER)->first();

            $features = Feature::all();
            $featureSnapshots = [];
            $entitlementsMap = [];

            foreach ($features as $f) {
                $limit = $this->limit($org, $f->key);
                $used = $this->used($org, $f->key);
                $remaining = $limit === -1 ? -1 : max(0, $limit - $used);
                $can = $this->can($org, $f->key);
                $rawValue = $this->resolveRawValue($org, $f->key);

                $featureSnapshots[$f->key] = [
                    'key' => $f->key,
                    'label' => $f->label,
                    'type' => $f->type,
                    'unit' => $f->unit,
                    'is_available' => (bool) $f->is_available,
                    'raw_value' => $rawValue,
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => $remaining,
                    'unlimited' => $limit === -1,
                    'allowed' => $can,
                ];
                $entitlementsMap[$f->key] = $rawValue;
            }

            $overrides = $org->entitlementOverrides()
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->get()
                ->keyBy('feature_key')
                ->map(fn($o) => [
                    'value' => $o->value,
                    'reason' => $o->reason,
                    'expires_at' => $o->expires_at?->toIso8601String(),
                ])
                ->toArray();

            return [
                'organization' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'status' => $org->status,
                ],
                'plan' => [
                    'id' => $plan?->id,
                    'key' => $plan?->key ?? Plan::KEY_STARTER,
                    'name' => $plan?->name ?? 'Starter (Free)',
                    'description' => $plan?->description ?? '',
                ],
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'plan_key' => $plan->key,
                    'plan_name' => $plan->name,
                    'status' => $subscription->status,
                    'billing_period' => $subscription->billing_period,
                    'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                    'grace_until' => $subscription->grace_until?->toIso8601String(),
                    'cancel_at_period_end' => $subscription->cancel_at_period_end,
                ] : [
                    'id' => null,
                    'plan_key' => Plan::KEY_STARTER,
                    'plan_name' => 'Starter (Free)',
                    'status' => 'active',
                    'billing_period' => 'monthly',
                    'current_period_end' => null,
                    'grace_until' => null,
                    'cancel_at_period_end' => false,
                ],
                'entitlements' => $entitlementsMap,
                'usage' => $featureSnapshots,
                'overrides' => $overrides,
                'features' => $featureSnapshots,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Invalidate cache for an organization.
     */
    public function invalidate(Organization $org): void
    {
        Cache::forget(self::CACHE_PREFIX . $org->id);
    }

    /**
     * Resolve raw feature value in strict hierarchy:
     * 1. Active unexpired Organization Override
     * 2. Active Subscription's Plan Entitlement
     * 3. Fallback Starter Plan Entitlement
     * 4. Default Deny (null)
     */
    public function resolveRawValue(Organization $org, string $featureKey): ?string
    {
        // 1. Check organization override
        $override = OrganizationEntitlementOverride::where('organization_id', $org->id)
            ->where('feature_key', $featureKey)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($override) {
            return $override->value;
        }

        // 2. Check active subscription plan
        $subscription = $org->activeSubscription()->first() ?? $org->activeSubscription;
        if ($subscription && $subscription->isValid()) {
            $entitlement = PlanEntitlement::where('plan_id', $subscription->plan_id)
                ->where('feature_key', $featureKey)
                ->first();

            if ($entitlement) {
                return $entitlement->value;
            }
        }

        // 3. Fallback to Starter plan (even on past_due / lapsed grace)
        $starterPlan = Plan::where('key', Plan::KEY_STARTER)->first();
        if ($starterPlan) {
            $starterEntitlement = PlanEntitlement::where('plan_id', $starterPlan->id)
                ->where('feature_key', $featureKey)
                ->first();

            if ($starterEntitlement) {
                return $starterEntitlement->value;
            }
        }

        return null;
    }

    /**
     * Standard structured error array when a limit is reached.
     */
    public function limitReachedResponse(Organization $org, string $featureKey): array
    {
        $limit = $this->limit($org, $featureKey);
        $used = $this->used($org, $featureKey);
        $feature = Feature::where('key', $featureKey)->first();

        return [
            'code' => 'limit_reached',
            'feature' => $featureKey,
            'feature_label' => $feature?->label ?? $featureKey,
            'limit' => $limit,
            'used' => $used,
            'upgrade_url' => route('marketplace.pricing'),
            'message' => "You have reached your {$feature?->label} limit ({$used} of {$limit}). Please upgrade your plan to increase this limit.",
        ];
    }

    /**
     * Execute downgrade reconciliation rules:
     * - Open deals continue (never locked or cancelled).
     * - Trade records and uploaded documents are preserved.
     * - Excess lots become inactive (marked draft).
     * - Excess team seats become suspended.
     */
    public function reconcileDowngrade(Organization $org, Plan $newPlan): array
    {
        $actions = [];

        // 1. Reconcile Active Lots
        $lotEntitlement = PlanEntitlement::where('plan_id', $newPlan->id)->where('feature_key', 'active_lots')->first();
        $newLotLimit = (int) ($lotEntitlement?->value ?? 3);

        if ($newLotLimit !== -1) {
            $memberUserIds = $org->memberships()->pluck('user_id');
            $activeLots = CoffeeListing::whereIn('seller_id', $memberUserIds)
                ->where('status', 'active')
                ->latest('updated_at')
                ->get();

            if ($activeLots->count() > $newLotLimit) {
                // Deactivate the oldest lots over the limit to keep the most recent
                $lotsToDeactivate = $activeLots->slice($newLotLimit);
                foreach ($lotsToDeactivate as $lot) {
                    $lot->update(['status' => 'draft']);
                    $actions[] = "Deactivated lot #{$lot->id} ({$lot->title}) to match new limit of {$newLotLimit}.";
                }
            }
        }

        // 2. Reconcile Team Seats
        $seatEntitlement = PlanEntitlement::where('plan_id', $newPlan->id)->where('feature_key', 'seats')->first();
        $newSeatLimit = (int) ($seatEntitlement?->value ?? 2);

        if ($newSeatLimit !== -1) {
            $activeMemberships = $org->memberships()
                ->where('is_active', true)
                ->where('role', '!=', 'owner') // Never suspend the owner
                ->latest('joined_at')
                ->get();

            $totalActive = $org->memberships()->where('is_active', true)->count();
            if ($totalActive > $newSeatLimit) {
                $excessCount = $totalActive - $newSeatLimit;
                $membersToSuspend = $activeMemberships->take($excessCount);
                foreach ($membersToSuspend as $membership) {
                    $membership->update(['is_active' => false]);
                    $actions[] = "Suspended membership for user #{$membership->user_id} ({$membership->role}) to match seat limit of {$newSeatLimit}.";
                }
            }
        }

        // Invalidate cache
        $this->invalidate($org);

        Log::info("Downgrade reconciliation executed for org #{$org->id} to plan {$newPlan->key}", $actions);

        return $actions;
    }

    protected function defaultPeriodKeyFor(string $featureKey): string
    {
        if (str_contains($featureKey, '_per_month')) {
            return now()->format('Y-m');
        }

        return 'forever';
    }
}
