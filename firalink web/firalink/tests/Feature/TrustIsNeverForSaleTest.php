<?php

namespace Tests\Feature;

use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\Feature;
use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingService;
use App\Services\EntitlementService;
use Tests\TestCase;

class TrustIsNeverForSaleTest extends TestCase
{
    /**
     * FORBIDDEN TRUST KEYS:
     * Hard constraint: No commercial plan, price, or entitlement may influence
     * verification outcome, verification speed, lot evidence, search ranking,
     * or trade record deletion.
     */
    protected const FORBIDDEN_KEYS = [
        'verification_tier',
        'verification_status',
        'verification_speed',
        'verification_approval',
        'verified_badge',
        'verified_badge_included',
        'evidence_level',
        'search_ranking_boost',
        'search_ranking',
        'remove_reports',
        'remove_trade_records',
        'delete_reports',
        'delete_trade_records',
        'bypass_verification',
    ];

    /**
     * Test that no registered feature or plan entitlement key touches forbidden trust mechanics.
     */
    public function test_no_plan_or_feature_entitlement_touches_forbidden_trust_keys(): void
    {
        // 1. Check all registered features
        $featureKeys = Feature::pluck('key')->toArray();
        foreach ($featureKeys as $key) {
            foreach (self::FORBIDDEN_KEYS as $forbidden) {
                $this->assertStringNotContainsString(
                    $forbidden,
                    strtolower($key),
                    "CRITICAL VIOLATION: Feature '{$key}' touches forbidden trust key '{$forbidden}'."
                );
            }
        }

        // 2. Check all plan entitlements across all plans
        $entitlementKeys = PlanEntitlement::pluck('feature_key')->unique()->toArray();
        foreach ($entitlementKeys as $key) {
            foreach (self::FORBIDDEN_KEYS as $forbidden) {
                $this->assertStringNotContainsString(
                    $forbidden,
                    strtolower($key),
                    "CRITICAL VIOLATION: Plan entitlement '{$key}' touches forbidden trust key '{$forbidden}'."
                );
            }
        }
    }

    /**
     * Test that purchasing/subscribing to a plan NEVER mutates seller verification status.
     * Even if a seller pays for Business ($99/mo), unverified stays unverified.
     */
    public function test_purchasing_plan_does_not_alter_verification_status(): void
    {
        $unique = bin2hex(random_bytes(6));
        $user = User::factory()->create([
            'email' => "unverified_{$unique}@example.com",
        ]);

        $sellerProfile = tap(MarketplaceSellerProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Unverified Coffee Trader ' . $unique,
            'company_slug' => 'unverified-coffee-' . $unique,
            'business_type' => 'exporter',
            'verification_tier' => 'none',
        ]), fn ($p) => $p->forceFill(['verification_status' => 'unverified'])->save());

        $org = Organization::create([
            'name' => $sellerProfile->company_name,
            'slug' => $sellerProfile->company_slug,
            'status' => 'active',
        ]);
        $sellerProfile->update(['organization_id' => $org->id]);
        $user->organizations()->attach($org->id, ['role' => 'owner', 'is_active' => true]);

        // Subscribe to top-tier Business plan
        $businessPlan = Plan::where('key', 'business')->firstOrFail();
        $billingService = app(BillingService::class);
        $res = $billingService->subscribe($org, $businessPlan, 'annual', 'USD', 'offline', $user);

        // Assert seller profile verification remains UNCHANGED
        $sellerProfile->refresh();
        $this->assertEquals(
            'unverified',
            $sellerProfile->verification_status,
            'CRITICAL: Commercial plan purchase must NEVER alter verification status.'
        );
        $this->assertTrue(
            empty($sellerProfile->verification_tier) || $sellerProfile->verification_tier === 'none',
            'CRITICAL: Commercial plan purchase must NEVER grant verification tiers.'
        );
    }

    /**
     * Test that subscription downgrade/lapse NEVER deletes or locks open deals.
     * Live deals and contracts must always continue to fulfillment.
     */
    public function test_downgrade_or_lapse_preserves_open_deals_and_records(): void
    {
        $unique = bin2hex(random_bytes(6));
        $buyer = User::factory()->create([
            'email' => "buyer_{$unique}@example.com",
        ]);

        $org = Organization::create([
            'name' => 'Lapse Protection Org ' . $unique,
            'slug' => 'lapse-org-' . $unique,
            'status' => 'active',
        ]);

        // Create an active deal room for this org
        $deal = DealRoom::create([
            'organization_id' => $org->id,
            'buyer_id' => $buyer->id,
            'deal_reference' => 'DEAL-' . strtoupper($unique),
            'title' => 'Export Contract 10 Containers',
            'status' => DealRoom::STATUS_UNDER_CONTRACT,
            'target_value_usd' => 150000.00,
        ]);

        // Reconcile downgrade to Starter plan
        $starterPlan = Plan::where('key', 'starter')->firstOrFail();
        $entitlementService = app(EntitlementService::class);
        $entitlementService->reconcileDowngrade($org, $starterPlan);

        // Verify open deal is NOT deleted and NOT locked
        $deal->refresh();
        $this->assertEquals(DealRoom::STATUS_UNDER_CONTRACT, $deal->status);
    }
}
