<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeListing;
use App\Models\MarketplaceSellerProfile;
use App\Models\TrustReport;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use App\Services\Auth\FreshAuth;
use Tests\TestCase;

/**
 * Slice 0, item 3: real files only, stored privately, admins open them through
 * short-lived signed URLs; existing public files can be migrated.
 */
class PrivateEvidenceUploadTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('verification');
    }

    public function test_report_evidence_is_validated_and_private(): void
    {
        Sanctum::actingAs($this->makeUser());

        $reported = $this->makeOrganization($this->makeUser());
        $this->post('/api/reports', [
            'reason' => 'Asked me to pay outside Fira Link',
            'details' => 'Sent a Telegram number',
            'reported_organization_id' => $reported->id,
            'evidence' => $this->dummyBytes('screenshot.png'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonPath('code', 'FILE_REJECTED');

        $res = $this->post('/api/reports', [
            'reason' => 'Asked me to pay outside Fira Link',
            'details' => 'Sent a Telegram number',
            'reported_organization_id' => $reported->id,
            'evidence' => $this->realPng('screenshot.png'),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $path = TrustReport::findOrFail($res->json('data.id'))->evidence_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_verification_document_rejects_dummy_and_uses_server_extension(): void
    {
        $owner = $this->makeUser();
        $this->makeOrganization($owner);
        FreshAuth::markUser($owner);
        Sanctum::actingAs($owner);

        foreach ([$this->emptyFile('license.pdf'), $this->dummyBytes('license.pdf'), $this->dummyBytes('license.pdf', 20480, '%PDF-1.4'), $this->tinyPng('license.png')] as $file) {
            $this->post('/api/marketplace/verification/documents', [
                'document_type' => 'trade_license',
                'document_number' => 'TL-1',
                'expires_at' => now()->addYear()->toDateString(),
                'pages' => [$file],
            ], ['Accept' => 'application/json'])->assertStatus(422);
        }
        $this->assertSame([], Storage::disk('verification')->allFiles());

        // A real PDF with a misleading name is stored with the extension of its real type.
        $this->post('/api/marketplace/verification/documents', [
            'document_type' => 'trade_license',
            'document_number' => 'TL-1',
            'expires_at' => now()->addYear()->toDateString(),
            'pages' => [$this->realPdf('license.exe')],
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $doc = VerificationDocument::latest('id')->firstOrFail();
        $this->assertStringEndsWith('.pdf', $doc->file_path);
        Storage::disk('verification')->assertExists($doc->file_path);
    }

    public function test_seller_license_is_private(): void
    {
        $seller = $this->makeUser();
        Sanctum::actingAs($seller);

        $this->post('/api/marketplace/seller/profile', [
            'company_name' => 'License Probe ' . Str::random(5),
            'business_type' => 'exporter',
            'license_document' => $this->realPdf('ecta.pdf'),
        ], ['Accept' => 'application/json'])->assertOk();

        $path = MarketplaceSellerProfile::where('user_id', $seller->id)->firstOrFail()->license_document_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_opens_private_file_only_with_a_valid_signed_url(): void
    {
        $seller = $this->makeUser();
        $path = 'marketplace/listings/evidence/' . Str::uuid() . '.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'Signed URL probe',
            'origin_region' => 'Jimma',
            'processing_method' => 'natural',
            'grade' => 'grade_3',
            'total_quantity_bags' => 5,
            'indicative_price_usd_per_lb' => 3,
            'evidence_document_path' => $path,
        ]);

        $admin = $this->makeUser('admin');
        $signed = app(\App\Services\PrivateDocumentStore::class)->adminUrl('listing-evidence', $listing->id, 10);

        $this->actingAsStaff($admin)->get($signed)->assertOk();

        // Unsigned URL is refused.
        $this->actingAsStaff($admin)->get("/admin/private-files/listing-evidence/{$listing->id}")->assertStatus(403);

        // Expired link is refused.
        $this->travel(11)->minutes();
        $this->actingAsStaff($admin)->get($signed)->assertStatus(403);
        $this->travelBack();

        // A valid signed URL is still useless to a non-admin.
        $fresh = app(\App\Services\PrivateDocumentStore::class)->adminUrl('listing-evidence', $listing->id, 10);
        $this->actingAs($seller)->get($fresh)->assertStatus(403);

        // The file is not on the public disk at all.
        Storage::disk('public')->assertMissing($path);
    }

    public function test_move_command_moves_existing_public_files(): void
    {
        $seller = $this->makeUser();
        $path = 'marketplace/listings/evidence/legacy-' . Str::random(8) . '.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 legacy evidence');
        $listing = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'Legacy probe',
            'origin_region' => 'Harar',
            'processing_method' => 'natural',
            'grade' => 'grade_4',
            'total_quantity_bags' => 5,
            'indicative_price_usd_per_lb' => 3,
            'evidence_document_path' => $path,
        ]);

        $this->artisan('files:move-evidence-private', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('public')->assertExists($path);

        $this->artisan('files:move-evidence-private')->assertSuccessful();
        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame($path, $listing->fresh()->evidence_document_path);
    }
}
