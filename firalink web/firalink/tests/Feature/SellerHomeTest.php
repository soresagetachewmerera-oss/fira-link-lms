<?php

namespace Tests\Feature;

use App\Models\BusinessBlock;
use App\Models\CoffeeListing;
use App\Models\LotEvidence;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSample;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/** Seller home: counts and to-do list of the active seller business. */
class SellerHomeTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private function business(string $role, bool $approved = true): array
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => $role, 'created_by' => $owner->id])->save();
        if ($approved) {
            $org = $this->approveBusiness($org, $role);
        }
        $owner->forceFill(['active_organization_id' => $org->id])->save();

        return [$org->fresh(), $owner];
    }

    private function lot(Organization $org, User $owner, string $status = 'active'): CoffeeListing
    {
        return CoffeeListing::create([
            'seller_id' => $owner->id, 'organization_id' => $org->id, 'title' => 'Guji ' . Str::random(4),
            'origin_region' => 'Guji', 'processing_method' => 'natural', 'grade' => 'grade_1',
            'total_quantity_bags' => 100, 'available_quantity_bags' => 100, 'indicative_price_usd_per_lb' => 5,
            'status' => $status,
        ]);
    }

    private function rfq(Organization $buyerOrg, User $buyer, string $title, int $closesInDays = 5): MarketplaceRfq
    {
        $r = MarketplaceRfq::create(['buyer_id' => $buyer->id, 'rfq_number' => 'RFQ-H' . Str::upper(Str::random(6)), 'title' => $title, 'volume_bags' => 10, 'destination_country' => 'Germany', 'destination_port' => 'Hamburg']);
        $r->forceFill(['organization_id' => $buyerOrg->id, 'status' => 'open', 'closing_date' => now()->addDays($closesInDays)->toDateString()])->save();

        return $r;
    }

    public function test_counts_and_to_do_for_a_verified_seller(): void
    {
        [$org, $owner] = $this->business('exporter');
        [$buyerOrg, $buyer] = $this->business('buyer');
        [$blockedOrg, $blockedBuyer] = $this->business('buyer');
        $lot = $this->lot($org, $owner);
        $this->lot($org, $owner, 'draft');

        $sample = new MarketplaceSample();
        $sample->forceFill([
            'listing_id' => $lot->id, 'organization_id' => $org->id, 'buyer_organization_id' => $buyerOrg->id, 'buyer_id' => $buyer->id,
            'sample_reference' => 'SMP-' . Str::upper(Str::random(7)), 'quantity_grams' => 250, 'shipping_address' => 'Hamburg', 'status' => MarketplaceSample::STATUS_FEE_PAID,
            'ship_by' => now()->addDays(3)->toDateString(),
        ])->save();

        $wanted = $this->rfq($buyerOrg, $buyer, 'Need Guji G1', 0);
        $quoted = $this->rfq($buyerOrg, $buyer, 'Already quoted');
        MarketplaceQuotation::create([
            'rfq_id' => $quoted->id, 'seller_id' => $owner->id, 'quote_number' => 'Q-H' . Str::random(6), 'offered_price_usd_per_lb' => 5,
            'total_price_usd' => 100, 'quantity_bags' => 10, 'validity_date' => now()->addDays(7),
        ])->forceFill(['organization_id' => $org->id])->save();
        $fromBlocked = $this->rfq($blockedOrg, $blockedBuyer, 'From a blocked buyer', 0);
        (new BusinessBlock())->forceFill(['organization_id' => $org->id, 'blocked_organization_id' => $blockedOrg->id])->save();

        $ev = LotEvidence::create(['listing_id' => $lot->id, 'organization_id' => $org->id, 'kind' => 'lab_report', 'submitted_by' => $owner->id, 'submitted_at' => now()]);
        $ev->forceFill(['status' => LotEvidence::STATUS_APPROVED, 'expires_at' => now()->addDays(10)])->save();

        Sanctum::actingAs($owner);
        $res = $this->getJson('/api/marketplace/seller/home')->assertOk()
            ->assertJsonPath('data.business.visible_to_buyers', true)
            ->assertJsonPath('data.counts.active_lots', 1)
            ->assertJsonPath('data.counts.samples_to_send', 1);
        $this->assertGreaterThanOrEqual(1, $res->json('data.counts.open_requests'));

        $todo = collect($res->json('data.todo'));
        $this->assertSame('ship_sample', $todo[0]['kind']);
        $this->assertSame($buyerOrg->name, $todo[0]['buyer']);
        $rfqIds = $todo->where('kind', 'answer_request')->pluck('rfq_id');
        $this->assertContains($wanted->id, $rfqIds);
        $this->assertNotContains($quoted->id, $rfqIds);
        $this->assertNotContains($fromBlocked->id, $rfqIds);
        $this->assertSame(10, $todo->firstWhere('kind', 'renew_evidence')['days']);
        $this->assertStringNotContainsString((string) $buyer->email, $res->getContent());
    }

    public function test_an_unverified_seller_sees_no_requests_and_hidden_lots(): void
    {
        [$org, $owner] = $this->business('exporter', false);
        [$buyerOrg, $buyer] = $this->business('buyer');
        $this->rfq($buyerOrg, $buyer, 'Need Guji G1');

        Sanctum::actingAs($owner);
        $this->getJson('/api/marketplace/seller/home')->assertOk()
            ->assertJsonPath('data.business.visible_to_buyers', false)
            ->assertJsonPath('data.counts.open_requests', 0)
            ->assertJsonPath('data.todo', []);
    }

    public function test_buyers_and_people_without_a_business_get_no_seller_home(): void
    {
        [, $buyer] = $this->business('buyer');
        Sanctum::actingAs($buyer);
        $this->getJson('/api/marketplace/seller/home')->assertStatus(403)->assertJsonPath('code', 'SELLERS_ONLY');

        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/marketplace/seller/home')->assertStatus(404);
    }
}
