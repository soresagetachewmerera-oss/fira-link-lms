<?php

namespace Tests\Feature\Security;

use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\CoffeeListing;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use App\Services\Auth\FreshAuth;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Slice 0, item 1 and 6: deal-room milestones and ownership.
 */
class DealRoomMilestoneSecurityTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function advanceUrl(DealRoom $deal, DealMilestone $m): string
    {
        return "/api/deal-rooms/{$deal->id}/milestones/{$m->id}/advance";
    }

    private function confirmUrl(DealRoom $deal, DealMilestone $m): string
    {
        return "/api/deal-rooms/{$deal->id}/milestones/{$m->id}/confirm";
    }

    public function test_non_member_gets_403_on_advance(): void
    {
        [$deal] = $this->makeDealRoom();
        $stranger = $this->makeUser();
        $milestone = $this->milestone($deal, 'payment_escrow');

        Sanctum::actingAs($stranger);
        $this->postJson($this->advanceUrl($deal, $milestone), ['status' => 'in_progress'])
            ->assertStatus(403);

        $this->assertSame(DealMilestone::STATUS_PENDING, $milestone->fresh()->status);
    }

    public function test_member_cannot_self_confirm_by_sending_achieved(): void
    {
        [$deal, $buyer, $seller] = $this->makeDealRoom();
        $milestone = $this->milestone($deal, 'payment_escrow');

        foreach ([$buyer, $seller] as $party) {
            Sanctum::actingAs($party);
            $this->postJson($this->advanceUrl($deal, $milestone), ['status' => 'achieved'])
                ->assertStatus(403)
                ->assertJsonPath('code', 'CONFIRMATION_REQUIRED');

            $this->postJson($this->advanceUrl($deal, $milestone), ['status' => 'waived'])
                ->assertStatus(403);
        }

        $fresh = $milestone->fresh();
        $this->assertSame(DealMilestone::STATUS_PENDING, $fresh->status);
        $this->assertNull($fresh->verified_by);
    }

    public function test_member_cannot_use_the_confirm_action(): void
    {
        [$deal, $buyer, $seller] = $this->makeDealRoom();
        $milestone = $this->milestone($deal, 'payment_escrow');

        foreach ([$buyer, $seller] as $party) {
            FreshAuth::markUser($party); // fresh, so the 403 comes from the policy
            Sanctum::actingAs($party);
            $this->postJson($this->confirmUrl($deal, $milestone), ['bank_reference' => 'FAKE-123'])
                ->assertStatus(403);
        }

        $this->assertSame(DealMilestone::STATUS_PENDING, $milestone->fresh()->status);
    }

    public function test_member_cannot_use_confirm_even_with_finance_role(): void
    {
        [$deal, $buyer] = $this->makeDealRoom();
        Role::findOrCreate('finance', 'web');
        $buyer->assignRole('finance');
        $milestone = $this->milestone($deal, 'payment_escrow');

        FreshAuth::markUser($buyer);
        Sanctum::actingAs($buyer);
        $this->postJson($this->confirmUrl($deal, $milestone), ['bank_reference' => 'SELF-1'])
            ->assertStatus(403);
    }

    public function test_bank_slip_only_moves_to_proof_submitted_and_is_stored_privately(): void
    {
        [$deal, $buyer] = $this->makeDealRoom();
        $milestone = $this->milestone($deal, 'payment_escrow');

        Sanctum::actingAs($buyer);
        $this->post($this->advanceUrl($deal, $milestone), ['document' => $this->realPng()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status', DealMilestone::STATUS_PROOF_SUBMITTED);

        $fresh = $milestone->fresh();
        $this->assertSame(DealMilestone::STATUS_PROOF_SUBMITTED, $fresh->status);
        $this->assertSame($buyer->id, $fresh->submitted_by);
        $this->assertNull($fresh->verified_by);
        Storage::disk('local')->assertExists($fresh->document_path);
        Storage::disk('public')->assertMissing($fresh->document_path);
        $this->assertStringEndsWith('.png', $fresh->document_path);
    }

    public function test_dummy_bank_slip_is_rejected(): void
    {
        [$deal, $buyer] = $this->makeDealRoom();
        $milestone = $this->milestone($deal, 'payment_escrow');
        Sanctum::actingAs($buyer);

        foreach ([$this->dummyBytes(), $this->dummyBytes('slip.pdf', 20480, '%PDF-1.4'), $this->tinyPng(), $this->emptyFile()] as $file) {
            $this->post($this->advanceUrl($deal, $milestone), ['document' => $file], ['Accept' => 'application/json'])
                ->assertStatus(422);
        }

        $this->assertNull($milestone->fresh()->document_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_admin_can_confirm_with_bank_reference(): void
    {
        [$deal] = $this->makeDealRoom();
        $admin = $this->makeUser('admin');
        $milestone = $this->milestone($deal, 'payment_escrow');

        FreshAuth::markUser($admin);
        Sanctum::actingAs($admin);
        $this->postJson($this->confirmUrl($deal, $milestone), [])->assertStatus(422);

        $this->postJson($this->confirmUrl($deal, $milestone), ['bank_reference' => 'CBE-TT-889201'])
            ->assertOk()
            ->assertJsonPath('data.status', DealMilestone::STATUS_ACHIEVED);

        $fresh = $milestone->fresh();
        $this->assertSame('CBE-TT-889201', $fresh->bank_reference);
        $this->assertSame($admin->id, $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);

        // Already confirmed: cannot be confirmed again or changed by a party.
        $this->postJson($this->confirmUrl($deal, $milestone), ['bank_reference' => 'X'])->assertStatus(422);
    }

    public function test_finance_role_can_confirm(): void
    {
        [$deal] = $this->makeDealRoom();
        $finance = $this->makeUser();
        Role::findOrCreate('finance', 'web');
        $finance->assignRole('finance');
        $milestone = $this->milestone($deal, 'payment_escrow');

        FreshAuth::markUser($finance);
        Sanctum::actingAs($finance);
        $this->postJson($this->confirmUrl($deal, $milestone), ['bank_reference' => 'AWASH-55'])
            ->assertOk();

        $this->assertSame(DealMilestone::STATUS_ACHIEVED, $milestone->fresh()->status);
    }

    public function test_plain_user_cannot_confirm(): void
    {
        [$deal] = $this->makeDealRoom();
        $milestone = $this->milestone($deal, 'payment_escrow');

        $plain = $this->makeUser();
        FreshAuth::markUser($plain);
        Sanctum::actingAs($plain);
        $this->postJson($this->confirmUrl($deal, $milestone), ['bank_reference' => 'X'])->assertStatus(403);
    }

    public function test_non_member_cannot_view_sign_dispute_or_complete(): void
    {
        [$deal] = $this->makeDealRoom();
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/deal-rooms/{$deal->id}")->assertStatus(403);
        $this->postJson("/api/deal-rooms/{$deal->id}/sign-contract")->assertStatus(403);
        $this->postJson("/api/deal-rooms/{$deal->id}/dispute", ['reason' => 'x', 'details' => 'y'])->assertStatus(403);
        $this->postJson("/api/deal-rooms/{$deal->id}/complete")->assertStatus(403);

        $fresh = $deal->fresh();
        $this->assertNull($fresh->seller_signed_at);
        $this->assertNull($fresh->buyer_signed_at);
        $this->assertSame(DealRoom::STATUS_UNDER_CONTRACT, $fresh->status);
    }

    public function test_admin_can_view_but_cannot_sign_as_a_party(): void
    {
        [$deal] = $this->makeDealRoom();
        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson("/api/deal-rooms/{$deal->id}")->assertOk();
        $this->postJson("/api/deal-rooms/{$deal->id}/sign-contract")->assertStatus(403);
        $this->assertNull($deal->fresh()->seller_signed_at);
    }

    public function test_complete_requires_confirmed_settlement(): void
    {
        [$deal, $buyer] = $this->makeDealRoom();

        Sanctum::actingAs($buyer);
        $this->postJson("/api/deal-rooms/{$deal->id}/complete")
            ->assertStatus(422)
            ->assertJsonPath('code', 'SETTLEMENT_NOT_CONFIRMED');

        $admin = $this->makeUser('admin');
        FreshAuth::markUser($admin);
        Sanctum::actingAs($admin);
        // Slice 7: steps are confirmed in order; the last one cannot jump ahead.
        $this->postJson($this->confirmUrl($deal, $this->milestone($deal, 'final_settlement')), ['bank_reference' => 'FINAL-1'])
            ->assertStatus(422)->assertJsonPath('code', 'STEP_LOCKED');
        foreach (['payment_escrow', 'preshipment_inspection', 'bill_of_lading_released', 'final_settlement'] as $i => $key) {
            $this->postJson($this->confirmUrl($deal, $this->milestone($deal, $key)), ['bank_reference' => 'BANK-' . $i])->assertOk();
        }

        Sanctum::actingAs($buyer);
        $this->postJson("/api/deal-rooms/{$deal->id}/complete")->assertOk();
        $this->assertSame(DealRoom::STATUS_COMPLETED, $deal->fresh()->status);
    }

    public function test_dispute_evidence_is_private_and_validated(): void
    {
        [$deal, , $seller] = $this->makeDealRoom();
        Sanctum::actingAs($seller);

        $this->post("/api/deal-rooms/{$deal->id}/dispute", [
            'reason' => 'Quality', 'details' => 'Bags differ from sample', 'evidence' => $this->dummyBytes(),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->post("/api/deal-rooms/{$deal->id}/dispute", [
            'reason' => 'Quality', 'details' => 'Bags differ from sample', 'evidence' => $this->realPdf(),
        ], ['Accept' => 'application/json'])->assertOk();

        $path = $deal->fresh()->escrow_proof_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_store_ignores_client_buyer_and_organization(): void
    {
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $org->id,
            'title' => 'Test lot',
            'origin_region' => 'Sidama',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 10,
            'available_quantity_bags' => 10,
            'indicative_price_usd_per_lb' => 4.5,
            'status' => 'active',
        ]);

        $buyer = $this->makeUser();
        $buyerOrg = $this->makeOrganization($buyer);
        $buyer->forceFill(['active_organization_id' => $buyerOrg->id])->save();
        $victim = $this->makeUser();
        $otherOrg = $this->makeOrganization($this->makeUser());

        Sanctum::actingAs($buyer);
        $quote = $this->acceptedQuote($listing, $buyer, $buyerOrg, 1000);
        $res = $this->postJson('/api/deal-rooms', [
            'quotation_id' => $quote->id,
            'title' => 'Deal',
            'target_value_usd' => 999999,
            'buyer_id' => $victim->id,
            'organization_id' => $otherOrg->id,
        ])->assertStatus(201);

        $deal = DealRoom::findOrFail($res->json('data.id'));
        $this->assertSame($buyer->id, $deal->buyer_id);
        $this->assertSame($org->id, $deal->organization_id);
        $this->assertSame($buyerOrg->id, (int) $deal->buyer_organization_id);
        // The value is the accepted quote's, not the request's.
        $this->assertEquals(1000, (float) $deal->target_value_usd);

        // A seller cannot open a deal room with their own business.
        Sanctum::actingAs($seller);
        $own = $this->acceptedQuote($listing, $seller, $org, 1);
        $this->postJson('/api/deal-rooms', ['quotation_id' => $own->id, 'title' => 'Self'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SELF_DEAL');
    }

    public function test_store_from_quote_requires_the_rfq_buyer(): void
    {
        $seller = $this->makeUser();
        $sellerOrg = $this->makeOrganization($seller);
        $rfqOwner = $this->makeUser();
        $rfqOrg = $this->makeOrganization($rfqOwner);
        $rfqOwner->forceFill(['active_organization_id' => $rfqOrg->id])->save();
        $lot = CoffeeListing::create([
            'seller_id' => $seller->id, 'organization_id' => $sellerOrg->id, 'title' => 'Sidama G2', 'origin_region' => 'Sidama',
            'processing_method' => 'washed', 'grade' => 'grade_2', 'total_quantity_bags' => 100, 'available_quantity_bags' => 100,
            'indicative_price_usd_per_lb' => 4.2, 'status' => 'active',
        ]);

        $rfq = MarketplaceRfq::create([
            'buyer_id' => $rfqOwner->id,
            'rfq_number' => 'RFQ-T' . Str::upper(Str::random(8)),
            'title' => 'Need Sidama G2',
            'volume_bags' => 100,
            'destination_country' => 'Germany',
            'destination_port' => 'Hamburg',
        ]);
        $quote = MarketplaceQuotation::create([
            'rfq_id' => $rfq->id,
            'listing_id' => $lot->id,
            'seller_id' => $seller->id,
            'quote_number' => 'Q-T' . Str::upper(Str::random(8)),
            'offered_price_usd_per_lb' => 4.2,
            'total_price_usd' => 10000,
            'quantity_bags' => 100,
            'validity_date' => now()->addDays(14),
        ]);

        Sanctum::actingAs($this->makeUser());
        $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Steal', 'target_value_usd' => 1])
            ->assertStatus(403);

        // Slice 5: only an accepted quote opens a deal room.
        Sanctum::actingAs($rfqOwner);
        $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Deal', 'target_value_usd' => 10000])
            ->assertStatus(422)
            ->assertJsonPath('code', 'QUOTE_NOT_ACCEPTED');
        $quote->forceFill(['status' => MarketplaceQuotation::STATUS_ACCEPTED])->save();
        // A quote without a lot has nothing to hold, so it cannot open a deal room.
        $lotless = $quote->replicate(['listing_id'])->fill(['quote_number' => 'Q-L' . Str::upper(Str::random(8))]);
        $lotless->forceFill(['status' => MarketplaceQuotation::STATUS_ACCEPTED, 'seller_id' => $seller->id])->save();
        $this->postJson('/api/deal-rooms', ['quotation_id' => $lotless->id, 'title' => 'Deal'])->assertStatus(422)->assertJsonPath('code', 'LOT_REQUIRED');

        $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Deal', 'target_value_usd' => 10000])
            ->assertStatus(201)
            ->assertJsonPath('data.my_side', 'buyer')
            ->assertJsonPath('data.buyer.id', $rfqOrg->id)
            ->assertJsonMissingPath('data.buyer_id');
    }
}
