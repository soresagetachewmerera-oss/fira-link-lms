<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Services\CoffeeTradingChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketPricesController extends Controller
{
    protected CoffeeTradingChartService $chartService;

    public function __construct(CoffeeTradingChartService $chartService)
    {
        $this->chartService = $chartService;
    }

    /**
     * Dedicated Market Prices & Institutional Coffee Trading Terminal Page
     */
    public function index(Request $request): View
    {
        $market = $request->get('market', 'ethiopia');
        $timeframe = $request->get('timeframe', '1d');
        $origin = $request->get('origin', 'composite');

        // Initial pre-loaded payload for instant zero-latency chart render
        $chartData = $this->chartService->getChartData($market, $timeframe, $origin);

        // Fetch official ECTA schedule data for contextual regulatory table
        $latestPriceDataset = CoffeeMinimumPriceDataset::where('status', 'published')
            ->latest('effective_date')
            ->first();

        $priceRecords = $latestPriceDataset 
            ? $latestPriceDataset->records()->where('status', 'valid')->take(12)->get()
            : collect();

        $pageTitle = 'Live Coffee Trading Charts & Market Prices | Fira Link Institutional Terminal';
        $metaDescription = 'Professional real-time financial candlestick charts for Ethiopian coffee, ICE Arabica (KC), and ICE Robusta (RC). Live OHLC price discovery, regional ECTA floors, and commodity technical indicators.';

        return view('market-prices.index', compact(
            'chartData',
            'latestPriceDataset',
            'priceRecords',
            'pageTitle',
            'metaDescription',
            'market',
            'timeframe',
            'origin'
        ));
    }

    /**
     * API endpoint: Fetch historical candlestick & volume series
     */
    public function chartData(Request $request): JsonResponse
    {
        $market = $request->get('market', 'arabica');
        $timeframe = $request->get('timeframe', '1d');
        $origin = $request->get('origin', 'composite');

        $data = $this->chartService->getChartData($market, $timeframe, $origin);

        return response()->json($data, 200, [
            'Cache-Control' => 'public, max-age=15',
        ]);
    }

    /**
     * API endpoint: Lightweight live tick for real-time OHLC active candle update
     */
    public function liveTick(Request $request): JsonResponse
    {
        $market = $request->get('market', 'arabica');
        $origin = $request->get('origin', 'composite');

        $tick = $this->chartService->getLiveTick($market, $origin);

        return response()->json($tick, 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}

