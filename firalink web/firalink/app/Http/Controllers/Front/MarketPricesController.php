<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\MarketSeries;
use App\Services\Market\MarketReadService;
use App\Services\Market\MarketSeriesService;
use App\Services\Market\MarketWatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Public coffee market page. Uses the same MarketReadService as GET /api/market/overview, and the
 * page's script reads /api/market/overview and /api/market/series: one source of truth for web and app.
 */
class MarketPricesController extends Controller
{
    public function __construct(private readonly MarketReadService $market) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MarketSeries::class);
        $watch = app(MarketWatchService::class)->build();
        $detail = $this->detailFor($request, $watch);

        $overview = $this->market->overview();

        // Imported ECTA price schedule (admin upload of the official file): shown with its source and date.
        $latestPriceDataset = CoffeeMinimumPriceDataset::where('status', 'published')->latest('effective_date')->first();
        $priceRecords = $latestPriceDataset
            ? $latestPriceDataset->records()->where('status', 'valid')->take(12)->get()
            : collect();

        return view('market-prices.index', [
            'overview' => $overview,
            'watch' => $watch,
            'detail' => $detail,
            'latestPriceDataset' => $latestPriceDataset,
            'priceRecords' => $priceRecords,
            'pageTitle' => __('market.web.title') . ' | Fira Link',
            'metaDescription' => __('market.web.meta'),
        ]);
    }

    /** The detail panel alone (HTML), for market-watch.js to swap in without a page load. */
    public function panel(Request $request): View
    {
        Gate::authorize('viewAny', MarketSeries::class);

        return view('market-prices.panel', ['detail' => $this->detailFor($request, app(MarketWatchService::class)->build())]);
    }

    /** Selected row (?market=, else the first row with a price), with ?range= and ?unit= when they are offered. */
    private function detailFor(Request $request, array $watch): ?array
    {
        $priced = collect($watch['rows'])->filter(fn (array $r) => $r['price'] !== null);
        $key = $priced->firstWhere('key', (string) $request->query('market'))['key'] ?? $priced->first()['key'] ?? null;
        if ($key === null) {
            return null;
        }
        $row = $priced->firstWhere('key', $key);
        $range = in_array($request->query('range'), $row['ranges'], true) ? $request->query('range') : '1M';
        $unit = in_array($request->query('unit'), $row['units'], true) ? $request->query('unit') : null;
        $series = MarketSeries::query()->where('symbol', $key)->with('source')->firstOrFail();

        return app(MarketSeriesService::class)->build($series, $range, $unit);
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
