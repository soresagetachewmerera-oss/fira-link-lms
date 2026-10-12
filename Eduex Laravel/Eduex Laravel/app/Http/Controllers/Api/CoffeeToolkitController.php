<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeExportCalculation;
use App\Models\CoffeeExportOffer;
use App\Models\CoffeeReadinessAssessment;
use App\Models\CoffeeMarketBenchmark;
use App\Models\Course;
use App\Services\CoffeeExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoffeeToolkitController extends Controller
{
    protected $coffeeService;

    public function __construct(CoffeeExportService $coffeeService)
    {
        $this->coffeeService = $coffeeService;
    }

    /**
     * Hub dashboard summary for mobile & web API
     */
    public function hubSummary()
    {
        $userId = Auth::id();

        $recentCalculations = CoffeeExportCalculation::where('user_id', $userId)
            ->latest()
            ->take(3)
            ->get();

        $recentOffers = CoffeeExportOffer::where('user_id', $userId)
            ->latest()
            ->take(3)
            ->get();

        $latestAssessment = CoffeeReadinessAssessment::where('user_id', $userId)
            ->latest()
            ->first();

        $marketBenchmarks = CoffeeMarketBenchmark::latest('market_date')
            ->take(5)
            ->get();

        // Academy trade courses
        $tradeCourses = Course::where('is_published', true)
            ->latest()
            ->take(4)
            ->get(['id', 'title', 'slug', 'thumbnail', 'price', 'level']);

        return response()->json([
            'success' => true,
            'data' => [
                'recent_calculations' => $recentCalculations,
                'recent_offers' => $recentOffers,
                'latest_assessment' => $latestAssessment,
                'market_benchmarks' => $marketBenchmarks,
                'trade_courses' => $tradeCourses,
            ],
        ]);
    }

    /**
     * Calculate profit (dry run or instant feedback)
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'quantity_bags' => 'nullable|numeric|min:1',
            'bag_weight_kg' => 'nullable|numeric|min:1',
            'purchase_price_etb_per_kg' => 'nullable|numeric|min:0',
            'purchase_cost_etb' => 'nullable|numeric|min:0',
            'processing_cost_etb_per_bag' => 'nullable|numeric|min:0',
            'packaging_cost_etb_per_bag' => 'nullable|numeric|min:0',
            'transport_corridor_etb' => 'nullable|numeric|min:0',
            'handling_customs_etb' => 'nullable|numeric|min:0',
            'selling_price_usd_per_lb' => 'nullable|numeric|min:0.01',
            'exchange_rate' => 'nullable|numeric|min:1',
            'freight_insurance_usd' => 'nullable|numeric|min:0',
            'incoterm' => 'nullable|string|in:FOB,CIF,CFR',
            'destination_port' => 'nullable|string',
        ]);

        $result = $this->coffeeService->calculateProfit($validated);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Save profit calculation to user workspace
     */
    public function saveCalculation(Request $request)
    {
        $validated = $request->validate([
            'deal_title' => 'required|string|max:255',
            'coffee_type' => 'required|string|max:255',
            'quantity_bags' => 'required|numeric|min:1',
            'bag_weight_kg' => 'nullable|numeric|min:1',
            'purchase_price_etb_per_kg' => 'nullable|numeric|min:0',
            'purchase_cost_etb' => 'nullable|numeric|min:0',
            'processing_cost_etb_per_bag' => 'nullable|numeric|min:0',
            'packaging_cost_etb_per_bag' => 'nullable|numeric|min:0',
            'transport_corridor_etb' => 'nullable|numeric|min:0',
            'handling_customs_etb' => 'nullable|numeric|min:0',
            'selling_price_usd_per_lb' => 'required|numeric|min:0.01',
            'exchange_rate' => 'required|numeric|min:1',
            'freight_insurance_usd' => 'nullable|numeric|min:0',
            'incoterm' => 'nullable|string|in:FOB,CIF,CFR',
            'destination_port' => 'nullable|string',
        ]);

        $calculation = $this->coffeeService->saveCalculation(Auth::id(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Export calculation saved successfully.',
            'data' => $calculation,
        ], 201);
    }

    /**
     * Get user's saved calculations
     */
    public function getCalculations()
    {
        $calculations = CoffeeExportCalculation::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $calculations,
        ]);
    }

    /**
     * Create export offer
     */
    public function createOffer(Request $request)
    {
        $validated = $request->validate([
            'exporter_name' => 'required|string|max:255',
            'exporter_contact' => 'nullable|string|max:255',
            'origin_region' => 'required|string|max:255',
            'sub_region' => 'nullable|string|max:255',
            'washing_station' => 'nullable|string|max:255',
            'grade' => 'required|string|max:100',
            'variety' => 'nullable|string|max:255',
            'processing_method' => 'required|string|max:100',
            'screen_size' => 'nullable|string|max:100',
            'cupping_score' => 'nullable|numeric|min:70|max:100',
            'moisture_content' => 'nullable|numeric|min:8|max:15',
            'crop_year' => 'required|string|max:50',
            'quantity_bags' => 'required|integer|min:1',
            'bag_type' => 'nullable|string|max:255',
            'price_usd_per_lb' => 'required|numeric|min:0.01',
            'incoterm' => 'required|string|max:50',
            'port_of_loading' => 'nullable|string|max:100',
            'port_of_destination' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'validity_date' => 'required|date',
            'tasting_notes' => 'nullable|string',
            'additional_terms' => 'nullable|string',
        ]);

        $offer = $this->coffeeService->createOffer(Auth::id(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Coffee export offer created successfully.',
            'data' => $offer,
        ], 201);
    }

    /**
     * Get user's export offers
     */
    public function getOffers()
    {
        $offers = CoffeeExportOffer::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    /**
     * Get single offer details
     */
    public function getOffer(int $id)
    {
        $offer = CoffeeExportOffer::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $offer,
        ]);
    }

    /**
     * Stream or download Offer PDF
     */
    public function downloadOfferPdf(int $id)
    {
        $offer = CoffeeExportOffer::where('user_id', Auth::id())
            ->findOrFail($id);

        $pdf = $this->coffeeService->renderOfferPdf($offer);

        return $pdf->download('Offer-' . $offer->reference_no . '.pdf');
    }

    /**
     * Submit Readiness Assessment
     */
    public function assessReadiness(Request $request)
    {
        $answers = $request->validate([
            'has_commercial_reg' => 'nullable',
            'has_ecta_export_cert' => 'nullable',
            'has_nbe_code' => 'nullable',
            'has_tax_clearance' => 'nullable',
            'has_direct_vertical_contracts' => 'nullable',
            'has_traceability_system' => 'nullable',
            'has_washing_station_access' => 'nullable',
            'has_q_grader_access' => 'nullable',
            'has_moisture_meter' => 'nullable',
            'has_screen_sizing_equipment' => 'nullable',
            'has_freight_forwarder_partner' => 'nullable',
            'knows_container_stuffing_protocols' => 'nullable',
            'knows_phytosanitary_procedures' => 'nullable',
            'understands_nbe_forex_retention' => 'nullable',
            'understands_lc_cad_terms' => 'nullable',
            'has_buyer_contract_templates' => 'nullable',
        ]);

        $assessment = $this->coffeeService->evaluateReadiness(Auth::id(), $answers);

        return response()->json([
            'success' => true,
            'message' => 'Export readiness assessment evaluated successfully.',
            'data' => $assessment,
        ]);
    }

    /**
     * Get user assessments history
     */
    public function getAssessments()
    {
        $assessments = CoffeeReadinessAssessment::where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }

    /**
     * Market intelligence benchmarks
     */
    public function getMarketIntelligence()
    {
        $benchmarks = CoffeeMarketBenchmark::latest('market_date')->get();

        return response()->json([
            'success' => true,
            'data' => $benchmarks,
        ]);
    }
}
