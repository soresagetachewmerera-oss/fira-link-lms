<?php

namespace Tests\Feature;

use App\Models\DocumentDownloadEvent;
use App\Models\DocumentTemplate;
use App\Models\ExportShipment;
use App\Models\GeneratedDocument;
use App\Models\Organization;
use App\Models\User;
use App\Services\ExportCalculationService;
use App\Services\ExportDocumentGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class ExportDocsStudioTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        // Generated PDFs and spreadsheets go to a fake disk, never to storage/app/private.
        Storage::fake('export_docs');
    }

    /** A user with their own business (the export docs API works per business). */
    private function exporterUser(): User
    {
        $user = $this->makeUser();
        $this->makeOrganization($user);

        return $user->fresh();
    }

    /**
     * Test deterministic trade calculation precision.
     */
    public function test_deterministic_calculation_service_precision(): void
    {
        $calculator = app(ExportCalculationService::class);

        // 320 bags of 60kg each with 0.80kg tare deduction
        $weights = $calculator->computeWeights(320, 60.0, 0.80);

        $this->assertEquals(320, $weights['bags']);
        $this->assertEquals(19200.00, $weights['net_weight_kg']);
        $this->assertEquals(256.00, $weights['tare_total_kg']);
        $this->assertEquals(19456.00, $weights['gross_weight_kg']);
        $this->assertEquals(42328.75, $weights['net_weight_lbs']);
        $this->assertEquals(19.2000, $weights['net_metric_tons']);

        // Pricing at $2.6000 per LB
        $finances = $calculator->computeInvoiceAmount($weights, 2.6000);

        $this->assertEquals('LB', $finances['pricing_basis']);
        $this->assertEquals(2.6000, $finances['applied_unit_price']);
        $this->assertEquals(110054.75, $finances['total_value_usd']);
        $this->assertEquals(
            'ONE HUNDRED TEN THOUSAND FIFTY-FOUR UNITED STATES DOLLARS AND SEVENTY-FIVE CENTS',
            $finances['total_amount_in_words']
        );
    }

    /**
     * Test templates catalog API returns 30 industry standard templates.
     */
    public function test_template_catalog_api_returns_all_30_templates(): void
    {
        $response = $this->getJson('/api/export-docs/templates');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'total',
                'categories',
                'data',
            ]);

        $this->assertGreaterThanOrEqual(30, $response->json('total'));
    }

    /**
     * Test retrieving single template schema and default sample data.
     */
    public function test_single_template_schema_and_sample_data_endpoint(): void
    {
        $response = $this->getJson('/api/export-docs/templates/EXP-CI-01');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.template_code', 'EXP-CI-01')
            ->assertJsonStructure([
                'schema' => ['sections'],
                'sample_data' => ['exporter_name', 'total_bags'],
            ]);
    }

    /**
     * Test stateless calculate API endpoint.
     */
    public function test_stateless_calculate_api_endpoint(): void
    {
        $response = $this->postJson('/api/export-docs/calculate', [
            'total_bags' => 320,
            'bag_weight_kg' => 60.0,
            'tare_weight_per_bag_kg' => 0.80,
            'unit_price_usd_per_lb' => 2.6000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('weights.net_weight_kg', 19200)
            ->assertJsonPath('weights.gross_weight_kg', 19456)
            ->assertJsonPath('financials.total_value_usd', 110054.75);
    }

    /**
     * Test creating a canonical master shipment ("Enter Shipment Once").
     */
    public function test_create_canonical_export_shipment(): void
    {
        $user = $this->exporterUser();

        $response = $this->actingAs($user)->postJson('/api/export-docs/shipments', [
            'contract_number' => 'TEST/EXP/2026/01',
            'exporter_name' => 'Buna Test Exporters PLC',
            'buyer_name' => 'Complete Coffee London Ltd',
            'commodity' => 'Ethiopia Washed Yirgacheffe Grade 1',
            'origin_region' => 'Yirgacheffe',
            'coffee_grade' => 'Grade 1',
            'total_bags' => 320,
            'bag_weight_kg' => 60.0,
            'tare_weight_per_bag_kg' => 0.80,
            'unit_price_usd_per_lb' => 3.1500,
            'ico_number' => '010/0568/9999',
            'container_number' => 'MSCU1234567',
            'seal_number' => 'ET-SEAL-888',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertEquals(19200.00, (float)$response->json('data.total_net_weight_kg'));
        $this->assertEquals(42328.75, (float)$response->json('data.total_net_weight_lbs'));
        $this->assertEquals(133335.56, (float)$response->json('data.total_value_usd'));

        $shipmentId = $response->json('data.id');
        $this->assertDatabaseHas('export_shipments', ['id' => $shipmentId]);
    }

    /**
     * Test 1-Click Generate All Export Documents from canonical shipment.
     */
    public function test_1_click_generate_all_documents_from_shipment(): void
    {
        $user = $this->exporterUser();
        $org = $user->primaryOrganization();

        $shipment = ExportShipment::create([
            'organization_id' => $org->id,
            'shipment_reference' => 'SHP-TEST-' . rand(1000, 9999),
            'reference_number' => 'SHP-TEST-' . rand(1000, 9999),
            'contract_number' => 'CONT-TEST-01',
            'exporter_name' => 'Test Coffee Exporter PLC',
            'buyer_name' => 'Volcafe Specialty Zurich',
            'commodity' => 'Ethiopia Natural Guji Grade 1',
            'origin_region' => 'Guji',
            'coffee_grade' => 'Grade 1',
            'total_bags' => 320,
            'bag_weight_kg' => 60.0,
            'tare_weight_per_bag_kg' => 0.80,
            'total_net_weight_kg' => 19200.00,
            'total_gross_weight_kg' => 19456.00,
            'total_net_weight_lbs' => 42328.75,
            'unit_price_usd_per_lb' => 2.8500,
            'total_value_usd' => 120636.94,
            'ico_number' => '010/0568/7777',
            'container_number' => 'CMAU9876543',
            'seal_number' => 'ET-999-SEAL',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/api/export-docs/shipments/{$shipment->id}/generate-all", [
            'template_codes' => ['EXP-CI-01', 'EXP-PL-07', 'EXP-WS-08'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $documents = $response->json('documents');
        $this->assertCount(3, $documents);

        // Verify each document has PDF and Excel files on private disk
        foreach ($documents as $docData) {
            $this->assertNotNull($docData['pdf_path'], "PDF path must be populated for {$docData['document_number']}");
            $this->assertNotNull($docData['xlsx_path'], "XLSX path must be populated for {$docData['document_number']}");
            $this->assertTrue(Storage::disk('export_docs')->exists($docData['pdf_path']), "PDF file must exist on private disk");
            $this->assertTrue(Storage::disk('export_docs')->exists($docData['xlsx_path']), "Excel file must exist on private disk");
        }
    }

    /**
     * Test document draft creation, editing, autosave, and revision tracking.
     */
    public function test_document_draft_creation_and_autosave(): void
    {
        $user = $this->exporterUser();

        // 1. Create draft
        $createRes = $this->actingAs($user)->postJson('/api/export-docs/documents', [
            'template_code' => 'EXP-CI-01',
            'title' => 'Commercial Invoice Draft 01',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $docId = $createRes->json('data.id');

        // 2. Autosave / update payload
        $updateRes = $this->actingAs($user)->putJson("/api/export-docs/documents/{$docId}", [
            'data_payload' => [
                'total_bags' => 320,
                'unit_price_usd_per_lb' => 2.7500,
                'buyer_name' => 'InterAmerican Coffee Hamburg',
            ],
            'change_summary' => 'Updated buyer name and pricing',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('calculated_summary.total_value_usd', 116404.06);

        // Check revisions count
        $doc = GeneratedDocument::find($docId);
        $this->assertGreaterThanOrEqual(2, $doc->revisions()->count());
    }

    /**
     * Test document finalization locks record and makes it immutable.
     */
    public function test_document_finalization_locks_record_and_computes_sha256(): void
    {
        $user = $this->exporterUser();

        $createRes = $this->actingAs($user)->postJson('/api/export-docs/documents', [
            'template_code' => 'EXP-CI-01',
            'title' => 'Invoice for Finalization',
        ]);

        $docId = $createRes->json('data.id');

        // Finalize
        $finalRes = $this->actingAs($user)->postJson("/api/export-docs/documents/{$docId}/finalize");

        $finalRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'finalized');

        $doc = GeneratedDocument::find($docId);
        $this->assertTrue($doc->isFinalized());
        $this->assertNotEmpty($doc->sha256_hash);
        $this->assertEquals(64, strlen($doc->sha256_hash));

        // Attempting to update a finalized document must return 422
        $blockedUpdate = $this->actingAs($user)->putJson("/api/export-docs/documents/{$docId}", [
            'data_payload' => ['buyer_name' => 'Tampered Buyer'],
        ]);

        $blockedUpdate->assertStatus(422);
    }

    /**
     * Test secure streaming download with audit trail logging.
     */
    public function test_secure_download_with_audit_trail(): void
    {
        $user = $this->exporterUser();

        $createRes = $this->actingAs($user)->postJson('/api/export-docs/documents', [
            'template_code' => 'EXP-CI-01',
            'title' => 'Downloadable Invoice',
        ]);

        $docId = $createRes->json('data.id');

        // Download PDF
        $response = $this->actingAs($user)->get("/api/export-docs/documents/{$docId}/download/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify audit log event
        $this->assertDatabaseHas('document_download_events', [
            'generated_document_id' => $docId,
            'format' => 'pdf',
        ]);
    }

    /**
     * Test Web Blade pages load successfully.
     */
    public function test_web_catalog_and_studio_pages_render(): void
    {
        $user = $this->exporterUser();

        // 1. Template Marketplace Index
        $indexRes = $this->actingAs($user)->get('/export-docs');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Template Marketplace');
        $indexRes->assertSee('EXP-CI-01');
        $indexRes->assertSee('Commercial Invoice');

        // 2. Start from template
        $startRes = $this->actingAs($user)->get('/export-docs/templates/EXP-CI-01/start');
        $startRes->assertRedirect();

        // Extract redirected studio URL
        $studioUrl = $startRes->headers->get('Location');
        $this->assertStringContainsString('/export-docs/studio/', $studioUrl);

        // 3. Studio workspace loads
        $studioRes = $this->actingAs($user)->get($studioUrl);
        $studioRes->assertStatus(200);
        $studioRes->assertSee('Document Particulars');
        $studioRes->assertSee('Trade Auditor', false);
        $studioRes->assertSee('Fira AI Assistant');
    }

    /**
     * Test Fira AI Trade Assistant with guardrails.
     */
    public function test_fira_ai_assistant_trade_guardrails(): void
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/export-docs/ai/assist', [
            'action' => 'review',
            'template_code' => 'EXP-CI-01',
            'data_payload' => [
                'commodity' => 'Ethiopia Sidama Coffee',
                'total_bags' => 320,
            ],
            'user_prompt' => 'Is this document ready for export?',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $advice = $response->json('advice');
        $this->assertNotEmpty($advice);
        // Ensure negative guardrail: clarifies official certificates
        $this->assertStringContainsString('ECTA', $advice);
    }
}
