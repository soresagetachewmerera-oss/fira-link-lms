<?php

namespace Tests\Feature;

use App\Models\CoffeeListing;
use App\Models\Invoice;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\WebhookCall;
use App\Services\Billing\BillingService;
use App\Services\EntitlementService;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\TestCase;

class SaasBillingEnforcementTest extends TestCase
{
    use ApprovesBusinesses;

    /**
     * Test lot creation is blocked when active_lots limit reached.
     */
    public function test_lot_creation_blocked_when_limit_reached(): void
    {
        $unique = bin2hex(random_bytes(6));
        $user = User::factory()->create([
            'email' => "seller_limit_{$unique}@example.com",
        ]);

        $sellerProfile = tap(MarketplaceSellerProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Limit Exporter ' . $unique,
            'company_slug' => 'limit-exporter-' . $unique,
            'business_type' => 'exporter',
        ]), fn ($p) => $p->forceFill(['verification_status' => 'verified'])->save());

        $org = Organization::create([
            'name' => $sellerProfile->company_name,
            'slug' => $sellerProfile->company_slug,
            'status' => 'active',
        ]);
        $sellerProfile->update(['organization_id' => $org->id]);
        $user->organizations()->attach($org->id, ['role' => 'owner', 'is_active' => true]);
        $this->approveBusiness($org, 'exporter');

        // Starter tier has a limit of 3 active lots. Let's create 3 active lots for this seller.
        for ($i = 1; $i <= 3; $i++) {
            CoffeeListing::create([
                'seller_id' => $user->id,
                'title' => "Lot {$i} {$unique}",
                'slug' => "lot-{$i}-{$unique}",
                'origin_region' => 'Yirgacheffe',
                'grade' => 'Grade 1',
                'processing_method' => 'Washed',
                'indicative_price_usd_per_lb' => 4.50,
                'available_bags' => 100,
                'status' => 'active',
                'organization_id' => $org->id,
            ]);
        }

        $entitlementService = app(EntitlementService::class);
        $entitlementService->invalidate($org);

        // A 4th lot can be drafted, but publishing it is blocked by the plan's lot count.
        $user->forceFill(['active_organization_id' => $org->id])->save();
        Sanctum::actingAs($user);
        $lotId = $this->postJson('/api/marketplace/seller/lots', [
            'title' => '4th Lot Exceeding Limit',
            'origin_region' => 'Sidama',
            'crop_year' => '2025/2026',
            'grade' => 'grade_2',
            'processing_method' => 'natural',
            'total_quantity_bags' => 50,
            'minimum_order_bags' => 10,
            'indicative_price_usd_per_lb' => 4.20,
        ])->assertStatus(201)->json('data.id');

        $this->postJson("/api/marketplace/seller/lots/{$lotId}/publish")->assertStatus(403)->assertJsonPath('success', false);
        $this->assertSame('draft', CoffeeListing::findOrFail($lotId)->status);
        $this->assertEquals(3, CoffeeListing::where('organization_id', $org->id)->where('status', 'active')->count());
    }

    /**
     * Test RFQ quote response is blocked when rfq_responses_per_month limit reached.
     */
    public function test_rfq_response_blocked_when_monthly_quota_exhausted(): void
    {
        $unique = bin2hex(random_bytes(6));
        $seller = User::factory()->create([
            'email' => "rfq_seller_{$unique}@example.com",
        ]);
        $buyer = User::factory()->create([
            'email' => "rfq_buyer_{$unique}@example.com",
        ]);

        $sellerProfile = tap(MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'company_name' => 'RFQ Quota Exporter ' . $unique,
            'company_slug' => 'rfq-exporter-' . $unique,
            'business_type' => 'exporter',
        ]), fn ($p) => $p->forceFill(['verification_status' => 'verified'])->save());

        $org = Organization::create([
            'name' => $sellerProfile->company_name,
            'slug' => $sellerProfile->company_slug,
            'status' => 'active',
        ]);
        $sellerProfile->update(['organization_id' => $org->id]);
        $seller->organizations()->attach($org->id, ['role' => 'owner', 'is_active' => true]);
        $this->approveBusiness($org, 'exporter');
        $seller->forceFill(['active_organization_id' => $org->id])->save();
        $lot = CoffeeListing::create([
            'seller_id' => $seller->id, 'organization_id' => $org->id, 'title' => 'Quota lot ' . $unique,
            'origin_region' => 'Yirgacheffe', 'processing_method' => 'washed', 'grade' => 'grade_1',
            'total_quantity_bags' => 300, 'available_quantity_bags' => 300, 'indicative_price_usd_per_lb' => 4.8, 'status' => 'active',
        ]);
        $buyerOrg = Organization::create(['name' => 'Quota Buyer ' . $unique, 'slug' => 'quota-buyer-' . $unique, 'status' => 'active']);
        $buyer->organizations()->attach($buyerOrg->id, ['role' => 'owner', 'is_active' => true]);
        $this->approveBusiness($buyerOrg, 'buyer');

        // Target RFQ
        $rfq = MarketplaceRfq::create([
            'buyer_id' => $buyer->id,
            'title' => 'Urgent 5 Containers Yirgacheffe ' . $unique,
            'target_origin_region' => 'Yirgacheffe',
            'volume_bags' => 300,
            'destination_country' => 'Germany',
            'destination_port' => 'Hamburg',
            'preferred_incoterm' => 'FOB',
        ]);
        $rfq->forceFill(['buyer_id' => $buyer->id, 'organization_id' => $buyerOrg->id, 'status' => 'open', 'closing_date' => now()->addWeek()])->save();

        $entitlementService = app(EntitlementService::class);
        $entitlementService->invalidate($org);

        // Exhaust the 5 monthly RFQ responses limit on Starter
        $entitlementService->consume($org, 'rfq_responses_per_month', 5);

        // Authenticate with Sanctum for API call
        Sanctum::actingAs($seller);

        // Attempt to submit a 6th quotation
        $response = $this->postJson("/api/marketplace/rfqs/{$rfq->id}/quote", [
            'listing_id' => $lot->id,
            'price_usd_per_lb' => 4.80,
            'volume_bags' => 100,
            'valid_days' => 14,
            'ready_to_ship' => now()->addDays(21)->toDateString(),
            'note' => 'Our best FOB quotation',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'limit_reached',
            'feature' => 'rfq_responses_per_month',
        ]);
    }

    /**
     * Test IDOR Protection: Non-owner member role cannot modify billing or subscription.
     */
    public function test_non_owner_member_cannot_manage_subscription(): void
    {
        $unique = bin2hex(random_bytes(6));
        $owner = User::factory()->create([
            'email' => "owner_{$unique}@example.com",
        ]);
        $staffUser = User::factory()->create([
            'email' => "staff_{$unique}@example.com",
        ]);

        $org = Organization::create([
            'name' => 'Multi-Member Trading Corp ' . $unique,
            'slug' => 'multi-member-' . $unique,
            'status' => 'active',
        ]);

        // Owner role
        $owner->organizations()->attach($org->id, ['role' => 'owner', 'is_active' => true]);
        // Staff role
        $staffUser->organizations()->attach($org->id, ['role' => 'staff', 'is_active' => true]);

        $growthPlan = Plan::where('key', 'growth')->firstOrFail();

        // Staff attempts to upgrade subscription -> MUST BE DENIED
        $response = $this->actingAs($staffUser)->post('/marketplace/subscribe', [
            'plan_id' => $growthPlan->id,
            'period' => 'monthly',
            'currency' => 'ETB',
            'gateway' => 'offline',
        ]);

        $response->assertRedirect('/marketplace/dashboard');
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Owner role', session('error'));

        // Staff attempts to cancel subscription -> MUST BE DENIED
        $cancelResponse = $this->actingAs($staffUser)->post('/marketplace/cancel-subscription');
        $cancelResponse->assertRedirect('/marketplace/dashboard');
        $cancelResponse->assertSessionHas('error');
        $this->assertStringContainsString('Owner role', session('error'));
    }

    /**
     * Test Webhook Idempotency: Duplicate webhook deliveries do not re-credit or double-process.
     */
    public function test_webhook_idempotency_and_replay_protection(): void
    {
        $unique = bin2hex(random_bytes(6));
        $org = Organization::create([
            'name' => 'Webhook Test Org ' . $unique,
            'slug' => 'webhook-org-' . $unique,
            'status' => 'active',
        ]);

        $plan = Plan::where('key', 'growth')->firstOrFail();
        $sub = Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $plan->id,
            'status' => 'past_due',
            'billing_period' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $invoice = Invoice::create([
            'organization_id' => $org->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-TEST-' . strtoupper($unique),
            'amount' => 29.00,
            'currency' => 'USD',
            'status' => 'open',
            'provider' => 'chapa',
            'provider_ref' => 'chapa_tx_' . $unique,
        ]);

        $payload1 = [
            'tx_ref' => $invoice->provider_ref,
            'status' => 'success',
            'amount' => 29.00,
            'currency' => 'USD',
        ];

        // First delivery: processes successfully
        $res1 = $this->postJson('/billing/webhook/chapa', $payload1);
        $res1->assertStatus(200);
        $res1->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $sub->refresh();
        $this->assertEquals('active', $sub->status);

        // Second delivery (replay attack or duplicate): must be recognized as duplicate
        $res2 = $this->postJson('/billing/webhook/chapa', $payload1);
        $res2->assertStatus(200);
        $res2->assertJson(['duplicate' => true]);
    }
}
