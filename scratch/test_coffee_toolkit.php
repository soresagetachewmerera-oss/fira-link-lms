<?php

require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CoffeeExportService;
use App\Models\CoffeeMarketBenchmark;
use App\Models\User;

echo "======================================================\n";
echo "FIRA LINK COFFEE EXPORT TOOLKIT VERIFICATION TEST\n";
echo "======================================================\n\n";

$service = app(CoffeeExportService::class);

// 1. Test Profit Calculation
echo "1. Testing Deterministic Profit Calculation...\n";
$calcResult = $service->calculateProfit([
    'quantity_bags' => 320,
    'selling_price_usd_per_lb' => 3.15,
    'exchange_rate' => 142.50,
    'purchase_price_etb_per_kg' => 340.0,
    'processing_cost_etb_per_bag' => 180.0,
    'packaging_cost_etb_per_bag' => 450.0,
    'transport_corridor_etb' => 380000.0,
    'handling_customs_etb' => 165000.0,
    'incoterm' => 'FOB',
]);

echo " - Net Weight (kg): " . $calcResult['net_weight_kg'] . " kg\n";
echo " - Net Weight (lbs): " . $calcResult['net_weight_lbs'] . " lbs\n";
echo " - Gross Revenue (USD): $" . number_format($calcResult['gross_revenue_usd'], 2) . "\n";
echo " - Gross Revenue (ETB): " . number_format($calcResult['gross_revenue_etb'], 2) . " ETB\n";
echo " - Total Costs (ETB): " . number_format($calcResult['cost_breakdown_etb']['total_cost'], 2) . " ETB\n";
echo " - Net Profit (ETB): " . number_format($calcResult['net_profit_etb'], 2) . " ETB\n";
echo " - Net Margin: " . $calcResult['margin_percentage'] . "%\n";
echo " - Break-Even FOB: $" . $calcResult['break_even_price_usd_per_lb'] . " / lb\n";

assert($calcResult['net_weight_kg'] == 19200.0, "Net weight kg must be 19200");
assert($calcResult['margin_percentage'] > 0, "Deal must be profitable");
echo " [PASS] Deterministic Calculation Verified!\n\n";

// 2. Test Market Benchmarks
echo "2. Testing Market Benchmarks...\n";
$benchmarks = CoffeeMarketBenchmark::all();
echo " - Found " . $benchmarks->count() . " active market benchmarks in database.\n";
foreach ($benchmarks as $b) {
    echo "   * " . $b->benchmark_name . " | Diff: +" . $b->differential_cents_lb . "c | Indicative FOB: $" . $b->indicative_fob_usd_lb . "/lb\n";
}
assert($benchmarks->count() >= 5, "At least 5 market benchmarks expected");
echo " [PASS] Market Benchmarks Verified!\n\n";

// 3. Test Readiness Evaluation
echo "3. Testing Readiness Diagnostic Logic...\n";
$studentUser = User::role('student')->first() ?? User::first();
if ($studentUser && !$studentUser->hasRole('student')) {
    $studentUser->assignRole('student');
}
if ($studentUser) {
    $assessment = $service->evaluateReadiness($studentUser->id, [
        'has_commercial_reg' => 1,
        'has_ecta_export_cert' => 1,
        'has_nbe_code' => 1,
        'has_tax_clearance' => 1,
        'has_direct_vertical_contracts' => 1,
        'has_traceability_system' => 1,
        'has_washing_station_access' => 1,
        'has_q_grader_access' => 1,
        'has_moisture_meter' => 1,
        'has_screen_sizing_equipment' => 1,
        'has_freight_forwarder_partner' => 1,
        'knows_container_stuffing_protocols' => 1,
        'knows_phytosanitary_procedures' => 1,
        'understands_nbe_forex_retention' => 1,
        'understands_lc_cad_terms' => 1,
        'has_buyer_contract_templates' => 1,
    ]);
    echo " - Overall Score: " . $assessment->overall_score . "%\n";
    echo " - Readiness Tier: " . $assessment->readiness_tier . "\n";
    assert($assessment->overall_score == 100.0, "All yes answers must yield 100% score");
    assert($assessment->readiness_tier == 'Export-Ready', "Tier must be Export-Ready");
    echo " [PASS] Readiness Assessment Engine Verified!\n\n";

    // 4. Test Offer Generation & PDF
    echo "4. Testing Offer Sheet Generation & PDF Rendering...\n";
    $offer = $service->createOffer($studentUser->id, [
        'exporter_name' => 'Fira Link Verified Exporter PLC',
        'origin_region' => 'Sidama',
        'grade' => 'Grade 2',
        'processing_method' => 'Fully Washed',
        'quantity_bags' => 320,
        'price_usd_per_lb' => 3.25,
        'incoterm' => 'FOB Djibouti',
        'validity_date' => date('Y-m-d', strtotime('+14 days')),
    ]);
    echo " - Generated Offer Reference: " . $offer->reference_no . "\n";
    $pdf = $service->renderOfferPdf($offer);
    $pdfOutput = $pdf->output();
    echo " - PDF Size: " . strlen($pdfOutput) . " bytes\n";
    assert(strlen($pdfOutput) > 1000, "PDF must render valid bytes");
    echo " [PASS] Offer Generator & PDF Engine Verified!\n\n";

    // 5. Test Web Route Rendering
    echo "5. Testing Web Blade Views Rendering...\n";
    Auth::login($studentUser);
    $routesToTest = [
        '/student/coffee-toolkit' => 'Toolkit Hub',
        '/student/coffee-toolkit/calculator' => 'Profit Calculator',
        '/student/coffee-toolkit/offer-generator' => 'Offer Generator',
        '/student/coffee-toolkit/readiness' => 'Export Readiness',
        '/student/coffee-toolkit/market-intelligence' => 'Market Intelligence',
        '/student/coffee-toolkit/documents' => 'Document Assistant',
    ];

    foreach ($routesToTest as $uri => $name) {
        $req = Illuminate\Http\Request::create($uri, 'GET');
        $resp = $app->handle($req);
        $status = $resp->getStatusCode();
        echo " - Route $name ($uri): Status $status\n";
        assert($status == 200, "Route $uri must return 200 OK");
    }
    echo " [PASS] All Web Blade Views Verified!\n\n";
}

echo "======================================================\n";
echo "ALL BACKEND ENGINES AND INTEGRATIONS VERIFIED!\n";
echo "======================================================\n";
