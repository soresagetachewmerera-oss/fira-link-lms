<?php

namespace Tests\Feature;

use App\Models\CoffeeListing;
use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\MarketplaceQuotation;
use App\Models\StockReservation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\MarketplaceService;
use App\Services\PrivateDocumentStore;
use App\Services\Trade\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Release 1 repairs from docs/audits/coffee-b2b-audit-2026-11-25.md (D1 to D13).
 * Each test reproduces the audited failure and asserts the corrected behavior.
 */
class TradeRepairsTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake(PrivateDocumentStore::DISK);
    }

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
            'seller_id' => $owner->id, 'organization_id' => $org->id, 'title' => 'Repair lot ' . Str::random(4),
            'origin_region' => 'Rx' . Str::random(6), 'processing_method' => 'washed', 'grade' => 'grade_1', 'crop_year' => '2025/2026',
            'total_quantity_bags' => 100, 'available_quantity_bags' => 100, 'minimum_order_bags' => 10,
            'indicative_price_usd_per_lb' => 4.5, 'status' => 'active',
        ], $extra));
    }

    private function member(Organization $org, string $role): User
    {
        $u = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $u->id, 'role' => $role, 'is_active' => true, 'joined_at' => now()]);
        $u->forceFill(['active_organization_id' => $org->id])->save();

        return $u;
    }

    /** [deal id, buyer, seller, buyerOrg, sellerOrg] */
    private function openDeal(float $total = 60847.58): array
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [$buyerOrg, $buyer] = $this->business('buyer');
        $quote = $this->acceptedQuote($this->lot($sellerOrg, $seller), $buyer, $buyerOrg, $total, 'FOB');
        Sanctum::actingAs($buyer);
        $id = $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Repair deal'])->assertStatus(201)->json('data.id');

        return [$id, $buyer, $seller, $buyerOrg, $sellerOrg];
    }

    // ---- D2, D4, D11: opening a deal ------------------------------------------

    public function test_deal_terms_come_from_the_accepted_quote_not_the_request(): void
    {
        config(['deals.deposit_percent' => 50]);
        [$sellerOrg, $seller] = $this->business('exporter');
        [$buyerOrg, $buyer] = $this->business('buyer');
        $quote = $this->acceptedQuote($this->lot($sellerOrg, $seller), $buyer, $buyerOrg, 60847.58, 'FOB');

        Sanctum::actingAs($buyer);
        $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Deal', 'target_value_usd' => 1, 'incoterm' => 'DDP Nowhere'])
            ->assertStatus(201)
            ->assertJsonPath('data.target_value_usd', 60847.58)
            ->assertJsonPath('data.incoterm', 'FOB')
            ->assertJsonPath('data.deposit_percent', 50)
            ->assertJsonPath('data.deposit_usd', 30423.79);
    }

    public function test_a_deal_cannot_be_opened_from_a_listing_without_an_accepted_quote(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        $draft = $this->lot($sellerOrg, $seller, ['status' => 'draft']);
        [, $unverifiedBuyer] = $this->business('buyer', false);

        Sanctum::actingAs($unverifiedBuyer);
        $this->postJson('/api/deal-rooms', ['listing_id' => $draft->id, 'title' => 'Deal', 'target_value_usd' => 5])
            ->assertStatus(422)->assertJsonPath('code', 'QUOTE_REQUIRED');
        $this->assertSame(0, DealRoom::where('listing_id', $draft->id)->count());
    }

    // ---- D3, D6: signing -------------------------------------------------------

    public function test_staff_cannot_sign_and_managers_can(): void
    {
        [$id, , , , $sellerOrg] = $this->openDeal();

        Sanctum::actingAs($this->member($sellerOrg, 'staff'));
        $this->postJson("/api/deal-rooms/{$id}/sign-contract")->assertStatus(403)->assertJsonPath('code', 'SIGN_NOT_ALLOWED');
        $this->assertNull(DealRoom::findOrFail($id)->seller_signed_at);

        Sanctum::actingAs($this->member($sellerOrg, 'manager'));
        $this->postJson("/api/deal-rooms/{$id}/sign-contract")->assertOk();
        $this->assertNotNull(DealRoom::findOrFail($id)->seller_signed_at);
    }

    public function test_the_contract_file_cannot_be_replaced_after_a_signature(): void
    {
        [$id, $buyer, $seller] = $this->openDeal();

        Sanctum::actingAs($seller);
        $this->post("/api/deal-rooms/{$id}/sign-contract", ['contract_document' => $this->realPdf()], ['Accept' => 'application/json'])->assertOk();
        $path = DealRoom::findOrFail($id)->contract_document_path;
        $this->assertNotNull($path);

        Sanctum::actingAs($buyer);
        $this->post("/api/deal-rooms/{$id}/sign-contract", ['contract_document' => $this->realPdf('other.pdf')], ['Accept' => 'application/json'])
            ->assertStatus(409)->assertJsonPath('code', 'CONTRACT_LOCKED');
        $deal = DealRoom::findOrFail($id);
        $this->assertSame($path, $deal->contract_document_path);
        $this->assertNull($deal->buyer_signed_at);

        // Signing the file that is there still works.
        $this->postJson("/api/deal-rooms/{$id}/sign-contract")->assertOk();
        $this->assertSame(DealRoom::STATUS_UNDER_CONTRACT, DealRoom::findOrFail($id)->status);
    }

    // ---- D7: disputes ----------------------------------------------------------

    public function test_a_dispute_freezes_steps_and_closed_deals_cannot_be_disputed(): void
    {
        [$deal, $buyer] = $this->makeDealRoom();
        Sanctum::actingAs($buyer);
        $this->postJson("/api/deal-rooms/{$deal->id}/dispute", ['reason' => 'quality', 'details' => 'Moisture too high'])->assertOk();
        $this->postJson("/api/deal-rooms/{$deal->id}/dispute", ['reason' => 'again', 'details' => 'x'])
            ->assertStatus(409)->assertJsonPath('code', 'DEAL_DISPUTED');

        $step = $this->milestone($deal, 'payment_escrow');
        $this->post("/api/deal-rooms/{$deal->id}/milestones/{$step->id}/advance", ['document' => $this->realPng()], ['Accept' => 'application/json'])
            ->assertStatus(423)->assertJsonPath('code', 'DEAL_DISPUTED');
        $this->assertSame('pending', $step->fresh()->status);

        [$closed, $buyer2] = $this->makeDealRoom();
        $closed->forceFill(['status' => DealRoom::STATUS_COMPLETED])->save();
        Sanctum::actingAs($buyer2);
        $this->postJson("/api/deal-rooms/{$closed->id}/dispute", ['reason' => 'late', 'details' => 'x'])
            ->assertStatus(409)->assertJsonPath('code', 'DEAL_NOT_OPEN');
        $this->assertSame(DealRoom::STATUS_COMPLETED, $closed->fresh()->status);
    }

    // ---- D8: editing a lot -----------------------------------------------------

    public function test_editing_a_lot_keeps_bags_that_are_already_taken(): void
    {
        [$org, $owner] = $this->business('exporter');
        $lot = $this->lot($org, $owner, ['available_quantity_bags' => 20]);
        Sanctum::actingAs($owner);

        $this->putJson("/api/marketplace/seller/lots/{$lot->id}", ['title' => 'Renamed', 'total_quantity_bags' => 100])->assertOk();
        $this->assertSame(20, $lot->fresh()->available_quantity_bags);

        $this->putJson("/api/marketplace/seller/lots/{$lot->id}", ['total_quantity_bags' => 120])->assertOk();
        $this->assertSame(40, $lot->fresh()->available_quantity_bags);

        $this->putJson("/api/marketplace/seller/lots/{$lot->id}", ['total_quantity_bags' => 50])->assertOk();
        $this->assertSame(0, $lot->fresh()->available_quantity_bags);
    }

    // ---- D9: CSV import --------------------------------------------------------

    public function test_csv_import_saves_quantities_and_prices_and_reports_bad_rows(): void
    {
        [$org, $owner] = $this->business('exporter');
        $this->mock(EntitlementService::class, fn ($m) => $m->shouldReceive('can')->andReturn(true));
        Sanctum::actingAs($owner);

        $csv = "\xEF\xBB\xBFtitle,origin_region,processing_method,grade,quantity_bags,price_per_kg_usd\n"
            . "Guji natural,Guji,natural,grade_1,320,7.50\n"
            . "Typo lot,Sidama,wahsed,grade_9,10,5\n";
        $res = $this->post('/api/marketplace/seller/lots/bulk-import', ['file' => UploadedFile::fake()->createWithContent('lots.csv', $csv)], ['Accept' => 'application/json'])
            ->assertStatus(201)->assertJsonPath('imported_count', 1);
        $this->assertCount(1, $res->json('errors'));
        $this->assertStringContainsString('Row #3', $res->json('errors.0'));

        $lot = CoffeeListing::findOrFail($res->json('imported_ids.0'));
        $this->assertSame('draft', $lot->status);
        $this->assertSame(320, $lot->total_quantity_bags);
        $this->assertSame(320, $lot->available_quantity_bags);
        $this->assertEquals(3.40, (float) $lot->indicative_price_usd_per_lb); // 7.50 USD/kg
        $this->assertSame('natural', $lot->processing_method);
        $this->assertDatabaseMissing('coffee_listings', ['organization_id' => $org->id, 'title' => 'Typo lot']);
    }

    // ---- D12: web price check --------------------------------------------------

    public function test_the_lot_page_benchmark_uses_only_published_price_lists(): void
    {
        [$org, $owner] = $this->business('exporter');
        $lot = $this->lot($org, $owner);
        $insert = function (string $status, float $price) use ($lot) {
            $id = DB::table('coffee_minimum_price_datasets')->insertGetId([
                'title' => 'List ' . $status, 'source' => 'ECTA', 'file_name' => 'x.xlsx', 'effective_date' => now()->subDay()->toDateString(),
                'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('coffee_minimum_price_records')->insert([
                'dataset_id' => $id, 'coffee_type' => 'x', 'origin' => $lot->origin_region, 'grade' => 'Grade 1', 'processing_method' => 'Fully Washed',
                'minimum_price' => $price, 'price_unit' => 'USD/lb', 'currency' => 'USD', 'effective_date' => now()->subDay()->toDateString(),
                'status' => 'valid', 'created_at' => now(), 'updated_at' => now(),
            ]);
        };

        $insert('draft', 3.0);
        $this->assertNull(app(MarketplaceService::class)->getOfficialBenchmarkPrice($lot));

        $insert('published', 5.0);
        $benchmark = app(MarketplaceService::class)->getOfficialBenchmarkPrice($lot);
        $this->assertEquals(5.0, $benchmark['benchmark_price_usd_lb']);
        $this->assertSame('below_regulatory_minimum', $benchmark['status']);
    }

    // ---- D13: admin activation -------------------------------------------------

    public function test_admin_cannot_activate_a_lot_of_an_unverified_business(): void
    {
        [$org, $owner] = $this->business('exporter', false);
        $lot = $this->lot($org, $owner, ['status' => 'archived']);
        $admin = $this->makeUser('admin');

        $this->actingAsStaff($admin)->post(route('admin.marketplace.listings.toggle-status', $lot->id))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame('archived', $lot->fresh()->status);

        [$okOrg, $okOwner] = $this->business('exporter');
        $okLot = $this->lot($okOrg, $okOwner, ['status' => 'archived']);
        $this->actingAsStaff($admin)->post(route('admin.marketplace.listings.toggle-status', $okLot->id))->assertSessionHas('success');
        $this->assertSame('active', $okLot->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $okLot->id, 'action' => 'lot_reactivated_by_admin']);
    }

    // ---- D1: stock held on acceptance ------------------------------------------

    /** An open request of a fresh buyer business and a quote on it, through the API. */
    private function quoteFor(User $seller, CoffeeListing $lot, int $bags): array
    {
        [$buyerOrg, $buyer] = $this->business('buyer');
        Sanctum::actingAs($buyer);
        $rfqId = $this->postJson('/api/marketplace/rfqs', ['title' => 'Need coffee', 'volume_value' => 6, 'volume_unit' => 'mt', 'destination' => 'Hamburg', 'incoterm' => 'FOB'])
            ->assertStatus(201)->json('data.id');
        Sanctum::actingAs($seller);
        $quoteId = $this->postJson("/api/marketplace/rfqs/{$rfqId}/quote", ['listing_id' => $lot->id, 'price_usd_per_lb' => 4.6, 'volume_bags' => $bags,
            'valid_days' => 14, 'ready_to_ship' => now()->addDays(9)->toDateString()])->assertStatus(201)->json('data.id');

        return [$quoteId, $buyer, $buyerOrg];
    }

    public function test_the_same_bags_cannot_be_sold_twice(): void
    {
        [$org, $seller] = $this->business('exporter');
        $lot = $this->lot($org, $seller);
        [$first, $buyer1] = $this->quoteFor($seller, $lot, 100);
        [$second, $buyer2] = $this->quoteFor($seller, $lot, 100);

        Sanctum::actingAs($buyer1);
        $this->postJson("/api/marketplace/quotes/{$first}/accept")->assertOk();
        $lot->refresh();
        $this->assertSame(0, $lot->available_quantity_bags);
        $this->assertSame('sold_out', $lot->status);
        $this->assertSame(StockReservation::STATUS_HELD, StockReservation::where('quotation_id', $first)->value('status'));

        Sanctum::actingAs($buyer2);
        $this->postJson("/api/marketplace/quotes/{$second}/accept")->assertStatus(422)->assertJsonPath('code', 'STOCK_UNAVAILABLE');
        $this->assertSame(MarketplaceQuotation::STATUS_SUBMITTED, MarketplaceQuotation::findOrFail($second)->status);
        $this->assertSame(1, StockReservation::where('listing_id', $lot->id)->count());
    }

    public function test_a_quote_must_meet_the_minimum_order_except_for_the_last_bags(): void
    {
        [$org, $seller] = $this->business('exporter');
        $lot = $this->lot($org, $seller, ['minimum_order_bags' => 50]);
        [, $buyer] = $this->business('buyer');
        Sanctum::actingAs($buyer);
        $rfqId = $this->postJson('/api/marketplace/rfqs', ['title' => 'x', 'volume_value' => 1, 'volume_unit' => 'mt', 'destination' => 'Hamburg', 'incoterm' => 'FOB'])->json('data.id');
        Sanctum::actingAs($seller);
        $body = ['listing_id' => $lot->id, 'price_usd_per_lb' => 4.6, 'valid_days' => 14, 'ready_to_ship' => now()->addDays(9)->toDateString()];
        $this->postJson("/api/marketplace/rfqs/{$rfqId}/quote", $body + ['volume_bags' => 1])->assertStatus(422)->assertJsonPath('code', 'BELOW_MINIMUM_ORDER');

        $lot->forceFill(['available_quantity_bags' => 20])->save();
        $this->postJson("/api/marketplace/rfqs/{$rfqId}/quote", $body + ['volume_bags' => 20])->assertStatus(201);
    }

    public function test_an_unsigned_deal_releases_its_bags_when_the_hold_expires(): void
    {
        [$org, $seller] = $this->business('exporter');
        $lot = $this->lot($org, $seller);
        [$quoteId, $buyer] = $this->quoteFor($seller, $lot, 40);
        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/quotes/{$quoteId}/accept")->assertOk();
        $dealId = $this->postJson('/api/deal-rooms', ['quotation_id' => $quoteId, 'title' => 'Deal'])->assertStatus(201)->json('data.id');
        $this->assertSame(60, $lot->fresh()->available_quantity_bags);

        $this->travel(StockService::holdDays() + 1)->days();
        $this->artisan('marketplace:release-expired-holds')->assertSuccessful();

        $this->assertSame(100, $lot->fresh()->available_quantity_bags);
        $this->assertSame(DealRoom::STATUS_CANCELLED, DealRoom::findOrFail($dealId)->status);
        $this->postJson("/api/deal-rooms/{$dealId}/sign-contract")->assertStatus(409)->assertJsonPath('code', 'DEAL_NOT_OPEN');
    }

    public function test_signed_deals_keep_their_bags_after_the_hold_period(): void
    {
        [$id, $buyer, $seller] = $this->openDeal();
        foreach ([$buyer, $seller] as $u) {
            Sanctum::actingAs($u);
            $this->postJson("/api/deal-rooms/{$id}/sign-contract")->assertOk();
        }
        $hold = StockReservation::where('deal_room_id', $id)->firstOrFail();
        $this->assertSame(StockReservation::STATUS_COMMITTED, $hold->status);

        $this->travel(StockService::holdDays() + 1)->days();
        $this->artisan('marketplace:release-expired-holds')->assertSuccessful();
        $this->assertSame(StockReservation::STATUS_COMMITTED, $hold->fresh()->status);
        $this->assertSame(DealRoom::STATUS_UNDER_CONTRACT, DealRoom::findOrFail($id)->status);
    }

    public function test_held_bags_cannot_be_edited_or_deleted_away(): void
    {
        [$org, $seller] = $this->business('exporter');
        $lot = $this->lot($org, $seller);
        [$quoteId, $buyer] = $this->quoteFor($seller, $lot, 40);
        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/quotes/{$quoteId}/accept")->assertOk();

        Sanctum::actingAs($seller);
        $this->putJson("/api/marketplace/seller/lots/{$lot->id}", ['total_quantity_bags' => 30])->assertStatus(422)->assertJsonPath('code', 'TOTAL_BELOW_TAKEN');
        $this->deleteJson("/api/marketplace/seller/lots/{$lot->id}")->assertStatus(409)->assertJsonPath('code', 'LOT_RESERVED');
        $this->putJson("/api/marketplace/seller/lots/{$lot->id}", ['total_quantity_bags' => 50])->assertOk();
        $this->assertSame(10, $lot->fresh()->available_quantity_bags);
    }

    // ---- D10: export licence --------------------------------------------------

    public function test_export_offers_need_an_approved_ecta_exporter_certificate(): void
    {
        $this->withoutExportLicence();
        [$org, $owner] = $this->business('supplier');
        $lot = $this->lot($org, $owner, ['status' => 'draft']);

        Sanctum::actingAs($owner);
        $this->postJson("/api/marketplace/seller/lots/{$lot->id}/publish")->assertStatus(403)->assertJsonPath('code', 'EXPORT_LICENCE_REQUIRED');

        $lot->forceFill(['status' => 'active'])->save();
        Sanctum::actingAs($this->makeUser());
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertStatus(404);

        $this->withExportLicence = true;
        $this->approveBusiness($org->fresh(), 'supplier');
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertOk();
    }

    // ---- D5: admin deal screen -------------------------------------------------

    public function test_admin_confirms_the_current_payment_step_with_a_bank_reference(): void
    {
        [$deal] = $this->makeDealRoom();
        $step = $this->milestone($deal, 'payment_escrow');
        $step->forceFill(['status' => DealMilestone::STATUS_PROOF_SUBMITTED, 'document_path' => 'deal_rooms/x/slip.png'])->save();
        $admin = $this->makeUser('admin');

        $this->actingAsStaff($admin)->get(route('admin.trade.deals.index'))->assertOk()->assertSee($deal->deal_reference);
        $this->actingAsStaff($admin)->get(route('admin.trade.deals.show', $deal->id))->assertOk()->assertSee(__('deals.confirm_title'));

        // Money needs a fresh confirmation.
        $this->actingAsStaff($admin)->post(route('admin.trade.deals.confirm', [$deal->id, $step->id]), ['decision' => 'achieved', 'bank_reference' => 'CBE-123'])
            ->assertRedirect();
        $this->assertSame(DealMilestone::STATUS_PROOF_SUBMITTED, $step->fresh()->status);

        $this->actingAsStaff($admin, true)->from(route('admin.trade.deals.show', $deal->id))
            ->post(route('admin.trade.deals.confirm', [$deal->id, $step->id]), ['decision' => 'achieved', 'bank_reference' => 'CBE-123'])
            ->assertSessionHas('success');
        $step->refresh();
        $this->assertSame(DealMilestone::STATUS_ACHIEVED, $step->status);
        $this->assertSame('CBE-123', $step->bank_reference);
        $this->assertSame($admin->id, (int) $step->verified_by);

        // A later step cannot be confirmed out of order.
        $later = $this->milestone($deal, 'bill_of_lading_released');
        $this->actingAsStaff($admin, true)->post(route('admin.trade.deals.confirm', [$deal->id, $later->id]), ['decision' => 'achieved', 'bank_reference' => 'X'])
            ->assertSessionHas('error');
        $this->assertSame(DealMilestone::STATUS_PENDING, $later->fresh()->status);
    }

    public function test_admin_decides_a_dispute_and_cancelling_returns_the_bags(): void
    {
        [$id, $buyer] = $this->openDeal();
        $lot = DealRoom::findOrFail($id)->listing;
        $this->assertSame(90, $lot->fresh()->available_quantity_bags);
        Sanctum::actingAs($buyer);
        $this->postJson("/api/deal-rooms/{$id}/dispute", ['reason' => 'quality', 'details' => 'Sample does not match'])->assertOk();

        $admin = $this->makeUser('admin');
        $this->actingAsStaff($admin, true)->post(route('admin.trade.deals.dispute', $id), ['outcome' => 'resume', 'note' => 'Seller agreed to re-test'])->assertSessionHas('success');
        $this->assertSame(DealRoom::STATUS_ACTIVE, DealRoom::findOrFail($id)->status);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/deal-rooms/{$id}/dispute", ['reason' => 'quality', 'details' => 'Re-test failed'])->assertOk();
        $this->actingAsStaff($admin, true)->post(route('admin.trade.deals.dispute', $id), ['outcome' => 'cancel', 'note' => 'Lab confirms defects'])->assertSessionHas('success');
        $this->assertSame(DealRoom::STATUS_CANCELLED, DealRoom::findOrFail($id)->status);
        $this->assertSame(100, $lot->fresh()->available_quantity_bags);
        $this->assertSame(StockReservation::STATUS_RELEASED, StockReservation::where('deal_room_id', $id)->value('status'));
    }

    public function test_non_admins_cannot_open_the_deal_screen(): void
    {
        [$deal, $buyer] = $this->makeDealRoom();
        $this->actingAs($buyer)->get(route('admin.trade.deals.show', $deal->id))->assertStatus(403);
    }
}
