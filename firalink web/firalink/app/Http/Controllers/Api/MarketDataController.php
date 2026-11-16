<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Services\Market\MarketReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MarketDataController extends Controller
{
    public function __construct(private readonly MarketReadService $market) {}

    public function overview(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MarketSeries::class);
        $this->setLocale($request);
        return response()->json(['success' => true, 'data' => $this->market->overview()])
            ->header('Cache-Control', 'public, max-age=60, stale-if-error=300');
    }

    public function series(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:64'],
            'range' => ['required', 'in:1D,1W,1M,3M,1Y'],
        ]);
        $series = MarketSeries::query()->where('symbol', $validated['symbol'])->where('enabled', true)->firstOrFail();
        Gate::authorize('view', $series);
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
