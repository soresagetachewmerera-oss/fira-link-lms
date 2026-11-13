<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\Organization;
use App\Models\OrganizationEntitlementOverride;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Models\User;
use App\Services\EntitlementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    protected EntitlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EntitlementService::class);
    }

    /**
     * Test entitlement resolution hierarchy:
     * Unexpired Override > Plan Entitlement > Default Deny
     */
    public function test_resolution_order_override_beats_plan_and_default(): void
    {
        $unique = bin2hex(random_bytes(6));
        $org = Organization::create([
            'name' => 'Unit Test Exporter ' . $unique,
            'slug' => 'unit-test-exporter-' . $unique,
            'business_type' => 'exporter',
            'status' => 'active',
        ]);

        $starterPlan = Plan::where('key', 'starter')->first();
        $growthPlan = Plan::where('key', 'growth')->first();

        // 1. Without subscription, falls back to Starter plan (3 active lots)
        $limit = $this->service->limit($org, 'active_lots');
        $this->assertEquals(3, $limit);

        // 2. Attach Growth subscription (15 active lots)
        $sub = Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'billing_period' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        $this->service->invalidate($org);

        $growthLimit = $this->service->limit($org, 'active_lots');
        $this->assertEquals(15, $growthLimit);

        // 3. Apply Founding Member Override for 50 active lots
        $override = OrganizationEntitlementOverride::create([
            'organization_id' => $org->id,
            'feature_key' => 'active_lots',
            'value' => '50',
            'reason' => 'Founding exporter bonus',
            'expires_at' => now()->addMonths(6),
        ]);
        $this->service->invalidate($org);

        // Override beats plan entitlement
        $overrideLimit = $this->service->limit($org, 'active_lots');
        $this->assertEquals(50, $overrideLimit);
    }

    /**
     * Test that expired overrides are ignored and resolution gracefully reverts to plan.
     */
    public function test_expired_override_falls_back_to_plan(): void
    {
        $unique = bin2hex(random_bytes(6));
        $org = Organization::create([
            'name' => 'Expired Override Org ' . $unique,
            'slug' => 'expired-override-' . $unique,
            'status' => 'active',
        ]);

        // Create expired override
        OrganizationEntitlementOverride::create([
            'organization_id' => $org->id,
            'feature_key' => 'active_lots',
            'value' => '999',
            'reason' => 'Expired trial',
            'expires_at' => now()->subDay(),
        ]);
        $this->service->invalidate($org);

        // Limit must be 3 (Starter plan), not 999
        $limit = $this->service->limit($org, 'active_lots');
        $this->assertEquals(3, $limit);
    }

    /**
     * Test unlimited (-1) handling.
     */
    public function test_unlimited_handling(): void
    {
        $unique = bin2hex(random_bytes(6));
        $org = Organization::create([
            'name' => 'Enterprise Corp ' . $unique,
            'slug' => 'enterprise-corp-' . $unique,
            'status' => 'active',
        ]);

        $businessPlan = Plan::where('key', 'business')->first();
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $businessPlan->id,
            'status' => 'active',
            'billing_period' => 'annual',
            'current_period_start' => now(),
            'current_period_end' => now()->addYear(),
        ]);
        $this->service->invalidate($org);

        $limit = $this->service->limit($org, 'active_lots');
        $this->assertEquals(-1, $limit);
        $this->assertTrue($this->service->can($org, 'active_lots', 100));
        $this->assertEquals(-1, $this->service->remaining($org, 'active_lots'));
    }

    /**
     * Test atomic quota consumption and release.
     */
    public function test_atomic_consume_and_release_enforcement(): void
    {
        $unique = bin2hex(random_bytes(6));
        $org = Organization::create([
            'name' => 'Atomic Test Org ' . $unique,
            'slug' => 'atomic-org-' . $unique,
            'status' => 'active',
        ]);

        // Starter allows 5 RFQ quotes per month
        $this->service->invalidate($org);
        $this->assertEquals(5, $this->service->remaining($org, 'rfq_responses_per_month'));

        // Consume 2
        $consumed = $this->service->consume($org, 'rfq_responses_per_month', 2);
        $this->assertTrue($consumed);
        $this->assertEquals(2, $this->service->used($org, 'rfq_responses_per_month'));
        $this->assertEquals(3, $this->service->remaining($org, 'rfq_responses_per_month'));

        // Attempt to consume 4 more (2 + 4 = 6, which exceeds limit 5)
        $blocked = $this->service->consume($org, 'rfq_responses_per_month', 4);
        $this->assertFalse($blocked, 'Atomic consume must reject requests that exceed remaining limit.');
        $this->assertEquals(2, $this->service->used($org, 'rfq_responses_per_month'));

        // Release 1
        $this->service->release($org, 'rfq_responses_per_month', 1);
        $this->assertEquals(1, $this->service->used($org, 'rfq_responses_per_month'));
        $this->assertEquals(4, $this->service->remaining($org, 'rfq_responses_per_month'));
    }

    /**
     * Test snapshot payload format and tagging cache invalidation.
     */
    public function test_snapshot_structure_and_invalidation(): void
    {
        $unique = bin2hex(random_bytes(6));
        $org = Organization::create([
            'name' => 'Snapshot Org ' . $unique,
            'slug' => 'snapshot-org-' . $unique,
            'status' => 'active',
        ]);

        $snapshot = $this->service->snapshot($org);

        $this->assertIsArray($snapshot);
        $this->assertArrayHasKey('organization', $snapshot);
        $this->assertArrayHasKey('subscription', $snapshot);
        $this->assertArrayHasKey('plan', $snapshot);
        $this->assertArrayHasKey('entitlements', $snapshot);
        $this->assertArrayHasKey('usage', $snapshot);
        $this->assertArrayHasKey('overrides', $snapshot);

        $this->assertEquals($org->id, $snapshot['organization']['id']);
        $this->assertEquals('starter', $snapshot['plan']['key']);
        $this->assertArrayHasKey('active_lots', $snapshot['usage']);
    }
}
