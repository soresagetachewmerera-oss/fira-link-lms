<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\MarketplaceSample;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\TradeUpdate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class SamplesTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('verification');
        Storage::fake('public');
        Notification::fake();
        config(['samples.payments_mock' => false]);
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
            'seller_id' => $owner->id, 'organization_id' => $org->id, 'title' => 'Sample lot ' . Str::random(4),
            'origin_region' => 'Guji', 'processing_method' => 'natural', 'grade' => 'grade_1',
            'total_quantity_bags' => 100, 'available_quantity_bags' => 100, 'indicative_price_usd_per_lb' => 5,
            'status' => 'active', 'sample_available' => true, 'sample_cost_usd' => 0,
        ], $extra));
    }

    private function requestSample(User $buyer, int $lotId, array $extra = [])
    {
        Sanctum::actingAs($buyer);

        return $this->postJson('/api/marketplace/samples', array_merge([
            'listing_id' => $lotId,
            'quantity_grams' => 250,
            'delivery_company' => 'Hamburg Roasters GmbH',
            'delivery_address' => 'Speicherstadt 7, 20457 Hamburg',
            'delivery_country' => 'Germany',
            'delivery_phone' => '+49 40 1234567',
            'courier_carrier' => 'dhl',
            'courier_account' => '963123456',
        ], $extra));
    }

    private function ship(User $seller, int $sampleId, array $extra = [])
    {
        Sanctum::actingAs($seller);

        return $this->post("/api/marketplace/samples/{$sampleId}/dispatch", array_merge([
            'seal_id' => 'fl-' . Str::upper(Str::random(8)),
            'sealed_photo' => $this->realPng('sealed.png'),
            'retained_photo' => $this->realPng('retained.png'),
            'tracking_number' => '1234567890',
        ], $extra), ['Accept' => 'application/json']);
    }

    /** A free sample, paid and ready: [sample id, buyer, seller]. */
    private function readySample(): array
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [, $buyer] = $this->business('buyer');
        $id = $this->requestSample($buyer, $this->lot($sellerOrg, $seller)->id)->assertStatus(201)->json('data.id');

        return [$id, $buyer, $seller];
    }

    // ---- Requesting ------------------------------------------------------------

    public function test_only_tier_1_buyer_businesses_request_samples_of_published_lots(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        $lot = $this->lot($sellerOrg, $seller);
        [, $buyer] = $this->business('buyer');
        [, $unverified] = $this->business('buyer', false);
        [, $otherSeller] = $this->business('exporter');

        $this->requestSample($unverified, $lot->id)->assertStatus(403)->assertJsonPath('code', 'VERIFICATION_REQUIRED');
        $this->requestSample($otherSeller, $lot->id)->assertStatus(403)->assertJsonPath('code', 'BUYER_ROLE_REQUIRED');
        $this->requestSample($buyer, $this->lot($sellerOrg, $seller, ['status' => 'draft'])->id)->assertStatus(422)->assertJsonPath('code', 'SAMPLE_NOT_AVAILABLE');
        $this->requestSample($buyer, $this->lot($sellerOrg, $seller, ['sample_available' => false])->id)->assertStatus(422);
        $this->requestSample($buyer, $lot->id, ['quantity_grams' => 1000])->assertStatus(422);
        $this->requestSample($buyer, $lot->id, ['courier_carrier' => 'pigeon'])->assertStatus(422);

        // A free sample is ready to ship at once, with a ship-by date.
        $res = $this->requestSample($buyer, $lot->id)->assertStatus(201)
            ->assertJsonPath('data.status', 'fee_paid')
            ->assertJsonPath('data.payment_status', 'waived')
            ->assertJsonPath('data.seller_business.name', $sellerOrg->name);
        $this->assertNotNull($res->json('data.ship_by'));
        Notification::assertSentTo(User::find($seller->id), TradeUpdate::class, fn ($n) => $n->event === 'sample_requested');

        // One open sample per lot, and contact details are stored encrypted.
        $this->requestSample($buyer, $lot->id)->assertStatus(409)->assertJsonPath('code', 'SAMPLE_ALREADY_OPEN');
        $raw = DB::table('marketplace_samples')->where('id', $res->json('data.id'))->first();
        $this->assertStringNotContainsString('Speicherstadt', (string) $raw->delivery_address);
        $this->assertStringNotContainsString('1234567', (string) $raw->delivery_phone);
        $this->assertStringNotContainsString('963123456', (string) $raw->courier_account);
    }

    public function test_the_seller_sees_the_delivery_contact_only_on_that_paid_sample(): void
    {
        [$id, $buyer, $seller] = $this->readySample();
        $sample = MarketplaceSample::findOrFail($id);
        $staff = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $sample->organization_id, 'user_id' => $staff->id, 'role' => 'staff', 'is_active' => true, 'joined_at' => now()]);
        $staff->forceFill(['active_organization_id' => $sample->organization_id])->save();

        Sanctum::actingAs($seller);
        $inbox = $this->getJson('/api/marketplace/samples?side=seller&tab=to_send')->assertOk();
        $this->assertSame(1, $inbox->json('data.counts.to_send'));
        foreach (['Speicherstadt', '1234567', '963123456', '"shipping"'] as $needle) {
            $this->assertStringNotContainsString($needle, $inbox->getContent());
        }
        $this->getJson("/api/marketplace/samples/{$id}")->assertOk()->assertJsonMissingPath('data.shipping');

        $this->getJson("/api/marketplace/samples/{$id}?shipping=1")->assertOk()
            ->assertJsonPath('data.shipping.address', 'Speicherstadt 7, 20457 Hamburg')
            ->assertJsonPath('data.shipping.phone', '+49 40 1234567')
            ->assertJsonPath('data.shipping.courier_account', '963123456');
        $this->assertTrue(AuditLog::where('action', 'sample_contact_viewed')->where('auditable_id', $id)->where('user_id', $seller->id)->exists());

        // Staff of the seller business and the buyer side never get the block from this endpoint.
        Sanctum::actingAs($staff);
        $this->getJson("/api/marketplace/samples/{$id}?shipping=1")->assertOk()->assertJsonMissingPath('data.shipping');
        Sanctum::actingAs($this->makeUser());
        $this->getJson("/api/marketplace/samples/{$id}")->assertStatus(404);
    }

    // ---- Paying (mocked) -----------------------------------------------------------

    public function test_paid_samples_wait_for_payment_and_the_mock_is_off_by_default(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [, $buyer] = $this->business('buyer');
        $id = $this->requestSample($buyer, $this->lot($sellerOrg, $seller, ['sample_cost_usd' => 25])->id)->assertStatus(201)
            ->assertJsonPath('data.status', 'requested')->assertJsonPath('data.fee_usd', 25)->json('data.id');

        // Nothing to ship before payment, and the seller sees no contact.
        $this->ship($seller, $id)->assertStatus(409)->assertJsonPath('code', 'SAMPLE_NOT_READY');
        $this->getJson("/api/marketplace/samples/{$id}?shipping=1")->assertJsonMissingPath('data.shipping');

        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/samples/{$id}/pay")->assertStatus(503)->assertJsonPath('code', 'PAYMENTS_NOT_AVAILABLE');
        $this->assertSame('requested', MarketplaceSample::findOrFail($id)->status);

        config(['samples.payments_mock' => true]);
        $this->app['env'] = 'production';
        $this->postJson("/api/marketplace/samples/{$id}/pay")->assertStatus(503);
        $this->app['env'] = 'testing';

        $this->postJson("/api/marketplace/samples/{$id}/pay")->assertOk()
            ->assertJsonPath('data.status', 'fee_paid')
            ->assertJsonPath('data.payment_mocked', true);
        $this->postJson("/api/marketplace/samples/{$id}/cancel")->assertStatus(409)->assertJsonPath('code', 'SAMPLE_NOT_CANCELLABLE');
    }

    public function test_an_unpaid_sample_can_be_cancelled_by_the_buyer_only(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [, $buyer] = $this->business('buyer');
        $id = $this->requestSample($buyer, $this->lot($sellerOrg, $seller, ['sample_cost_usd' => 10])->id)->json('data.id');

        Sanctum::actingAs($seller);
        $this->postJson("/api/marketplace/samples/{$id}/cancel")->assertStatus(403);
        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/samples/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    // ---- Seal and ship -------------------------------------------------------------

    public function test_sealing_needs_a_unique_seal_id_real_photos_and_tracking(): void
    {
        [$id, $buyer, $seller] = $this->readySample();
        [$id2, , $seller2] = $this->readySample();

        // Only the seller business ships its own sample.
        $this->ship($seller2, $id)->assertStatus(403);
        $this->ship($buyer, $id)->assertStatus(403);

        $this->ship($seller, $id, ['seal_id' => 'x!'])->assertStatus(422)->assertJsonPath('code', 'SEAL_ID_INVALID');
        $this->ship($seller, $id, ['sealed_photo' => $this->dummyBytes('sealed.jpg', 20480, "\xFF\xD8\xFF")])->assertStatus(422)->assertJsonPath('code', 'FILE_REJECTED');
        $this->ship($seller, $id, ['retained_photo' => $this->tinyPng('retained.png')])->assertStatus(422)->assertJsonPath('code', 'FILE_REJECTED');
        $this->assertSame([], Storage::disk('verification')->allFiles());

        $res = $this->ship($seller, $id, ['seal_id' => 'fl 0042-ab'])->assertOk()
            ->assertJsonPath('data.status', 'dispatched')
            ->assertJsonPath('data.seal_id', 'FL0042-AB')
            ->assertJsonPath('data.courier', 'dhl')
            ->assertJsonPath('data.tracking_number', '1234567890');
        $this->assertStringContainsString('1234567890', $res->json('data.tracking_url'));
        $sample = MarketplaceSample::findOrFail($id);
        Storage::disk('verification')->assertExists($sample->sealed_photo_path);
        Storage::disk('verification')->assertExists($sample->retained_photo_path);
        Storage::disk('public')->assertMissing($sample->sealed_photo_path);
        Notification::assertSentTo(User::find($buyer->id), TradeUpdate::class, fn ($n) => $n->event === 'sample_dispatched');

        // A seal is used once.
        $this->ship($seller2, $id2, ['seal_id' => 'FL0042-AB'])->assertStatus(422)->assertJsonPath('code', 'SEAL_ID_USED');
        // Shipped twice? No.
        $this->ship($seller, $id)->assertStatus(409);
    }

    public function test_seal_photos_open_only_through_a_signed_link_for_either_business(): void
    {
        [$id, $buyer, $seller] = $this->readySample();
        $this->ship($seller, $id)->assertOk();

        Sanctum::actingAs($buyer);
        $url = $this->getJson("/api/marketplace/samples/{$id}")->assertOk()->json('data.photos.retained');
        $this->assertNotNull($url);
        $this->get($url)->assertOk();
        $this->get("/api/marketplace/samples/{$id}/photos/retained")->assertStatus(403);

        Sanctum::actingAs($this->makeUser());
        $this->get(URL::temporarySignedRoute('api.samples.photo', now()->addMinutes(10), ['sample' => $id, 'kind' => 'retained']))->assertStatus(404);
    }

    // ---- Delivery and cupping ----------------------------------------------------------

    public function test_buyer_confirms_the_seal_then_cups_and_approval_goes_into_the_deal_room(): void
    {
        [$id, $buyer, $seller] = $this->readySample();
        $sample = MarketplaceSample::findOrFail($id);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/samples/{$id}/delivered", ['seal_intact' => true])->assertStatus(409)->assertJsonPath('code', 'SAMPLE_NOT_SHIPPED');
        $this->postJson("/api/marketplace/samples/{$id}/evaluate", ['decision' => 'approved', 'cupping_score' => 87])->assertStatus(409);

        $seal = 'FL-' . Str::upper(Str::random(6));
        $this->ship($seller, $id, ['seal_id' => $seal])->assertOk();

        Sanctum::actingAs($seller);
        $this->postJson("/api/marketplace/samples/{$id}/delivered", ['seal_intact' => true])->assertStatus(403);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/samples/{$id}/delivered", ['seal_intact' => true])->assertOk()
            ->assertJsonPath('data.status', 'delivered')->assertJsonPath('data.seal_intact', true);
        $this->postJson("/api/marketplace/samples/{$id}/evaluate", ['decision' => 'approved', 'cupping_score' => 120])->assertStatus(422);
        $this->postJson("/api/marketplace/samples/{$id}/evaluate", ['decision' => 'approved', 'cupping_score' => 87.5, 'cupping_notes' => 'Bergamot, clean'])->assertOk()
            ->assertJsonPath('data.status', 'cupped_approved')->assertJsonPath('data.cupping_score', 87.5);
        Notification::assertSentTo(User::find($seller->id), TradeUpdate::class, fn ($n) => $n->event === 'sample_approved');

        // A deal room for this lot carries the approved seal ID.
        $quote = $this->acceptedQuote(CoffeeListing::findOrFail($sample->listing_id), $buyer, Organization::findOrFail($sample->buyer_organization_id), 50000);
        $dealId = $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Guji deal'])
            ->assertStatus(201)->json('data.id');
        $this->assertSame($seal, DealRoom::findOrFail($dealId)->sample_seal_id);
    }

    public function test_compare_quotes_shows_the_buyers_sample_status(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        $lot = $this->lot($sellerOrg, $seller);
        [, $buyer] = $this->business('buyer');
        Sanctum::actingAs($buyer);
        $rfqId = $this->postJson('/api/marketplace/rfqs', [
            'title' => 'Guji natural', 'volume_value' => 10, 'volume_unit' => 'bags', 'destination' => 'Hamburg', 'incoterm' => 'FOB',
        ])->assertStatus(201)->json('data.id');
        Sanctum::actingAs($seller);
        $this->postJson("/api/marketplace/rfqs/{$rfqId}/quote", [
            'listing_id' => $lot->id, 'price_usd_per_lb' => 9, 'volume_bags' => 10, 'valid_days' => 7, 'ready_to_ship' => now()->addWeek()->toDateString(),
        ])->assertStatus(201);

        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertJsonPath('data.quotes.0.sample', null);
        $sampleId = $this->requestSample($buyer, $lot->id)->json('data.id');
        $this->ship($seller, $sampleId)->assertOk();
        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/rfqs/{$rfqId}/quotes")->assertJsonPath('data.quotes.0.sample.status', 'dispatched');
    }
}
