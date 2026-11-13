<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\MarketplaceSellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Slice 0, item 2: evidence_level is server-controlled; only an admin review raises it.
 */
class EvidenceLevelServerControlledTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function verifiedSeller(): User
    {
        $seller = $this->makeUser();
        $suffix = Str::lower(Str::random(8));
        MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'company_name' => 'Verified Exporter ' . $suffix,
            'company_slug' => 'verified-exporter-' . $suffix,
            'business_type' => 'exporter',
            'verification_status' => 'verified',
        ]);

        return $seller;
    }

    private function lotPayload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Washed Sidama G1 ' . Str::random(4),
            'origin_region' => 'Sidama',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 50,
            'indicative_price_usd_per_lb' => 4.75,
        ], $extra);
    }

    public function test_seller_cannot_set_evidence_level_when_creating_a_lot(): void
    {
        $seller = $this->verifiedSeller();
        Sanctum::actingAs($seller);

        $res = $this->postJson('/api/marketplace/seller/listings', $this->lotPayload(['evidence_level' => 'inspected']));
        $res->assertStatus(201);

        $listing = CoffeeListing::where('seller_id', $seller->id)->latest('id')->firstOrFail();
        $this->assertSame('claimed', $listing->fresh()->evidence_level);
    }

    public function test_uploading_lab_evidence_does_not_raise_the_level_and_is_private(): void
    {
        $seller = $this->verifiedSeller();
        Sanctum::actingAs($seller);

        $this->post('/api/marketplace/seller/listings', $this->lotPayload([
            'evidence_level' => 'lab_backed',
            'evidence_document' => $this->realPdf('lab-report.pdf'),
        ]), ['Accept' => 'application/json'])->assertStatus(201);

        $listing = CoffeeListing::where('seller_id', $seller->id)->latest('id')->firstOrFail()->fresh();
        $this->assertSame('claimed', $listing->evidence_level);
        Storage::disk('local')->assertExists($listing->evidence_document_path);
        Storage::disk('public')->assertMissing($listing->evidence_document_path);
    }

    public function test_dummy_evidence_document_is_rejected(): void
    {
        $seller = $this->verifiedSeller();
        Sanctum::actingAs($seller);

        $this->post('/api/marketplace/seller/listings', $this->lotPayload([
            'evidence_document' => $this->dummyBytes('lab.pdf', 20480, '%PDF-1.7'),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'FILE_REJECTED');

        $this->assertSame(0, CoffeeListing::where('seller_id', $seller->id)->count());
    }

    public function test_evidence_level_is_not_mass_assignable(): void
    {
        $seller = $this->makeUser();
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'Mass assign probe',
            'origin_region' => 'Guji',
            'processing_method' => 'natural',
            'grade' => 'grade_1',
            'total_quantity_bags' => 5,
            'indicative_price_usd_per_lb' => 5,
            'evidence_level' => 'inspected',
        ]);

        $this->assertSame('claimed', $listing->fresh()->evidence_level);

        $listing->update(['evidence_level' => 'inspected']);
        $this->assertSame('claimed', $listing->fresh()->evidence_level);
    }

    public function test_one_time_reset_command_resets_levels_with_audit_log_only_when_forced(): void
    {
        $seller = $this->makeUser();
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'Reset probe',
            'origin_region' => 'Limu',
            'processing_method' => 'washed',
            'grade' => 'grade_2',
            'total_quantity_bags' => 5,
            'indicative_price_usd_per_lb' => 4,
        ]);
        $listing->forceFill(['evidence_level' => 'inspected'])->save();

        $this->artisan('marketplace:reset-evidence-levels')->assertSuccessful();
        $this->assertSame('inspected', $listing->fresh()->evidence_level, 'Dry run must not change anything.');

        $this->artisan('marketplace:reset-evidence-levels', ['--force' => true])->assertSuccessful();
        $this->assertSame('claimed', $listing->fresh()->evidence_level);

        $log = AuditLog::where('action', 'listing_evidence_level_reset')->where('auditable_id', $listing->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('inspected', $log->before_state['evidence_level']);
    }

    public function test_only_admin_review_can_raise_evidence_level(): void
    {
        $seller = $this->makeUser();
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'Review probe',
            'origin_region' => 'Guji',
            'processing_method' => 'natural',
            'grade' => 'grade_1',
            'total_quantity_bags' => 5,
            'indicative_price_usd_per_lb' => 5,
        ]);
        $url = route('admin.marketplace.listings.evidence-level', $listing->id);
        $payload = ['evidence_level' => 'documented', 'notes' => 'Checked 3 in-app photos'];

        // The seller, and any non-admin, is refused.
        $this->actingAs($seller)->post($url, $payload)->assertStatus(403);
        $this->assertSame('claimed', $listing->fresh()->evidence_level);

        $admin = $this->makeUser('admin');

        // A review note and a known level are required.
        $this->actingAs($admin)->post($url, ['evidence_level' => 'platinum', 'notes' => 'x'])->assertSessionHasErrors(['evidence_level', 'notes']);

        $this->actingAs($admin)->post($url, $payload)->assertRedirect();
        $this->assertSame('documented', $listing->fresh()->evidence_level);

        $this->assertTrue(AuditLog::where('action', 'listing_evidence_level_reviewed')
            ->where('auditable_id', $listing->id)
            ->where('user_id', $admin->id)
            ->exists());
    }
}
