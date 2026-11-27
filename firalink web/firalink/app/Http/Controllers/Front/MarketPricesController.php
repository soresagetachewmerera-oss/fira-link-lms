<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\MarketSeries;
use App\Services\Market\MarketReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Public coffee market page. Uses the same MarketReadService as GET /api/market/overview, and the
 * page's script reads /api/market/overview and /api/market/series: one source of truth for web and app.
 */
class MarketPricesController extends Controller
{
    public function __construct(private readonly MarketReadService $market) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MarketSeries::class);

        $overview = $this->market->overview();

        // Imported ECTA price schedule (admin upload of the official file): shown with its source and date.
        $latestPriceDataset = CoffeeMinimumPriceDataset::where('status', 'published')->latest('effective_date')->first();
        $priceRecords = $latestPriceDataset
            ? $latestPriceDataset->records()->where('status', 'valid')->take(12)->get()
            : collect();

        return view('market-prices.index', [
            'overview' => $overview,
            'watch' => app(\App\Services\Market\MarketWatchService::class)->build(),
            // 1D only when an intraday instrument's source is on (the API refuses 1D otherwise).
            'intradayAvailable' => MarketSeries::query()->where('enabled', true)->where('supports_intraday', true)
                ->whereHas('source', fn ($q) => $q->where('enabled', true))->exists(),
            'latestPriceDataset' => $latestPriceDataset,
            'priceRecords' => $priceRecords,
            'pageTitle' => __('market.web.title') . ' | Fira Link',
            'metaDescription' => __('market.web.meta'),
        ]);
    }

    /** The old web chart and tick endpoints generated prices at random. They now answer "unavailable". */
    public function chartData(): JsonResponse
    {
        return $this->retired();
    }

    public function liveTick(): JsonResponse
    {
        return $this->retired();
    }

    private function retired(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => 'unavailable',
            'message' => __('market.legacy_unavailable'),
            'use' => url('/api/market/overview'),
        ], 410);
    }
}
