<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\Feature;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\Lots\LotEvidenceService;
use App\Services\PrivateDocumentStore;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class SaasEntitlementEnforcementTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    private function createSaaSPlan(string $key, array $limits): Plan
    {
        $plan = Plan::firstOrCreate(
            ['key' => $key],
            [
                'name' => ucfirst($key) . ' Plan',
                'description' => "Test {$key} plan",
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 1,
                'trial_days' => 0,
            ]
        );

        foreach ($limits as $featureKey => $val) {
            Feature::firstOrCreate(
                ['key' => $featureKey],
                [
                    'label' => ucfirst(str_replace('_', ' ', $featureKey)),
                    'type' => Feature::TYPE_LIMIT,
                    'is_available' => true,
                ]
            );

            PlanEntitlement::updateOrCreate(
                ['plan_id' => $plan->id, 'feature_key' => $featureKey],
                ['value' => (string) $val]
            );
        }

        return $plan;
    }

    private function assignSubscription(Organization $org, Plan $plan): Subscription
    {
        $sub = Subscription::updateOrCreate(
            ['organization_id' => $org->id],
            [
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_period' => 'monthly',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'provider' => 'chapa',
            ]
        );

        app(EntitlementService::class)->invalidate($org);
        return $sub;
    }

    public function test_seats_quota_blocks_adding_member_when_limit_reached(): void
    {
        $plan = $this->createSaaSPlan('starter', ['seats' => 2]); // owner + 1 staff member

        $owner = $this->makeUser('student');
        $org = $this->makeOrganization($owner);
        $this->assignSubscription($org, $plan);

        // Add 1st team member (total 2 seats: owner + user1) -> Should SUCCEED
        $user1 = $this->makeUser('student');
        Sanctum::actingAs($owner);

        $res1 = $this->postJson("/api/organizations/{$org->id}/members", [
            'email' => $user1->email,
            'role' => 'staff',
        ]);
        $res1->assertStatus(200);

        // Verify roster endpoint reports seat count
        $roster = $this->getJson("/api/organizations/{$org->id}/members");
        $roster->assertStatus(200)
            ->assertJsonPath('data.can_add_members', false)
            ->assertJsonPath('data.seats.limit', 2)
            ->assertJsonPath('data.seats.used', 2)
            ->assertJsonPath('data.seats.remaining', 0);

        // Add 2nd team member (would exceed limit of 2) -> Should FAIL with 403
        $user2 = $this->makeUser('student');
        $res2 = $this->postJson("/api/organizations/{$org->id}/members", [
            'email' => $user2->email,
            'role' => 'staff',
        ]);

        $res2->assertStatus(403)
            ->assertJsonPath('code', 'limit_reached')
            ->assertJsonPath('limit', 2)
            ->assertJsonPath('used', 2);
    }

    public function test_active_deal_rooms_quota_blocks_creating_deal_room_when_limit_reached(): void
    {
        $plan = $this->createSaaSPlan('starter', ['active_deal_rooms' => 1]);

        $seller = $this->makeUser('instructor');
        $sellerOrg = $this->makeOrganization($seller);
        $this->assignSubscription($sellerOrg, $plan);

        $buyer = $this->makeUser('student');
        $buyerOrg = $this->makeOrganization($buyer);

        // Create a lot for the seller
        $lot = CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $sellerOrg->id,
            'title' => 'Test Specialty Lot',
            'origin_region' => 'Yirgacheffe',
            'status' => 'active',
            'lot_code' => 'LOT-TEST-1',
            'slug' => 'lot-test-1',
            'crop_year' => '2026',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 100,
            'available_quantity_bags' => 100,
            'minimum_order_bags' => 10,
            'indicative_price_usd_per_lb' => 3.5,
        ]);

        // Deal room 1 -> Should SUCCEED
        Sanctum::actingAs($buyer);
        $buyer->update(['active_organization_id' => $buyerOrg->id]);

        $res1 = $this->postJson('/api/deal-rooms', [
            'listing_id' => $lot->id,
            'title' => 'Deal 1',
            'target_value_usd' => 15000,
        ]);
        $res1->assertStatus(201);

        // Deal room 2 -> Seller at limit (1) -> Should FAIL with 403
        $lot2 = CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $sellerOrg->id,
            'title' => 'Test Specialty Lot 2',
            'origin_region' => 'Sidama',
            'status' => 'active',
            'lot_code' => 'LOT-TEST-2',
            'slug' => 'lot-test-2',
            'crop_year' => '2026',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 100,
            'available_quantity_bags' => 100,
            'minimum_order_bags' => 10,
            'indicative_price_usd_per_lb' => 4.0,
        ]);

        $res2 = $this->postJson('/api/deal-rooms', [
            'listing_id' => $lot2->id,
            'title' => 'Deal 2',
            'target_value_usd' => 20000,
        ]);

        $res2->assertStatus(403)
            ->assertJsonPath('code', 'limit_reached');
    }

    public function test_storage_quota_blocks_evidence_upload_when_storage_exceeded(): void
    {
        Storage::fake(PrivateDocumentStore::DISK);

        $plan = $this->createSaaSPlan('starter', ['storage_gb' => 1]); // 1 GB limit

        $owner = $this->makeUser('student');
        $org = $this->makeOrganization($owner);
        $this->assignSubscription($org, $plan);

        // Pre-fill storage counter to 1 GB (1,073,741,824 bytes)
        UsageCounter::create([
            'organization_id' => $org->id,
            'feature_key' => 'storage_bytes',
            'period_key' => 'forever',
            'count' => 1024 * 1024 * 1024,
        ]);

        app(EntitlementService::class)->invalidate($org);

        $lot = CoffeeListing::create([
            'seller_id' => $owner->id,
            'organization_id' => $org->id,
            'title' => 'Storage Lot',
            'origin_region' => 'Sidama',
            'status' => 'active',
            'lot_code' => 'LOT-STOR-1',
            'slug' => 'lot-stor-1',
            'crop_year' => '2026',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 50,
            'available_quantity_bags' => 50,
            'minimum_order_bags' => 5,
            'indicative_price_usd_per_lb' => 3.0,
        ]);

        $file = UploadedFile::fake()->create('lab_report.pdf', 500, 'application/pdf');

        $service = app(LotEvidenceService::class);

        $this->expectException(\App\Services\Verification\VerificationException::class);
        $service->submitLabReport($lot, $owner, [$file], 'Addis Lab', 'REP-101', '2026-11-01');
    }

    public function test_subscription_cancel_allows_owner_and_blocks_staff(): void
    {
        $plan = $this->createSaaSPlan('growth', ['seats' => 5]);

        $owner = $this->makeUser('student');
        $org = $this->makeOrganization($owner);
        $sub = $this->assignSubscription($org, $plan);

        $staff = $this->makeUser('student');
        OrganizationMembership::create([
            'organization_id' => $org->id,
            'user_id' => $staff->id,
            'role' => 'staff',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Staff attempts to cancel -> 403 Forbidden
        Sanctum::actingAs($staff);
        $staff->update(['active_organization_id' => $org->id]);

        $resStaff = $this->postJson('/api/marketplace/subscribe/cancel');
        $resStaff->assertStatus(403)
            ->assertJsonPath('code', 'OWNER_ONLY');

        // Owner cancels -> 200 OK
        Sanctum::actingAs($owner);
        $owner->update(['active_organization_id' => $org->id]);

        $resOwner = $this->postJson('/api/marketplace/subscribe/cancel');
        $resOwner->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cancel_at_period_end', true);

        $this->assertTrue($sub->fresh()->cancel_at_period_end);
    }

    public function test_audit_log_export_enforces_saas_entitlement(): void
    {
        $plan = $this->createSaaSPlan('starter', ['audit_log_export' => 0]);
        $owner = $this->makeUser('student');
        $org = $this->makeOrganization($owner);
        $this->assignSubscription($org, $plan);

        Sanctum::actingAs($owner);

        // 1. Viewing logs works for owner
        $resIndex = $this->getJson("/api/organizations/{$org->id}/audit-logs");
        $resIndex->assertStatus(200)
            ->assertJsonPath('success', true);

        // 2. Export on Starter returns 403 limit_reached
        $resExportBlocked = $this->getJson("/api/organizations/{$org->id}/audit-logs/export");
        $resExportBlocked->assertStatus(403)
            ->assertJsonPath('code', 'limit_reached')
            ->assertJsonPath('feature', 'audit_log_export');

        // 3. Grant entitlement via plan
        $proPlan = $this->createSaaSPlan('business', ['audit_log_export' => 1]);
        $this->assignSubscription($org, $proPlan);

        $resExportAllowed = $this->get("/api/organizations/{$org->id}/audit-logs/export");
        $resExportAllowed->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_developer_api_token_enforces_saas_entitlement(): void
    {
        $plan = $this->createSaaSPlan('starter', ['api_access' => 0]);
        $owner = $this->makeUser('student');
        $org = $this->makeOrganization($owner);
        $this->assignSubscription($org, $plan);

        Sanctum::actingAs($owner);

        // 1. Generation on Starter returns 403 limit_reached
        $resBlocked = $this->postJson("/api/organizations/{$org->id}/api-tokens", [
            'name' => 'ERP Integration',
        ]);
        $resBlocked->assertStatus(403)
            ->assertJsonPath('code', 'limit_reached')
            ->assertJsonPath('feature', 'api_access');

        // 2. Grant api_access entitlement
        $bizPlan = $this->createSaaSPlan('business', ['api_access' => 1]);
        $this->assignSubscription($org, $bizPlan);

        $resAllowed = $this->postJson("/api/organizations/{$org->id}/api-tokens", [
            'name' => 'ERP Integration',
        ]);
        $resAllowed->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'token']]);

        $tokenId = $resAllowed->json('data.id');

        // 3. List tokens
        $resList = $this->getJson("/api/organizations/{$org->id}/api-tokens");
        $resList->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 4. Revoke token
        $resDelete = $this->deleteJson("/api/organizations/{$org->id}/api-tokens/{$tokenId}");
        $resDelete->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_bulk_csv_lot_upload_enforces_saas_entitlement(): void
    {
        $plan = $this->createSaaSPlan('starter', ['bulk_csv_lot_upload' => 0]);
        $user = $this->makeUser('instructor');
        $org = $this->makeOrganization($user);
        $this->assignSubscription($org, $plan);

        Sanctum::actingAs($user);
        $user->update(['active_organization_id' => $org->id]);

        $csvContent = "title,origin_region,processing_method,grade,quantity_bags,price_per_kg_usd\n";
        $csvContent .= "Sidama Washed,Sidama,washed,grade_1,100,5.50\n";
        $csvContent .= "Yirgacheffe Natural,Yirgacheffe,natural,grade_1,80,6.20\n";

        $file = UploadedFile::fake()->createWithContent('lots.csv', $csvContent);

        // 1. On Starter returns 403 limit_reached
        $resBlocked = $this->postJson('/api/marketplace/seller/lots/bulk-import', [
            'file' => $file,
        ]);
        $resBlocked->assertStatus(403)
            ->assertJsonPath('code', 'limit_reached')
            ->assertJsonPath('feature', 'bulk_csv_lot_upload');

        // 2. Grant entitlement
        $bizPlan = $this->createSaaSPlan('business', ['bulk_csv_lot_upload' => 1]);
        $this->assignSubscription($org, $bizPlan);

        $file2 = UploadedFile::fake()->createWithContent('lots.csv', $csvContent);
        $resAllowed = $this->postJson('/api/marketplace/seller/lots/bulk-import', [
            'file' => $file2,
        ]);
        $resAllowed->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('imported_count', 2);

        $this->assertDatabaseHas('coffee_listings', [
            'organization_id' => $org->id,
            'origin_region' => 'Sidama',
            'status' => 'draft',
        ]);
    }
}
