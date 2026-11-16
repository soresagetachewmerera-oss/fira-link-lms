<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CoffeeExportCalculation;
use App\Models\CoffeeExportOffer;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\Course;
use App\Services\CoffeeExportService;
use App\Services\CoffeeMarketService;
use App\Services\CoffeeTradingChartService;
use App\Services\CoffeeMarketSynthesisService;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class FreeToolsController extends Controller
{
    protected CoffeeMarketService $marketService;
    protected CoffeeExportService $exportService;
    protected CoffeeMarketSynthesisService $synthesisService;
    protected GeminiService $geminiService;
    protected CoffeeTradingChartService $chartService;

    public function __construct(
        CoffeeMarketService $marketService,
        CoffeeExportService $exportService,
        CoffeeMarketSynthesisService $synthesisService,
        GeminiService $geminiService,
        CoffeeTradingChartService $chartService
    ) {
        $this->marketService = $marketService;
        $this->exportService = $exportService;
        $this->synthesisService = $synthesisService;
        $this->geminiService = $geminiService;
        $this->chartService = $chartService;
    }

    /**
     * Render the main Free Tools page.
     */
    public function index()
    {
        // Sourced market prices only (MarketReadService, same as /api/market/overview).
        // The old CoffeeMarketService data estimated Ethiopian FOB prices; it is no longer shown.
        $marketOverview = app(\App\Services\Market\MarketReadService::class)->overview();
        $marketData = [];

        // Get latest published Coffee Minimum Price Dataset
        $latestPriceDataset = CoffeeMinimumPriceDataset::where('status', 'published')
            ->latest('effective_date')
            ->first();

        $coffeePricesRecords = $latestPriceDataset 
            ? $latestPriceDataset->records()->where('status', 'valid')->orderBy('origin')->orderBy('grade')->get()
            : collect();

        $coffeePricesSnapshot = $latestPriceDataset 
            ? $latestPriceDataset->computeSnapshot()
            : null;

        // Pre-computed / cached market synthesis
        $marketSynthesis = $latestPriceDataset
            ? ($latestPriceDataset->ai_analysis ?: $this->synthesisService->synthesize($latestPriceDataset))
            : null;

        // Academy trade courses for cross-linking
        $tradeCourses = Course::where('is_published', true)
            ->latest()
            ->take(3)
            ->get(['id', 'title', 'slug', 'thumbnail', 'price', 'level']);

        $brandName = config('seo.brand_name', 'Fira Link Academy');
        $pageTitle = 'Free Trade Tools & Market Intelligence | ' . $brandName;
        $metaDescription = 'Free international trade suite for Ethiopian commerce: live coffee export benchmark pricing, official ECTA minimum prices, Djibouti corridor costing, tariff calculator, and Fira AI market synthesis.';
        $breadcrumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Free Tools', 'url' => '/free-tools'],
        ];

        $savedCalculationsCount = Auth::check() 
            ? CoffeeExportCalculation::where('user_id', Auth::id())->count() 
            : 0;

        return view('free-tools', compact(
            'marketData',
            'marketOverview',
            'latestPriceDataset',
            'coffeePricesRecords',
            'coffeePricesSnapshot',
            'marketSynthesis',
            'tradeCourses',
            'savedCalculationsCount',
            'pageTitle',
            'metaDescription',
            'breadcrumbs'
        ));
    }

    /** Retired market feed (see below). */
    public function marketData(Request $request): JsonResponse
    {
        // The old 5-second feed returned estimated Ethiopian FOB prices and fixed fallbacks.
        // Sourced prices are at /api/market/overview; this endpoint now answers "unavailable".
        return response()->json([
            'success' => false,
            'status' => 'unavailable',
            'message' => __('market.legacy_unavailable'),
            'use' => url('/api/market/overview'),
        ], 410);
    }

    /**
     * Fetch coffee minimum prices data with search, sorting, and filters.
     */
    public function coffeePricesData(Request $request): JsonResponse
    {
        $dataset = CoffeeMinimumPriceDataset::where('status', 'published')
            ->latest('effective_date')
            ->first();

        if (!$dataset) {
            return response()->json([
                'success'  => false,
                'message'  => 'No published coffee minimum price dataset currently available.',
                'records'  => [],
                'snapshot' => null,
            ]);
        }

        $query = $dataset->records()->where('status', 'valid');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('coffee_type', 'like', "%{$s}%")
                  ->orWhere('origin', 'like', "%{$s}%")
                  ->orWhere('grade', 'like', "%{$s}%")
                  ->orWhere('processing_method', 'like', "%{$s}%");
            });
        }

        if ($request->filled('origin') && $request->origin !== 'all') {
            $query->where('origin', $request->origin);
        }

        if ($request->filled('coffee_type') && $request->coffee_type !== 'all') {
            $query->where('coffee_type', 'like', "%{$request->coffee_type}%");
        }

        if ($request->filled('grade') && $request->grade !== 'all') {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('process') && $request->process !== 'all') {
            $query->where('processing_method', $request->process);
        }

        // Sorting
        $sortField = $request->input('sort', 'origin');
        $sortDir = $request->input('direction', 'asc');
        $allowedSorts = ['origin', 'grade', 'minimum_price', 'processing_method', 'effective_date', 'coffee_type'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('origin')->orderBy('grade');
        }

        $records = $query->paginate(25);

        return response()->json([
            'success'  => true,
            'dataset'  => [
                'id'             => $dataset->id,
                'title'          => $dataset->title,
                'source'         => $dataset->source,
                'effective_date' => $dataset->effective_date?->format('d M Y'),
                'updated_at'     => $dataset->updated_at?->format('d M Y'),
                'version'        => $dataset->version ?? 'v1.0',
                'file_name'      => $dataset->file_name,
            ],
            'snapshot' => $dataset->computeSnapshot(),
            'records'  => $records,
        ]);
    }

    /**
     * Export Coffee Minimum Prices as CSV
     */
    public function exportCoffeePrices(Request $request)
    {
        $dataset = CoffeeMinimumPriceDataset::where('status', 'published')
            ->latest('effective_date')
            ->first();

        if (!$dataset) {
            return redirect()->back()->with('error', 'No dataset available to export.');
        }

        $records = $dataset->records()->where('status', 'valid')->orderBy('origin')->orderBy('grade')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Coffee_Minimum_Prices_' . ($dataset->effective_date?->format('Y-m-d') ?? date('Y-m-d')) . '.csv"',
        ];

        $callback = function () use ($records, $dataset) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Coffee Specification', 'Origin', 'Grade', 'Processing Method', 'Minimum Price (USD/lb)', 'Currency', 'Previous Price', 'Change', 'Movement', 'Effective Date', 'Source']);

            foreach ($records as $r) {
                fputcsv($file, [
                    $r->coffee_type,
                    $r->origin,
                    $r->grade,
                    $r->processing_method,
                    $r->minimum_price,
                    $r->currency,
                    $r->previous_price ?? 'N/A',
                    $r->price_change ?? '0',
                    $r->price_change_direction,
                    $r->effective_date?->format('Y-m-d') ?? '',
                    $dataset->source,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate or fetch structured Fira AI market synthesis.
     * Analyzes actual imported Coffee Minimum Price records and macro indicators.
     */
    public function aiAnalysis(Request $request): JsonResponse
    {
        // Rate limit AI requests: 20 per minute per IP/user
        $key = 'ai-market-analysis:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Please wait {$seconds} seconds before requesting new AI analysis.",
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $force = $request->boolean('force', false);
            $datasetId = $request->input('dataset_id');

            $dataset = $datasetId
                ? CoffeeMinimumPriceDataset::find($datasetId)
                : CoffeeMinimumPriceDataset::where('status', 'published')->latest('effective_date')->first();

            $analysis = $this->synthesisService->synthesize($dataset, $force);

            return response()->json($analysis);
        } catch (\Throwable $e) {
            Log::warning('FreeToolsController::aiAnalysis error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'AI analysis is temporarily unavailable: ' . $e->getMessage(),
                'cached'  => false,
            ], 500);
        }
    }

    /**
     * Perform deterministic Coffee Export Profit calculation.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quantity_bags'              => 'nullable|numeric|min:1',
            'bag_weight_kg'              => 'nullable|numeric|min:1',
            'purchase_price_etb_per_kg'  => 'nullable|numeric|min:0',
            'purchase_cost_etb'          => 'nullable|numeric|min:0',
            'processing_cost_etb_per_bag'=> 'nullable|numeric|min:0',
            'packaging_cost_etb_per_bag' => 'nullable|numeric|min:0',
            'transport_corridor_etb'     => 'nullable|numeric|min:0',
            'handling_customs_etb'       => 'nullable|numeric|min:0',
            'selling_price_usd_per_lb'   => 'nullable|numeric|min:0.01',
            'exchange_rate'              => 'nullable|numeric|min:1',
            'freight_insurance_usd'      => 'nullable|numeric|min:0',
            'incoterm'                   => 'nullable|string|in:FOB,CIF,CFR',
            'destination_port'           => 'nullable|string|max:100',
        ]);

        $result = $this->exportService->calculateProfit($validated);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Save calculation to logged-in user's workspace.
     */
    public function saveCalculation(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please create a free account or sign in to save calculations to your workspace.',
                'requires_auth' => true,
            ], 401);
        }

        $validated = $request->validate([
            'deal_title'                 => 'nullable|string|max:100',
            'coffee_type'                => 'nullable|string|max:100',
            'quantity_bags'              => 'required|numeric|min:1',
            'bag_weight_kg'              => 'nullable|numeric|min:1',
            'selling_price_usd_per_lb'   => 'required|numeric|min:0.01',
            'exchange_rate'              => 'nullable|numeric|min:1',
            'purchase_price_etb_per_kg'  => 'nullable|numeric|min:0',
            'transport_corridor_etb'     => 'nullable|numeric|min:0',
            'handling_customs_etb'       => 'nullable|numeric|min:0',
            'freight_insurance_usd'      => 'nullable|numeric|min:0',
            'incoterm'                   => 'nullable|string|in:FOB,CIF,CFR',
            'destination_port'           => 'nullable|string|max:100',
        ]);

        $saved = $this->exportService->saveCalculation(Auth::id(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Calculation saved to your student workspace.',
            'id'      => $saved->id,
        ]);
    }

    /**
     * Ask Fira AI Export Advisor a specialized export question.
     */
    public function askAdvisor(Request $request): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip() ?: '127.0.0.1';
        $key = 'ai-ask-advisor:' . ($user ? 'user:' . $user->id : 'ip:' . $ip);
        $maxAttempts = $user ? 20 : 5; // 5 requests/min for guests, 20/min for authenticated users

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Please wait {$seconds} seconds before asking another question.",
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $reply = $this->geminiService->generateContent($validated['question']);

        return response()->json([
            'success' => true,
            'reply'   => $reply,
        ]);
    }
}
