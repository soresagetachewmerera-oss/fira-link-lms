<?php

namespace Tests\Feature;

use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\User;
use App\Services\CoffeePriceImportService;
use App\Services\CoffeeMarketSynthesisService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CoffeeMarketIntelligenceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * A published dataset of test-only values (rolled back after each test). These numbers are
     * fixtures for the page and API shape, not prices; real data comes from an admin import of the ECTA file.
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (CoffeeMinimumPriceDataset::where('status', 'published')->exists()) {
            return;
        }
        $date = now()->startOfWeek();
        $dataset = CoffeeMinimumPriceDataset::create([
            'title' => 'Test fixture dataset (not real prices)',
            'source' => 'Test fixture',
            'file_name' => 'fixture.xlsx',
            'file_path' => null,
            'effective_date' => $date,
            'version' => 'test-fixture',
            'total_records' => 3,
            'valid_records' => 3,
            'duplicate_records' => 0,
            'review_required_records' => 0,
            'status' => 'published',
        ]);
        foreach ([['Sidama Washed Grade 2', 'Sidama', 'Grade 2', 'Fully Washed', 3.10, 3.00], ['Guji Natural Grade 1', 'Guji', 'Grade 1', 'Natural / Sun-Dried', 3.40, 3.45], ['Limu Washed Grade 2', 'Limu', 'Grade 2', 'Fully Washed', 2.90, 2.90]] as [$type, $origin, $grade, $proc, $price, $prev]) {
            CoffeeMinimumPriceRecord::create([
                'dataset_id' => $dataset->id, 'coffee_type' => $type, 'origin' => $origin, 'grade' => $grade,
                'processing_method' => $proc, 'minimum_price' => $price, 'price_unit' => 'USD/lb', 'currency' => 'USD',
                'effective_date' => $date, 'previous_price' => $prev, 'price_change' => round($price - $prev, 3),
                'price_change_percentage' => round(($price - $prev) / $prev * 100, 2),
                'price_change_direction' => $price > $prev ? 'up' : ($price < $prev ? 'down' : 'unchanged'),
                'status' => 'valid',
            ]);
        }
    }
    /**
     * Verify that the redesigned Free Tools page loads with the institutional intelligence dashboard.
     */
    public function test_free_tools_market_intelligence_page_loads_with_institutional_components(): void
    {
        $response = $this->get('/free-tools');

        $response->assertStatus(200);
        $response->assertSee('Free Trade Tools');
        $response->assertSee('Official Regulatory Benchmark');
        $response->assertSee('Analyze with Fira AI');
        $response->assertSee('Export CSV');
        $response->assertSee('Highest Minimum Price');
        $response->assertSee('Lowest Minimum Price');
        $response->assertSee('Average Minimum Price');
        $response->assertSee('Executive Summary');
        $response->assertSee('Price Leader');
        $response->assertSee('Lowest Reference');
        $response->assertSee('Largest Movement');
        $response->assertSee('Emerging Pattern');
        $response->assertSee('Opportunity Indicators');
        $response->assertSee('Risk Indicators');
        $response->assertSee('Buyer &amp; Seller Guidance', false);
        $response->assertSee('Regional Reference Price Comparison');
        $response->assertSee('Coffee Minimum Reference Prices Table');
    }

    /**
     * Verify JSON filtering & data endpoint for Coffee Minimum Prices.
     */
    public function test_coffee_prices_json_data_endpoint(): void
    {
        $response = $this->getJson('/free-tools/coffee-prices?origin=Sidama');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'dataset' => ['id', 'title', 'source', 'effective_date', 'version'],
            'snapshot',
            'records' => ['data', 'total', 'current_page'],
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * Verify Coffee Minimum Prices CSV export endpoint.
     */
    public function test_coffee_prices_csv_export_streams_correct_data(): void
    {
        $response = $this->get('/free-tools/coffee-prices/export');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'Coffee_Minimum_Prices_'));
    }

    /**
     * Verify that Fira AI Market Synthesis returns the complete institutional analytical framework.
     */
    public function test_fira_ai_synthesis_returns_structured_market_intelligence(): void
    {
        $response = $this->postJson('/free-tools/ai-analysis', [
            'force' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'dataset_id',
            'dataset_title',
            'source',
            'effective_date',
            'snapshot' => [
                'records_analyzed',
                'highest_price',
                'highest_spec',
                'lowest_price',
                'lowest_spec',
                'average_price',
                'price_spread',
            ],
            'regional_breakdown',
            'price_movements',
            'insights' => [
                'executive_summary',
                'price_leader' => ['title', 'observation', 'evidence', 'commercial_implication', 'action'],
                'lowest_reference' => ['title', 'observation', 'evidence', 'commercial_implication', 'action'],
                'largest_movement' => ['title', 'observation', 'evidence', 'commercial_implication', 'action'],
                'emerging_pattern' => ['title', 'observation', 'evidence', 'commercial_implication', 'action'],
                'recommended_actions',
            ],
            'opportunity_signals',
            'risk_signals',
            'buyer_seller_signals',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['insights']['executive_summary']);
        $this->assertNotEmpty($data['insights']['price_leader']['observation']);
        $this->assertNotEmpty($data['insights']['price_leader']['evidence']);
        $this->assertNotEmpty($data['insights']['price_leader']['commercial_implication']);
        $this->assertNotEmpty($data['insights']['price_leader']['action']);

        // Data integrity check: zero hallucinations, no placeholder AI fluff phrases
        $summary = $data['insights']['executive_summary'];
        $this->assertStringNotContainsString("In today's rapidly changing market", $summary);
        $this->assertStringNotContainsString("It is important to note", $summary);
        $this->assertStringNotContainsString("Overall, this presents an opportunity", $summary);
    }

    /**
     * Verify authorization protection on admin coffee prices management routes.
     */
    public function test_unauthenticated_or_non_admin_cannot_access_coffee_price_admin_management(): void
    {
        // Unauthenticated
        $this->get('/admin/coffee-prices')->assertRedirect();
        $this->get('/admin/coffee-prices/import')->assertRedirect();
        $this->postJson('/admin/coffee-prices/preview', [])->assertStatus(401);

        // Authenticated as regular student (non-admin) with verified phone
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'student-' . uniqid() . '@example.com',
            'phone' => '+251911' . rand(100000, 999999),
            'phone_verified_at' => now(),
        ]);
        $this->actingAs($student)->get('/admin/coffee-prices')->assertStatus(403);
        $this->actingAs($student)->get('/admin/coffee-prices/import')->assertStatus(403);
    }

    /**
     * Test Excel import parsing service on messy spreadsheets with varied headers, empty rows, and mixed formats.
     */
    public function test_excel_import_service_parses_messy_spreadsheet(): void
    {
        // Create an in-memory spreadsheet with PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Messy headers with varied casing, spaces, and extra columns
        $sheet->setCellValue('A1', 'COFFEE TYPE / VARIETY');
        $sheet->setCellValue('B1', 'ORIGIN REGION');
        $sheet->setCellValue('C1', 'GRADE');
        $sheet->setCellValue('D1', 'PROCESSING METHOD');
        $sheet->setCellValue('E1', 'MIN PRICE ($/LB)');
        $sheet->setCellValue('F1', 'EFFECTIVE DATE');
        $sheet->setCellValue('G1', 'INTERNAL NOTES');

        // Row 2: Standard clean record
        $sheet->setCellValue('A2', 'Sidama Washed Grade 2');
        $sheet->setCellValue('B2', 'Sidama');
        $sheet->setCellValue('C2', 'Grade 2');
        $sheet->setCellValue('D2', 'Washed');
        $sheet->setCellValue('E2', '$3.450');
        $sheet->setCellValue('F2', '2026-10-25');
        $sheet->setCellValue('G2', 'Clean lot');

        // Row 3: Empty row to test whitespace resilience
        $sheet->setCellValue('A3', '');

        // Row 4: Messy record with unwashed, cents format, and variety text
        $sheet->setCellValue('A4', 'Yirgacheffe Natural Grade 1 Premium');
        $sheet->setCellValue('B4', 'Yirgacheffe');
        $sheet->setCellValue('C4', 'G1');
        $sheet->setCellValue('D4', 'sun-dried natural');
        $sheet->setCellValue('E4', '425.5¢'); // Cents representation!
        $sheet->setCellValue('F4', '2026-10-25');
        $sheet->setCellValue('G4', 'Specialty micro-lot');

        // Row 5: Duplicate of row 2 to test duplicate detection
        $sheet->setCellValue('A5', 'Sidama Washed Grade 2');
        $sheet->setCellValue('B5', 'Sidama');
        $sheet->setCellValue('C5', 'Grade 2');
        $sheet->setCellValue('D5', 'Washed');
        $sheet->setCellValue('E5', '3.450');
        $sheet->setCellValue('F5', '2026-10-25');

        // Row 6: Invalid row with missing price
        $sheet->setCellValue('A6', 'Harar Longberry Grade 4');
        $sheet->setCellValue('B6', 'Harar');
        $sheet->setCellValue('C6', 'Grade 4');
        $sheet->setCellValue('D6', 'Natural');
        $sheet->setCellValue('E6', ''); // Missing price!

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'coffee_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $importService = app(CoffeePriceImportService::class);

        // Step 1: Preview file via UploadedFile
        $uploadedFile = new UploadedFile($tempFile, 'ECTA_Messy_Test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $preview = $importService->previewFile($uploadedFile);

        $this->assertTrue($preview['success']);
        $this->assertNotEmpty($preview['headers']);
        $this->assertEquals(0, $preview['header_row_index']);

        // Check detected mapping
        $mapping = $preview['detected_mapping'];
        $this->assertEquals('coffee_type', $mapping[0]); // Column A: COFFEE TYPE / VARIETY
        $this->assertEquals('origin', $mapping[1]);      // Column B: ORIGIN REGION
        $this->assertEquals('grade', $mapping[2]);       // Column C: GRADE
        $this->assertEquals('processing_method', $mapping[3]); // Column D: PROCESSING METHOD
        $this->assertEquals('minimum_price', $mapping[4]);     // Column E: MIN PRICE ($/LB)

        // Step 2: Validate full dataset with duplicates and review checks
        $validated = $importService->parseAndValidate($tempFile, $mapping, 0, '2026-10-25', 'USD');

        $this->assertEquals(4, $validated['total_detected']); // 4 non-empty rows
        $this->assertEquals(2, $validated['valid_count']); // Rows 2 & 4 valid
        $this->assertEquals(1, $validated['duplicate_count']); // Row 5 is duplicate of Row 2
        $this->assertEquals(1, $validated['review_count']); // Row 6 has missing price

        // Check price normalization: 425.5 cents -> 4.255 USD/lb
        $yirgaRecord = collect($validated['valid'])->firstWhere('origin', 'Yirgacheffe');
        $this->assertNotNull($yirgaRecord);
        $this->assertEquals(4.255, $yirgaRecord['minimum_price']);
        $this->assertEquals('Grade 1', $yirgaRecord['grade']);
        $this->assertEquals('Natural / Sun-Dried', $yirgaRecord['processing_method']);

        // Clean up temp file
        @unlink($tempFile);
    }

    /**
     * Verify that mobile API endpoints for Coffee Toolkit return rich authentic ECTA intelligence publicly.
     */
    public function test_mobile_api_coffee_toolkit_endpoints_accessible_publicly_with_rich_intelligence(): void
    {
        // 1. Test /api/coffee-toolkit/market-intelligence
        $response = $this->getJson('/api/coffee-toolkit/market-intelligence');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'dataset' => ['id', 'title', 'source', 'effective_date', 'version'],
            'snapshot' => ['highest_price', 'lowest_price', 'average_price', 'price_spread'],
            'synthesis',
            'origins',
            'records',
            'data',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('records'));
        $this->assertNotEmpty($response->json('origins'));

        // 2. Test /api/coffee-toolkit/coffee-prices with search filter
        $pricesResponse = $this->getJson('/api/coffee-toolkit/coffee-prices?origin=Sidama');
        $pricesResponse->assertStatus(200);
        $pricesResponse->assertJsonStructure([
            'success',
            'dataset',
            'snapshot',
            'records' => ['data', 'total', 'per_page'],
        ]);
        $this->assertTrue($pricesResponse->json('success'));

        // 3. Test /api/coffee-toolkit/hub-summary
        $hubResponse = $this->getJson('/api/coffee-toolkit/hub-summary');
        $hubResponse->assertStatus(200);
        $hubResponse->assertJsonStructure([
            'success',
            'data' => [
                'market_benchmarks',
                'latest_dataset',
                'dataset_snapshot',
            ],
        ]);
        $this->assertTrue($hubResponse->json('success'));
    }
}
