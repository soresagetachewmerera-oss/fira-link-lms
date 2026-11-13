<?php

namespace Tests\Feature;

use App\Models\CoffeeListing;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\MarketplaceSubscriptionPlan;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserMarketplaceSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class CoffeeMarketplaceTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    /**
     * Test public marketplace catalog web page loads.
     */
    public function test_marketplace_catalog_page_loads(): void
    {
        $response = $this->get('/marketplace');

        $response->assertStatus(200);
        $response->assertSee('Coffee Marketplace');
        $response->assertSee('Refine results');
    }

    /**
     * Test single lot detail page loads with ECTA compliance check.
     */
    public function test_marketplace_listing_detail_page_loads(): void
    {
        // Lots are visible only when the seller business holds an approved, unexpired seller verification.
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $this->approveBusiness($org, 'exporter');
        $lot = CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $org->id,
            'title' => 'Approved Seller Lot ' . uniqid(),
            'origin_region' => 'Yirgacheffe',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 10,
            'indicative_price_usd_per_lb' => 5,
            'status' => 'active',
        ])->refresh();

        $response = $this->get("/marketplace/listings/{$lot->slug}");

        $response->assertStatus(200);
        $response->assertSee($lot->title);
        $response->assertSee('Technical Export Specifications');
        $response->assertSee('Request Green Coffee Sample');
    }

    /**
     * Test RFQs tenders page loads.
     */
    public function test_marketplace_rfq_board_loads(): void
    {
        $response = $this->get('/marketplace/rfqs');

        $response->assertStatus(200);
        $response->assertSee('Active Buyer RFQs & Tenders', false);
        $response->assertSee('Post New Buyer RFQ');
    }

    /**
     * Test pricing page loads with Starter, Pro, and Enterprise tiers.
     */
    public function test_marketplace_pricing_page_loads(): void
    {
        $response = $this->get('/marketplace/pricing');

        $response->assertStatus(200);
        $response->assertSee('Unlock Direct Global Coffee Trade');
        $response->assertSee('Exporter Starter');
        $response->assertSee('Pro Exporter');
    }

    /**
     * Test REST API: GET /api/marketplace/listings
     */
    public function test_api_listings_endpoint_returns_json(): void
    {
        $response = $this->getJson('/api/marketplace/listings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'origin_region',
                        'indicative_price_usd_per_lb',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test REST API: GET /api/marketplace/filters
     */
    public function test_api_filters_endpoint_returns_taxonomies(): void
    {
        $response = $this->getJson('/api/marketplace/filters');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'origins',
                'processes',
                'grades',
                'crop_years',
            ],
        ]);
    }

    /**
     * Test negative authorization: unauthenticated request to post RFQ fails.
     */
    public function test_unauthenticated_user_cannot_create_rfq(): void
    {
        $response = $this->postJson('/api/marketplace/rfqs', [
            'title' => 'Test RFQ Without Auth',
            'volume_bags' => 100,
            'destination_country' => 'USA',
            'destination_port' => 'Miami',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test end-to-end flow: Buyer creates RFQ, Seller submits quote, Buyer accepts quote.
     */
    public function test_rfq_bidding_and_acceptance_lifecycle(): void
    {
        $buyer = User::factory()->create([
            'role' => 'student',
            'email' => 'rfq-buyer-' . uniqid() . '@example.com',
            'phone' => '+251911' . rand(100000, 999999),
            'phone_verified_at' => now(),
        ]);
        $buyerOrg = Organization::create([
            'name' => 'Buyer Roasters Ltd',
            'slug' => 'buyer-roasters-' . uniqid(),
            'type' => 'buyer',
            'country' => 'Netherlands',
        ]);
        $this->approveBusiness($buyerOrg, 'buyer');
        $buyer->organizations()->attach($buyerOrg->id, ['role' => 'owner', 'is_active' => true]);
        $buyer->update(['active_organization_id' => $buyerOrg->id]);
        $seller = User::factory()->create([
            'role' => 'instructor',
            'email' => 'rfq-seller-' . uniqid() . '@example.com',
            'phone' => '+251912' . rand(100000, 999999),
            'phone_verified_at' => now(),
        ]);

        // Give seller verified profile and pro plan
        tap(MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'company_name' => 'Test Exporter PLC',
            'business_type' => 'exporter',
        ]), fn ($p) => $p->forceFill(['verification_status' => 'verified'])->save());

        $proPlan = MarketplaceSubscriptionPlan::where('slug', 'seller_pro')->first();
        if ($proPlan) {
            UserMarketplaceSubscription::create([
                'user_id' => $seller->id,
                'plan_id' => $proPlan->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
            ]);
        }

        // 1. Buyer creates RFQ
        $rfqResponse = $this->actingAs($buyer, 'sanctum')->postJson('/api/marketplace/rfqs', [
            'title' => 'Lifecycle Test Tender: 100 Bags Washed G1',
            'target_origin_region' => 'Yirgacheffe',
            'volume_bags' => 100,
            'destination_country' => 'Netherlands',
            'destination_port' => 'Rotterdam',
            'preferred_incoterm' => 'FOB',
        ]);

        $rfqResponse->assertStatus(201);
        $rfqId = $rfqResponse->json('data.id');

        // 2. Seller submits Quote
        $quoteResponse = $this->actingAs($seller, 'sanctum')->postJson("/api/marketplace/rfqs/{$rfqId}/quote", [
            'offered_price_usd_per_lb' => 4.30,
            'quantity_bags' => 100,
            'payment_terms' => 'LC at sight',
            'validity_date' => now()->addDays(14)->toDateString(),
        ]);

        $quoteResponse->assertStatus(201);
        $quoteId = $quoteResponse->json('data.id');

        // 3. Buyer accepts Quote
        $acceptResponse = $this->actingAs($buyer, 'sanctum')->putJson("/api/marketplace/quotes/{$quoteId}/status", [
            'status' => 'accepted',
        ]);

        $acceptResponse->assertStatus(200);
        $this->assertEquals('accepted', MarketplaceQuotation::find($quoteId)->status);
        $this->assertEquals('fulfilled', MarketplaceRfq::find($rfqId)->status);
    }
}

