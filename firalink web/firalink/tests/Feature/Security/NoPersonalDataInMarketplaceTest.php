<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeListing;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Lots, requests and quotes show the other party as a business only. No endpoint may send a
 * person's email, phone, balance or the seller profile's TIN, contact details or document paths.
 */
class NoPersonalDataInMarketplaceTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private const SECRETS = ['leak-seller@example.test', '251911000111', '0098765432', 'licenses/secret.pdf', 'sales@leak.test', '251922000222', 'leak-buyer@example.test', '251933000333'];

    private function assertNoSecrets(string $body): void
    {
        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
        foreach (['"email"', '"phone"', '"balance"', '"tin_number"', '"license_document_path"', '"contact_phone"', '"contact_email"'] as $key) {
            $this->assertStringNotContainsString($key, $body);
        }
    }

    private function scenario(): array
    {
        $seller = $this->makeUser();
        $seller->forceFill(['email' => 'leak-seller@example.test', 'phone' => '251911000111'])->save();
        $org = $this->approveBusiness($this->makeOrganization($seller), 'exporter');
        tap(MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'organization_id' => $org->id,
            'company_name' => 'Leak Probe Exporter',
            'company_slug' => 'leak-probe-' . Str::lower(Str::random(6)),
            'business_type' => 'exporter',
        ]), fn ($p) => $p->forceFill([
            'tin_number' => '0098765432',
            'license_document_path' => 'licenses/secret.pdf',
            'contact_email' => 'sales@leak.test',
            'contact_phone' => '251922000222',
        ])->save());
        $lot = CoffeeListing::create([
            'seller_id' => $seller->id, 'organization_id' => $org->id, 'title' => 'Leak probe lot',
            'origin_region' => 'Guji', 'processing_method' => 'natural', 'grade' => 'grade_1',
            'total_quantity_bags' => 10, 'indicative_price_usd_per_lb' => 4, 'status' => 'active',
        ]);

        $buyer = $this->makeUser();
        $buyer->forceFill(['email' => 'leak-buyer@example.test', 'phone' => '251933000333'])->save();
        $this->approveBusiness($this->makeOrganization($buyer), 'buyer');
        $rfq = MarketplaceRfq::create([
            'buyer_id' => $buyer->id, 'title' => 'Leak probe RFQ', 'volume_bags' => 10,
            'destination_country' => 'Germany', 'destination_port' => 'Hamburg', 'status' => 'open',
        ]);
        MarketplaceQuotation::create([
            'rfq_id' => $rfq->id, 'listing_id' => $lot->id, 'seller_id' => $seller->id,
            'offered_price_usd_per_lb' => 4, 'total_price_usd' => 5000, 'quantity_bags' => 10,
            'validity_date' => now()->addWeek(), 'status' => 'submitted',
        ]);

        return [$seller, $buyer, $lot, $rfq];
    }

    public function test_public_lot_endpoints_never_send_the_seller_as_a_person(): void
    {
        [, , $lot] = $this->scenario();

        $this->assertNoSecrets($this->getJson('/api/marketplace/listings?per_page=100')->assertOk()->getContent());
        $this->assertNoSecrets($this->getJson('/api/marketplace/listings/' . $lot->slug)->assertOk()->getContent());
    }

    public function test_requests_need_sign_in_and_show_the_buyer_as_a_business(): void
    {
        [$seller, $buyer, , $rfq] = $this->scenario();

        $this->getJson('/api/marketplace/rfqs')->assertStatus(401);
        $this->getJson('/api/marketplace/rfqs/' . $rfq->id)->assertStatus(401);

        Sanctum::actingAs($seller);
        $body = $this->getJson('/api/marketplace/rfqs')->assertOk()->getContent();
        $this->assertNoSecrets($body);
        $res = $this->getJson('/api/marketplace/rfqs/' . $rfq->id)->assertOk();
        $this->assertNoSecrets($res->getContent());
        $this->assertSame('buyer', $res->json('data.rfq.buyer_business.business_role'));

        Sanctum::actingAs($buyer);
        $quotes = $this->getJson("/api/marketplace/rfqs/{$rfq->id}/quotes")->assertOk();
        $this->assertNoSecrets($quotes->getContent());
        $this->assertSame('exporter', $quotes->json('data.quotes.0.seller_business.business_role'));
        $this->assertSame(1, $quotes->json('data.quotes.0.seller_business.verification_tier'));
    }
}
