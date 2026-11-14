<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\TradeUpdate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class TradeTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private string $region;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        // A region no seeded price list uses, so the ECTA checks below are deterministic.
        $this->region = 'Testregion' . Str::random(5);
    }

    /** A business of the given role, with its owner signed in to it. */
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

    private function lot(Organization $org, User $owner, array $extra = []): CoffeeListing
    {
        return CoffeeListing::create(array_merge([
            'seller_id' => $owner->id, 'organization_id' => $org->id, 'title' => 'Trade lot ' . Str::random(4),
            'origin_region' => $this->region, 'processing_method' => 'washed', 'grade' => 'grade_1',
            'total_quantity_bags' => 200, 'available_quantity_bags' => 200, 'indicative_price_usd_per_lb' => 4.5, 'status' => 'active',
        ], $extra));
    }

    private function postRfq(User $buyer, array $extra = [])
    {
        Sanctum::actingAs($buyer);

        return $this->postJson('/api/marketplace/rfqs', array_merge([
            'title' => 'Washed G1 for Hamburg',
            'origin_region' => $this->region,
            'volume_value' => 19.2,
            'volume_unit' => 'mt',
            'destination' => 'Hamburg',
            'incoterm' => 'FOB',
            'needed_by' => now()->addDays(60)->toDateString(),
            'sample_first' => true,
        ], $extra));
    }

    private function quote(User $seller, int $rfqId, int $lotId, array $extra = [])
    {
        Sanctum::actingAs($seller);

        return $this->postJson("/api/marketplace/rfqs/{$rfqId}/quote", array_merge([
            'listing_id' => $lotId,
            'price_usd_per_lb' => 4.6,
            'volume_bags' => 100,
            'valid_days' => 14,
            'ready_to_ship' => now()->addDays(10)->toDateString(),
            'note' => 'Washed, GrainPro, ready at our Addis warehouse.',
        ], $extra));
    }

    private function publishedMinimum(float $price, string $status = 'published'): void
    {
        $datasetId = DB::table('coffee_minimum_price_datasets')->insertGetId([
            'title' => 'ECTA test schedule', 'source' => 'Ethiopian Coffee & Tea Authority (ECTA)', 'file_name' => 'test.xlsx',
            'effective_date' => now()->subDays(3)->toDateString(), 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('coffee_minimum_price_records')->insert([
            'dataset_id' => $datasetId, 'coffee_type' => $this->region . ' Washed Grade 1', 'origin' => $this->region,
            'grade' => 'Grade 1', 'processing_method' => 'Fully Washed', 'minimum_price' => $price, 'price_unit' => 'USD/lb',
            'currency' => 'USD', 'effective_date' => now()->subDays(3)->toDateString(), 'status' => 'valid',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ---- Posting requests -----------------------------------------------------

    public function test_only_tier_1_buyer_or_broker_businesses_post_requests(): void
    {
        [$buyerOrg, $buyer] = $this->business('buyer');
        $res = $this->postRfq($buyer)->assertStatus(201)
            ->assertJsonPath('data.volume_unit', 'mt')
            ->assertJsonPath('data.volume_kg', 19200)
            ->assertJsonPath('data.sample_first', true)
            ->assertJsonPath('data.buyer_business.name', $buyerOrg->name);
        $this->assertSame($buyerOrg->id, (int) MarketplaceRfq::findOrFail($res->json('data.id'))->organization_id);

        [, $broker] = $this->business('broker');
        $this->postRfq($broker)->assertStatus(201);

        [, $exporter] = $this->business('exporter');
        $this->postRfq($exporter)->assertStatus(403)->assertJsonPath('code', 'BUYER_ROLE_REQUIRED');

        [, $unverified] = $this->business('buyer', false);
        $this->postRfq($unverified)->assertStatus(403)->assertJsonPath('code', 'VERIFICATION_REQUIRED');

        $this->postRfq($buyer, ['notes' => 'Call me on +251 911 22 33 44'])->assertStatus(422)->assertJsonPath('code', 'CONTACT_DETAILS');
        $this->postRfq($buyer, ['incoterm' => 'DDP'])->assertStatus(422);
    }

    // ---- Who sees requests ------------------------------------------------------

    public function test_open_requests_are_for_verified_seller_businesses_only(): void
    {
        [, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        [, $seller] = $this->business('exporter');
        [, $unverifiedSeller] = $this->business('supplier', false);
        [, $otherBuyer] = $this->business('buyer');

        Sanctum::actingAs($seller);
        $this->getJson('/api/marketplace/rfqs')->assertOk()->assertJsonFragment(['id' => $rfqId]);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}")->assertOk()->assertJsonPath('data.can_quote', true);

        Sanctum::actingAs($unverifiedSeller);
        $this->getJson('/api/marketplace/rfqs')->assertStatus(403)->assertJsonPath('code', 'VERIFIED_SELLER_REQUIRED');
        $this->getJson("/api/marketplace/rfqs/{$rfqId}")->assertStatus(404);

        Sanctum::actingAs($otherBuyer);
        $this->getJson('/api/marketplace/rfqs')->assertStatus(403);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertStatus(404);

        // The buyer sees their own requests.
        Sanctum::actingAs($buyer);
        $this->getJson('/api/marketplace/my-rfqs')->assertOk()->assertJsonPath('data.data.0.id', $rfqId);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}")->assertOk()->assertJsonPath('data.is_own', true);
    }

    // ---- Quotes -----------------------------------------------------------------

    public function test_a_quote_needs_a_published_lot_of_the_sellers_own_business(): void
    {
        [, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        [$sellerOrg, $seller] = $this->business('exporter');
        [$otherOrg, $other] = $this->business('exporter');
        $lot = $this->lot($sellerOrg, $seller);
        $draft = $this->lot($sellerOrg, $seller, ['status' => 'draft']);
        $foreign = $this->lot($otherOrg, $other);

        $this->quote($seller, $rfqId, $foreign->id)->assertStatus(422)->assertJsonPath('code', 'LOT_REQUIRED');
        $this->quote($seller, $rfqId, $draft->id)->assertStatus(422)->assertJsonPath('code', 'LOT_REQUIRED');
        $this->quote($seller, $rfqId, $lot->id, ['volume_bags' => 500])->assertStatus(422)->assertJsonPath('code', 'VOLUME_TOO_HIGH');
        $this->quote($seller, $rfqId, $lot->id, ['note' => 'WhatsApp me for a better price'])->assertStatus(422)->assertJsonPath('code', 'CONTACT_DETAILS');

        $first = $this->quote($seller, $rfqId, $lot->id)->assertStatus(201)
            ->assertJsonPath('data.lot.lot_code', $lot->lot_code)
            ->assertJsonPath('data.seller_business.name', $sellerOrg->name);
        // Sending again updates the same quote: one per business per request.
        $this->quote($seller, $rfqId, $lot->id, ['price_usd_per_lb' => 4.9])->assertStatus(201)->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertSame(1, MarketplaceQuotation::where('rfq_id', $rfqId)->where('organization_id', $sellerOrg->id)->count());
        Notification::assertSentTo(User::find($buyer->id), TradeUpdate::class);

        // A buyer cannot quote, and nobody quotes their own request.
        $this->quote($buyer, $rfqId, $lot->id)->assertStatus(403);
    }

    public function test_closed_requests_take_no_quotes(): void
    {
        [, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        $this->postJson("/api/marketplace/rfqs/{$rfqId}/close")->assertOk()->assertJsonPath('data.status', 'closed');
        [$org, $seller] = $this->business('exporter');
        $this->quote($seller, $rfqId, $this->lot($org, $seller)->id)->assertStatus(403)->assertJsonPath('code', 'REQUEST_CLOSED');
    }

    // ---- Compare and accept ---------------------------------------------------------

    public function test_compare_shows_tier_and_lot_level_and_filters(): void
    {
        [, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        [$aOrg, $a] = $this->business('exporter');
        [$bOrg, $b] = $this->business('supplier');
        $lotA = $this->lot($aOrg, $a);
        $lotA->forceFill(['evidence_level' => 'documented'])->save();
        $this->quote($a, $rfqId, $lotA->id, ['price_usd_per_lb' => 5.1])->assertStatus(201);
        $this->quote($b, $rfqId, $this->lot($bOrg, $b)->id, ['price_usd_per_lb' => 4.7])->assertStatus(201);

        Sanctum::actingAs($buyer);
        $res = $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertOk()->assertJsonCount(2, 'data.quotes');
        $this->assertSame([4.7, 5.1], array_column($res->json('data.quotes'), 'price_usd_per_lb'));
        $this->assertSame(1, $res->json('data.quotes.0.seller_business.verification_tier'));
        $this->assertSame('claimed', $res->json('data.quotes.0.lot.evidence_level'));
        $this->assertNull($res->json('data.quotes.0.reference_price'));
        $this->assertTrue($res->json('data.quotes.0.can_accept'));

        $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes?min_level=documented")->assertJsonCount(1, 'data.quotes')
            ->assertJsonPath('data.quotes.0.lot.evidence_level', 'documented');

        // A seller whose tier dropped after quoting: hidden by "verified only", and never acceptable.
        $bOrg->verificationDocuments()->update(['status' => 'rejected']);
        app(\App\Services\Verification\TierService::class)->recompute($bOrg);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertJsonCount(1, 'data.quotes');
        $all = $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes?verified_only=0")->assertJsonCount(2, 'data.quotes');
        $dropped = collect($all->json('data.quotes'))->firstWhere('seller_business.id', $bOrg->id);
        $this->assertFalse($dropped['can_accept']);
        $this->assertSame('SELLER_NOT_VERIFIED', $dropped['blocker']);
        $this->postJson('/api/marketplace/quotes/' . $dropped['id'] . '/accept')->assertStatus(422)->assertJsonPath('code', 'SELLER_NOT_VERIFIED');
    }

    public function test_quotes_below_the_ecta_minimum_are_flagged_and_cannot_be_accepted(): void
    {
        $this->publishedMinimum(4.25);
        [, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        [$org, $seller] = $this->business('exporter');
        $quoteId = $this->quote($seller, $rfqId, $this->lot($org, $seller)->id, ['price_usd_per_lb' => 4.1])->json('data.id');

        Sanctum::actingAs($buyer);
        $q = $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertOk()->json('data.quotes.0');
        $this->assertTrue($q['below_minimum']);
        $this->assertFalse($q['can_accept']);
        $this->assertSame('BELOW_MINIMUM', $q['blocker']);
        $this->assertEquals(4.25, $q['reference_price']['minimum_usd_per_lb']);
        $this->assertSame('Ethiopian Coffee & Tea Authority (ECTA)', $q['reference_price']['source']);
        $this->postJson("/api/marketplace/quotes/{$quoteId}/accept")->assertStatus(422)->assertJsonPath('code', 'BELOW_MINIMUM');

        // A price at or above the minimum can be accepted.
        $this->quote($seller, $rfqId, $this->lot($org, $seller)->id, ['price_usd_per_lb' => 4.25])->assertStatus(201);
        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/quotes/{$quoteId}/accept")->assertOk()->assertJsonPath('data.status', 'accepted');
    }

    public function test_archived_price_lists_are_not_a_reference(): void
    {
        $this->publishedMinimum(9.99, 'archived');
        [, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        [$org, $seller] = $this->business('exporter');
        $this->quote($seller, $rfqId, $this->lot($org, $seller)->id, ['price_usd_per_lb' => 4.1])->assertStatus(201);

        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertJsonPath('data.quotes.0.reference_price', null)
            ->assertJsonPath('data.quotes.0.below_minimum', false);
    }

    public function test_accepting_a_quote_closes_the_request_and_others_are_not_selected(): void
    {
        [$buyerOrg, $buyer] = $this->business('buyer');
        $rfqId = $this->postRfq($buyer)->json('data.id');
        [$aOrg, $a] = $this->business('exporter');
        [$bOrg, $b] = $this->business('exporter');
        $winner = $this->quote($a, $rfqId, $this->lot($aOrg, $a)->id)->json('data.id');
        $loser = $this->quote($b, $rfqId, $this->lot($bOrg, $b)->id)->json('data.id');

        // Staff of the buyer business and the sellers cannot decide.
        $staff = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $buyerOrg->id, 'user_id' => $staff->id, 'role' => 'staff', 'is_active' => true, 'joined_at' => now()]);
        Sanctum::actingAs($staff);
        $this->postJson("/api/marketplace/quotes/{$winner}/accept")->assertStatus(403);
        Sanctum::actingAs($a);
        $this->postJson("/api/marketplace/quotes/{$winner}/accept")->assertStatus(403);

        // Deal room only after acceptance.
        Sanctum::actingAs($buyer);
        $this->postJson('/api/deal-rooms', ['quotation_id' => $winner, 'title' => 'Deal', 'target_value_usd' => 1000])->assertStatus(422)->assertJsonPath('code', 'QUOTE_NOT_ACCEPTED');

        $this->postJson("/api/marketplace/quotes/{$winner}/accept")->assertOk();
        $this->assertSame('fulfilled', MarketplaceRfq::findOrFail($rfqId)->status);
        $this->assertSame(MarketplaceQuotation::STATUS_NOT_SELECTED, MarketplaceQuotation::findOrFail($loser)->status);
        $this->assertTrue(AuditLog::where('action', 'quote_accepted')->where('auditable_id', $winner)->exists());
        Notification::assertSentTo(User::find($b->id), TradeUpdate::class, fn ($n) => $n->event === 'quote_not_selected');
        Notification::assertSentTo(User::find($a->id), TradeUpdate::class, fn ($n) => $n->event === 'quote_accepted');

        $this->postJson("/api/marketplace/quotes/{$loser}/accept")->assertStatus(422);

        $first = $this->postJson('/api/deal-rooms', ['quotation_id' => $winner, 'title' => 'Deal', 'target_value_usd' => 1000])->assertStatus(201)->json('data.id');
        $again = $this->postJson('/api/deal-rooms', ['quotation_id' => $winner, 'title' => 'Deal', 'target_value_usd' => 1000])->assertOk()->json('data.id');
        $this->assertSame($first, $again);
        $this->assertSame($aOrg->id, (int) DealRoom::findOrFail($first)->organization_id);
    }

    // ---- Market filters ---------------------------------------------------------------

    public function test_market_filters_by_region_process_grade_level_and_tier(): void
    {
        [$orgA, $a] = $this->business('exporter');
        [$orgB, $b] = $this->business('exporter');
        $orgB = $this->approveBusiness($orgB, 'exporter', null, 2);
        $washedG1 = $this->lot($orgA, $a);
        $naturalG4 = $this->lot($orgB, $b, ['processing_method' => 'natural', 'grade' => 'grade_4']);
        $naturalG4->forceFill(['evidence_level' => 'lab_backed'])->save();

        $ids = fn (string $q) => collect($this->getJson('/api/marketplace/listings?per_page=50&regions[]=' . $this->region . $q)->assertOk()->json('data.data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$washedG1->id, $naturalG4->id], $ids(''));
        $this->assertSame([$naturalG4->id], $ids('&processes[]=natural'));
        $this->assertSame([$naturalG4->id], $ids('&grades[]=g4_plus'));
        $this->assertSame([$washedG1->id], $ids('&grades[]=grade_1'));
        $this->assertSame([$naturalG4->id], $ids('&min_level=documented'));
        $this->assertSame([$naturalG4->id], $ids('&min_tier=2'));
        $this->assertSame(2, $this->getJson('/api/marketplace/listings?regions[]=' . strtolower($this->region))->json('data.total'));
    }

    // ---- Exporters ---------------------------------------------------------------------

    public function test_exporters_are_for_signed_in_users_and_show_public_fields_only(): void
    {
        [$org, $owner] = $this->business('exporter');
        $org->forceFill(['tin_number' => '0011223344'])->save();
        $this->lot($org, $owner);
        [$hidden] = $this->business('exporter', false);

        $this->getJson('/api/marketplace/exporters')->assertStatus(401);

        Sanctum::actingAs($this->makeUser());
        $list = $this->getJson('/api/marketplace/exporters?search=' . urlencode($org->name))->assertOk();
        $this->assertSame($org->id, $list->json('data.data.0.id'));
        $this->assertSame(1, $list->json('data.data.0.active_lots'));

        $profile = $this->getJson("/api/marketplace/exporters/{$org->id}")->assertOk()
            ->assertJsonPath('data.verification_tier', 1)
            ->assertJsonPath('data.usual_reply_hours', null)
            ->assertJsonPath('data.confirmed_deals', 0)
            ->assertJsonCount(1, 'data.lots');
        foreach (['0011223344', '"email"', '"phone"', 'tin_number'] as $needle) {
            $this->assertStringNotContainsString($needle, $profile->getContent());
        }

        $this->getJson("/api/marketplace/exporters/{$hidden->id}")->assertStatus(404);
    }

    public function test_usual_reply_time_needs_three_quotes(): void
    {
        [$org, $seller] = $this->business('exporter');
        $lot = $this->lot($org, $seller);
        foreach ([2, 4, 10] as $i => $hours) {
            [, $buyer] = $this->business('buyer');
            $rfqId = $this->postRfq($buyer)->json('data.id');
            MarketplaceRfq::whereKey($rfqId)->update(['created_at' => now()->subHours($hours)]);
            $this->quote($seller, $rfqId, $lot->id)->assertStatus(201);
        }
        $this->assertSame(4, \App\Http\Controllers\Api\ExporterController::usualReplyHours($org));
    }

    public function test_prefill_from_a_lot_and_report_command(): void
    {
        [$org, $seller] = $this->business('exporter');
        $lot = $this->lot($org, $seller, ['processing_method' => 'natural', 'grade' => 'grade_2']);
        [, $buyer] = $this->business('buyer');
        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/rfqs/prefill?listing={$lot->id}")->assertOk()
            ->assertJsonPath('data.origin_region', $this->region)
            ->assertJsonPath('data.processing_method', 'natural')
            ->assertJsonPath('data.grade', 'grade_2');

        $this->artisan('marketplace:report-trade-without-business')->assertSuccessful();
    }
}
