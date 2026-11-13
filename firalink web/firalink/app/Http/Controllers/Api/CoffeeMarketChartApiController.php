<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoffeeTradingChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoffeeMarketChartApiController extends Controller
{
    protected CoffeeTradingChartService $chartService;

    public function __construct(CoffeeTradingChartService $chartService)
    {
        $this->chartService = $chartService;
    }

    /**
     * Get Candlestick Chart Data
     */
    public function chartData(Request $request): JsonResponse
    {
        $market = $request->get('market', 'ethiopia');
        $timeframe = $request->get('timeframe', '1d');
        $origin = $request->get('origin', 'composite');

        $data = $this->chartService->getChartData($market, $timeframe, $origin);

        return response()->json($data, 200, [
            'Cache-Control' => 'public, max-age=15',
        ]);
    }

    /**
     * Get Real-Time Tick
     */
    public function liveTick(Request $request): JsonResponse
    {
        $market = $request->get('market', 'ethiopia');
        $origin = $request->get('origin', 'composite');

        $tick = $this->chartService->getLiveTick($market, $origin);

        return response()->json($tick);
    }

    /**
     * Multi-Market Overview Ticker
     */
    public function ticker(): JsonResponse
    {
        $arabica = $this->chartService->getChartData('arabica', '1d')['summary'];
        $robusta = $this->chartService->getChartData('robusta', '1d')['summary'];
        $ethiopia = $this->chartService->getChartData('ethiopia', '1d', 'composite')['summary'];

        return response()->json([
            'success' => true,
            'markets' => [
                'arabica' => $arabica,
                'robusta' => $robusta,
                'ethiopia' => $ethiopia,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

