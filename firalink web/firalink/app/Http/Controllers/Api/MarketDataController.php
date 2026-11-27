<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Services\Market\HeroOverviewService;
use App\Services\Market\MarketReadService;
use App\Services\Market\MarketWatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MarketDataController extends Controller
{
    public function __construct(
        private readonly MarketReadService $market,
        private readonly HeroOverviewService $heroOverview,
        private readonly MarketWatchService $watch,
    ) {}

    /** Market watch rows, schema 1. Public, cached, with an ETag so unchanged data costs a 304. */
    public function watch(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MarketSeries::class);
        $this->setLocale($request);
        // Keep 250.0 a decimal in JSON so typed clients (Dart doubles) never receive an int.
        $response = response()->json(['success' => true, 'data' => $this->watch->build()], 200, [], JSON_PRESERVE_ZERO_FRACTION);
        $response->setEtag(sha1($response->getContent()))
            ->header('Cache-Control', 'public, max-age=' . (int) config('market.watch.http_max_age_seconds', 300) . ', stale-if-error=600')
            ->header('Vary', 'Accept-Language');
        // Turns the response into an empty 304 when the client already has this version.
        $response->isNotModified($request);

        return $response;
    }

    public function overview(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MarketSeries::class);
        $this->setLocale($request);
        $payload = $this->market->overview() + [
            'site_hero' => $this->heroOverview->build(),
        ];

        return response()->json(['success' => true, 'data' => $payload])
            ->header('Cache-Control', 'public, max-age=300, stale-if-error=600');
    }

    public function series(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:64'],
            'range' => ['required', 'in:1D,1W,1M,3M,1Y'],
        ]);
        $series = MarketSeries::query()->where('symbol', $validated['symbol'])->where('enabled', true)->with('source')->firstOrFail();
        Gate::authorize('view', $series);
        // 1D only for an intraday instrument whose source is on; a daily source has one point per day.
        if ($validated['range'] === '1D' && !($series->supports_intraday && $series->source?->enabled)) {
            return response()->json(['success' => false, 'code' => 'RANGE_UNAVAILABLE', 'message' => __('market.watch.range_unavailable')], 422);
        }
        $this->setLocale($request);
        return response()->json(['success' => true, 'data' => $this->market->series($series, $validated['range'])])
            ->header('Cache-Control', 'public, max-age=60, stale-if-error=300');
    }

    public function sources(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MarketSource::class);
        $this->setLocale($request);
        return response()->json(['success' => true, 'data' => $this->market->sources()])
            ->header('Cache-Control', 'public, max-age=300, stale-if-error=900');
    }

    /** Old generated-price endpoints: never a number, always "unavailable" with a pointer to the sourced data. */
    public function retired(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MarketSeries::class);
        $this->setLocale($request);

        return response()->json([
            'success' => false,
            'status' => 'unavailable',
            'message' => __('market.legacy_unavailable'),
            'use' => url('/api/market/overview'),
        ], 410);
    }

    private function setLocale(Request $request): void
    {
        $requested = strtolower(substr((string) $request->header('Accept-Language', 'en'), 0, 2));
        app()->setLocale(in_array($requested, ['en', 'am'], true) ? $requested : 'en');
    }
}
