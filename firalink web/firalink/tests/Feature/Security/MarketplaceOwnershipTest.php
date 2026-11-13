<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeListing;
use App\Models\Conversation;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSample;
use App\Models\MarketplaceSellerProfile;
use App\Models\VerificationDocument;
use App\Models\OrganizationVerification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Slice 0, item 6: ownership on samples, RFQ quotes, verification documents and messages.
 */
class MarketplaceOwnershipTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    /**
     * @return array{0: MarketplaceSample, 1: \App\Models\User, 2: \App\Models\User} sample, buyer, seller
     */
    private function makeSample(): array
    {
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $suffix = Str::lower(Str::random(8));
        MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'organization_id' => $org->id,
            'company_name' => 'Sample Seller ' . $suffix,
            'company_slug' => 'sample-seller-' . $suffix,
            'business_type' => 'exporter',
            'verification_status' => 'verified',
        ]);
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $org->id,
            'title' => 'Sample lot',
            'origin_region' => 'Yirgacheffe',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 10,
            'indicative_price_usd_per_lb' => 5,
        ]);
        $buyer = $this->makeUser();
        $sample = MarketplaceSample::create([
            'sample_reference' => 'SMP-T' . Str::upper(Str::random(7)),
            'listing_id' => $listing->id,
            'organization_id' => $org->id,
            'buyer_id' => $buyer->id,
            'status' => MarketplaceSample::STATUS_FEE_PAID,
            'shipping_address' => 'Hamburg',
        ]);

        return [$sample, $buyer, $seller];
    }

    public function test_other_verified_exporter_cannot_dispatch_someone_elses_sample(): void
    {
        [$sample] = $this->makeSample();
        [, , $otherSeller] = $this->makeSample(); // verified exporter of a different org

        Sanctum::actingAs($otherSeller);
        $this->postJson("/api/marketplace/samples/{$sample->id}/dispatch", [
            'courier_name' => 'DHL', 'waybill_number' => 'W1', 'retained_sample_seal_no' => 'S1',
        ])->assertStatus(403);

        $this->assertSame(MarketplaceSample::STATUS_FEE_PAID, $sample->fresh()->status);
    }

    public function test_seller_can_dispatch_own_sample(): void
    {
        [$sample, , $seller] = $this->makeSample();

        Sanctum::actingAs($seller);
        $this->postJson("/api/marketplace/samples/{$sample->id}/dispatch", [
            'courier_name' => 'DHL', 'waybill_number' => 'W1', 'retained_sample_seal_no' => 'S1',
        ])->assertOk();

        $this->assertSame(MarketplaceSample::STATUS_DISPATCHED, $sample->fresh()->status);
    }

    public function test_only_the_buyer_confirms_delivery(): void
    {
        [$sample, $buyer, $seller] = $this->makeSample();

        foreach ([$this->makeUser(), $seller] as $notBuyer) {
            Sanctum::actingAs($notBuyer);
            $this->postJson("/api/marketplace/samples/{$sample->id}/delivered")->assertStatus(403);
        }
        $this->assertSame(MarketplaceSample::STATUS_FEE_PAID, $sample->fresh()->status);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/marketplace/samples/{$sample->id}/delivered")->assertOk();
        $this->assertSame(MarketplaceSample::STATUS_DELIVERED, $sample->fresh()->status);
    }

    public function test_stranger_cannot_view_or_evaluate_a_sample(): void
    {
        [$sample] = $this->makeSample();
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/marketplace/samples/{$sample->id}")->assertStatus(403);
        $this->postJson("/api/marketplace/samples/{$sample->id}/evaluate", ['decision' => 'approved', 'cupping_score' => 86])
            ->assertStatus(403);
    }

    public function test_only_rfq_owner_reads_quotes_and_changes_quote_status(): void
    {
        $owner = $this->makeUser();
        $seller = $this->makeUser();
        $rfq = MarketplaceRfq::create([
            'buyer_id' => $owner->id,
            'rfq_number' => 'RFQ-T' . Str::upper(Str::random(8)),
            'title' => 'Need Guji',
            'volume_bags' => 20,
            'destination_country' => 'Japan',
            'destination_port' => 'Yokohama',
        ]);
        $quote = MarketplaceQuotation::create([
            'rfq_id' => $rfq->id,
            'seller_id' => $seller->id,
            'quote_number' => 'Q-T' . Str::upper(Str::random(8)),
            'offered_price_usd_per_lb' => 4,
            'total_price_usd' => 5000,
            'quantity_bags' => 20,
            'validity_date' => now()->addDays(10),
        ]);

        Sanctum::actingAs($this->makeUser());
        $this->getJson("/api/marketplace/rfqs/{$rfq->id}/quotes")->assertStatus(404);
        $this->putJson("/api/marketplace/quotes/{$quote->id}/status", ['status' => 'accepted'])->assertStatus(403);

        Sanctum::actingAs($seller);
        $this->putJson("/api/marketplace/quotes/{$quote->id}/status", ['status' => 'accepted'])->assertStatus(403);
        $this->assertNotSame('accepted', $quote->fresh()->status);
    }

    public function test_stranger_cannot_delete_or_download_a_verification_document(): void
    {
        Storage::fake('verification');
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $verification = OrganizationVerification::create([
            'organization_id' => $org->id,
            'target_role' => 'exporter',
            'status' => OrganizationVerification::STATUS_DRAFT,
        ]);
        Storage::disk('verification')->put('organizations/x/doc.pdf', '%PDF-1.4');
        $doc = VerificationDocument::create([
            'organization_verification_id' => $verification->id,
            'document_type' => 'commercial_reg',
            'file_path' => 'organizations/x/doc.pdf',
            'file_disk' => 'verification',
            'original_filename' => 'doc.pdf',
        ]);

        $stranger = $this->makeUser();
        $this->makeOrganization($stranger);

        Sanctum::actingAs($stranger);
        $this->deleteJson("/api/marketplace/verification/documents/{$doc->id}")->assertStatus(403);
        $this->assertNotNull($doc->fresh());

        $this->actingAs($stranger)->get(route('marketplace.verification.download', $doc->id))->assertStatus(403);
    }

    public function test_stranger_cannot_read_or_post_in_someone_elses_conversation(): void
    {
        $student = $this->makeUser();
        $instructor = $this->makeUser('instructor');
        $conversation = Conversation::create([
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'is_accepted' => true,
        ]);

        Sanctum::actingAs($this->makeUser());
        $this->getJson("/api/messages/{$conversation->id}")->assertStatus(404);
        $this->postJson("/api/messages/{$conversation->id}/send", ['message' => 'hi'])->assertStatus(404);
        $this->getJson("/api/messages/{$conversation->id}/poll")->assertStatus(404);
    }
}
