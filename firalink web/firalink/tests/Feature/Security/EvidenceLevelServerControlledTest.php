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
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Slice 0, item 2: evidence_level is server-controlled; only an admin review raises it.
 */
class EvidenceLevelServerControlledTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

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
        tap(MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'company_name' => 'Verified Exporter ' . $suffix,
            'company_slug' => 'verified-exporter-' . $suffix,
            'business_type' => 'exporter',
        ]), fn ($p) => $p->forceFill(['verification_status' => 'verified'])->save());
        $org = $this->approveBusiness($this->makeOrganization($seller), 'exporter');
        $seller->forceFill(['active_organization_id' => $org->id])->save();

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

        $res = $this->postJson('/api/marketplace/seller/lots', $this->lotPayload(['evidence_level' => 'inspected', 'status' => 'active']));
        $res->assertStatus(201)->assertJsonPath('data.evidence_level', 'claimed')->assertJsonPath('data.status', 'draft');

        $listing = CoffeeListing::findOrFail($res->json('data.id'));
        $this->assertSame('claimed', $listing->evidence_level);

        $this->putJson("/api/marketplace/seller/lots/{$listing->id}", ['title' => 'Renamed', 'origin_region' => 'Sidama', 'evidence_level' => 'lab_backed'])->assertOk();
        $this->assertSame('claimed', $listing->fresh()->evidence_level);
    }

    public function test_uploading_lab_evidence_does_not_raise_the_level_and_is_private(): void
    {
        Storage::fake('verification');
        $seller = $this->verifiedSeller();
        Sanctum::actingAs($seller);
        $lotId = $this->postJson('/api/marketplace/seller/lots', $this->lotPayload())->json('data.id');

        $res = $this->post("/api/marketplace/seller/lots/{$lotId}/evidence/lab-report", [
            'pages' => [$this->realPdf('lab-report.pdf')],
            'lab_name' => 'ECTA Central Lab',
            'evidence_level' => 'lab_backed',
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $this->assertSame('claimed', CoffeeListing::findOrFail($lotId)->evidence_level);
        $page = \App\Models\LotEvidence::findOrFail($res->json('data.evidence_id'))->pages()->firstOrFail();
        Storage::disk('verification')->assertExists($page->file_path);
        Storage::disk('public')->assertMissing($page->file_path);
    }

    public function test_dummy_evidence_document_is_rejected(): void
    {
        Storage::fake('verification');
        $seller = $this->verifiedSeller();
        Sanctum::actingAs($seller);
        $lotId = $this->postJson('/api/marketplace/seller/lots', $this->lotPayload())->json('data.id');

        $this->post("/api/marketplace/seller/lots/{$lotId}/evidence/lab-report", [
            'pages' => [$this->dummyBytes('lab.pdf', 20480, '%PDF-1.7')],
            'lab_name' => 'Lab',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'FILE_REJECTED');

        $this->assertSame(0, \App\Models\LotEvidence::where('listing_id', $lotId)->count());
        $this->assertSame([], Storage::disk('verification')->allFiles());
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

    public function test_there_is_no_route_that_sets_a_level_directly(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.marketplace.listings.evidence-level'));

        $seller = $this->verifiedSeller();
        Sanctum::actingAs($seller);
        $lotId = $this->postJson('/api/marketplace/seller/lots', $this->lotPayload())->json('data.id');
        $this->postJson("/api/marketplace/seller/lots/{$lotId}/level", ['evidence_level' => 'inspected'])->assertStatus(404);
        $this->assertSame('claimed', CoffeeListing::findOrFail($lotId)->evidence_level);
    }
}
