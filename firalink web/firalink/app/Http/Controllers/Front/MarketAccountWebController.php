<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MarketPriceAlert;
use App\Models\MarketSeries;
use App\Models\MarketWatchlistItem;
use App\Services\Market\MarketAlertService;
use App\Services\Market\MarketCsvExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Market Prices page, signed-in actions (web session, routes under auth): watchlist, price alerts,
 * CSV export, and the sign-in hop that brings a guest back to the same instrument.
 */
class MarketAccountWebController extends Controller
{
    private function series(string $key): MarketSeries
    {
        $series = MarketSeries::query()->where('symbol', $key)->where('enabled', true)->with('source')->firstOrFail();
        Gate::authorize('view', $series);

        return $series;
    }

    private function back(string $key): RedirectResponse
    {
        return redirect()->route('market-prices.index', ['market' => $key]);
    }

    /** Guests reach this through the login page (auth middleware remembers it) and land on the same instrument. */
    public function signIn(Request $request): RedirectResponse
    {
        $key = (string) $request->query('market');

        return MarketSeries::query()->where('symbol', $key)->exists() ? $this->back($key) : redirect()->route('market-prices.index');
    }

    public function toggleWatch(Request $request, string $key): RedirectResponse
    {
        $series = $this->series($key);
        $query = MarketWatchlistItem::query()->where('user_id', $request->user()->id)->where('series_id', $series->id);
        if ($query->exists()) {
            $query->delete();
            $message = __('market.watch.account.watch_removed');
        } else {
            (new MarketWatchlistItem())->forceFill(['user_id' => $request->user()->id, 'series_id' => $series->id])->save();
            $message = __('market.watch.account.watch_added');
        }

        return $this->back($key)->with('success', $message);
    }

    public function storeAlert(Request $request, string $key, MarketAlertService $service): RedirectResponse
    {
        Gate::authorize('create', MarketPriceAlert::class);
        $data = $request->validate([
            'direction' => ['required', 'in:' . implode(',', MarketPriceAlert::DIRECTIONS)],
            'threshold' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['in:' . implode(',', MarketPriceAlert::CHANNELS)],
        ]);
        $service->create($request->user(), $this->series($key), $data['direction'], (float) $data['threshold'], $data['channels']);

        return $this->back($key)->with('success', __('market.watch.account.alert_saved'));
    }

    public function destroyAlert(MarketPriceAlert $alert): RedirectResponse
    {
        Gate::authorize('delete', $alert);
        $key = $alert->series->symbol;
        $alert->delete();

        return $this->back($key)->with('success', __('market.watch.account.alert_deleted'));
    }

    public function export(Request $request, string $key, MarketCsvExport $export): StreamedResponse
    {
        $range = in_array($request->query('range'), array_keys(MarketCsvExport::RANGES), true) ? $request->query('range') : '1Y';

        return $export->response($this->series($key), $range);
    }
}
