<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketPriceAlert;
use App\Models\MarketSeries;
use App\Models\MarketWatchlistItem;
use App\Services\Market\MarketAlertService;
use App\Services\Market\MarketCsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Market watch, signed-in features (auth:sanctum): watchlist, price alerts, CSV export. */
class MarketAccountController extends Controller
{
    private function series(string $key): MarketSeries
    {
        $series = MarketSeries::query()->where('symbol', $key)->where('enabled', true)->with('source')->firstOrFail();
        Gate::authorize('view', $series);

        return $series;
    }

    public function watchlist(Request $request): JsonResponse
    {
        $keys = MarketWatchlistItem::query()->where('user_id', $request->user()->id)->with('series')->orderBy('id')->get()
            ->pluck('series.symbol')->filter()->values();

        return response()->json(['success' => true, 'data' => ['keys' => $keys]]);
    }

    public function watch(Request $request, string $key): JsonResponse
    {
        $series = $this->series($key);
        $exists = MarketWatchlistItem::query()->where('user_id', $request->user()->id)->where('series_id', $series->id)->exists();
        if (!$exists) {
            (new MarketWatchlistItem())->forceFill(['user_id' => $request->user()->id, 'series_id' => $series->id])->save();
        }

        return $this->watchlist($request);
    }

    public function unwatch(Request $request, string $key): JsonResponse
    {
        $series = $this->series($key);
        MarketWatchlistItem::query()->where('user_id', $request->user()->id)->where('series_id', $series->id)->delete();

        return $this->watchlist($request);
    }

    public function alerts(Request $request): JsonResponse
    {
        $alerts = MarketPriceAlert::query()->where('user_id', $request->user()->id)->with('series')->latest()->get()
            ->map(fn (MarketPriceAlert $a) => self::present($a))->values();

        return response()->json(['success' => true, 'data' => ['alerts' => $alerts, 'max' => MarketPriceAlert::MAX_PER_USER]]);
    }

    public function storeAlert(Request $request, MarketAlertService $service): JsonResponse
    {
        Gate::authorize('create', MarketPriceAlert::class);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:40'],
            'direction' => ['required', 'in:' . implode(',', MarketPriceAlert::DIRECTIONS)],
            'threshold' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['in:' . implode(',', MarketPriceAlert::CHANNELS)],
        ]);
        $alert = $service->create($request->user(), $this->series($data['key']), $data['direction'], (float) $data['threshold'], $data['channels']);

        return response()->json(['success' => true, 'data' => self::present($alert->load('series'))], 201);
    }

    public function destroyAlert(MarketPriceAlert $alert): Response
    {
        Gate::authorize('delete', $alert);
        $alert->delete();

        return response()->noContent();
    }

    public function export(Request $request, string $key, MarketCsvExport $export): StreamedResponse
    {
        $range = in_array($request->query('range'), array_keys(MarketCsvExport::RANGES), true) ? $request->query('range') : '1Y';

        return $export->response($this->series($key), $range);
    }

    public static function present(MarketPriceAlert $a): array
    {
        $unitKey = $a->series->currency . '_' . $a->series->unit;

        return [
            'id' => $a->id,
            'key' => $a->series->symbol,
            'short_name' => \Illuminate\Support\Facades\Lang::has("market.watch.short.{$a->series->symbol}") ? __("market.watch.short.{$a->series->symbol}") : ($a->series->short_name ?: $a->series->name),
            'direction' => $a->direction,
            'threshold' => round((float) $a->threshold, (int) $a->series->decimals),
            'unit_label' => __("market.watch.units.{$unitKey}"),
            'channels' => $a->channels,
            'active' => (bool) $a->active,
            'triggered_at' => $a->triggered_at?->toIso8601String(),
        ];
    }
}
