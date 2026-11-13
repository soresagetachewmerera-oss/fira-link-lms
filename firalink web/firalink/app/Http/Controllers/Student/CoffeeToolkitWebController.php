<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CoffeeExportCalculation;
use App\Models\CoffeeExportOffer;
use App\Models\CoffeeReadinessAssessment;
use App\Models\CoffeeMarketBenchmark;
use App\Models\Course;
use App\Services\CoffeeExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoffeeToolkitWebController extends Controller
{
    protected $coffeeService;

    public function __construct(CoffeeExportService $coffeeService)
    {
        $this->coffeeService = $coffeeService;
    }

    /**
     * Toolkit Hub landing page
     */
    public function index()
    {
        $user = Auth::user();
        $recentCalculations = CoffeeExportCalculation::where('user_id', $user->id)->latest()->take(5)->get();
        $recentOffers = CoffeeExportOffer::where('user_id', $user->id)->latest()->take(5)->get();
        $latestAssessment = CoffeeReadinessAssessment::where('user_id', $user->id)->latest()->first();
        $marketBenchmarks = CoffeeMarketBenchmark::latest('market_date')->take(5)->get();
        
        $tradeCourses = Course::where('is_published', true)
            ->latest()
            ->take(4)
            ->get();

        return view('student.coffee-toolkit.index', compact(
            'user',
            'recentCalculations',
            'recentOffers',
            'latestAssessment',
            'marketBenchmarks',
            'tradeCourses'
        ));
    }

    /**
     * Profit Calculator page
     */
    public function calculator()
    {
        $user = Auth::user();
        $calculations = CoffeeExportCalculation::where('user_id', $user->id)->latest()->paginate(10);
        $benchmarks = CoffeeMarketBenchmark::latest('market_date')->take(5)->get();

        return view('student.coffee-toolkit.calculator', compact('user', 'calculations', 'benchmarks'));
    }

    /**
     * Save calculation via web form
     */
    public function storeCalculation(Request $request)
    {
        $validated = $request->validate([
            'deal_title' => 'required|string|max:255',
            'coffee_type' => 'required|string|max:255',
            'quantity_bags' => 'required|numeric|min:1',
            'bag_weight_kg' => 'nullable|numeric|min:1',
            'purchase_price_etb_per_kg' => 'nullable|numeric|min:0',
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Deal calculation saved successfully.',
                'data' => $calculation,
            ]);
        }

        return redirect()->route('student.coffee-toolkit.calculator')
            ->with('success', 'Deal calculation saved successfully.');
    }

    /**
     * Offer Generator page
     */
    public function offerGenerator()
    {
        $user = Auth::user();
        $offers = CoffeeExportOffer::where('user_id', $user->id)->latest()->paginate(10);

        return view('student.coffee-toolkit.offer-generator', compact('user', 'offers'));
    }

    /**
     * Store offer via web
     */
    public function storeOffer(Request $request)
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Export offer generated successfully.',
                'data' => $offer,
            ]);
        }

        return redirect()->route('student.coffee-toolkit.offer-generator')
            ->with('success', 'Coffee export offer generated successfully.');
    }

    /**
     * Download / Stream Offer PDF
     */
    public function downloadOfferPdf(int $id)
    {
        $offer = CoffeeExportOffer::where('user_id', Auth::id())->findOrFail($id);
        $pdf = $this->coffeeService->renderOfferPdf($offer);

        return $pdf->stream('Offer-' . $offer->reference_no . '.pdf');
    }

    /**
     * Readiness Assessment page
     */
    public function readiness()
    {
        $user = Auth::user();
        $latestAssessment = CoffeeReadinessAssessment::where('user_id', $user->id)->latest()->first();
        $assessments = CoffeeReadinessAssessment::where('user_id', $user->id)->latest()->take(5)->get();

        return view('student.coffee-toolkit.readiness', compact('user', 'latestAssessment', 'assessments'));
    }

    /**
     * Store assessment
     */
    public function storeAssessment(Request $request)
    {
        $answers = $request->except(['_token']);
        $assessment = $this->coffeeService->evaluateReadiness(Auth::id(), $answers);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Readiness assessment completed successfully.',
                'data' => $assessment,
            ]);
        }

        return redirect()->route('student.coffee-toolkit.readiness')
            ->with('success', 'Readiness assessment evaluated.');
    }

    /**
     * Market Intelligence page
     */
    public function marketIntelligence()
    {
        $user = Auth::user();
        $benchmarks = CoffeeMarketBenchmark::latest('market_date')->get();

        return view('student.coffee-toolkit.market-intelligence', compact('user', 'benchmarks'));
    }

    /**
     * Documents Assistant page
     */
    public function documents()
    {
        $user = Auth::user();
        $recentOffers = CoffeeExportOffer::where('user_id', $user->id)->latest()->take(10)->get();

        return view('student.coffee-toolkit.documents', compact('user', 'recentOffers'));
    }
}
