<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Models\User;
use App\Services\Market\ManualPriceService;
use App\Services\Market\MarketIngestionService;
use App\Services\Market\MarketTrendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MarketDataAdminController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', MarketSource::class);
        return view('admin.market-data.index', [
            'sources' => MarketSource::query()->with('series')->orderBy('name')->get(),
            'reviews' => $reviews = MarketPrice::query()->where('status', 'needs_review')->with('series.source')->latest('source_time')->paginate(30),
            'manualSeries' => MarketSeries::query()->where('enabled', true)
                ->whereHas('source', fn ($q) => $q->where('adapter', 'manual'))->orderBy('sort_order')->get(),
            'enteredBy' => User::query()->whereIn('id', collect($reviews->items())
                ->map(fn (MarketPrice $p) => (int) ($p->raw_ref['entered_by'] ?? 0))->filter()->unique()->all())->pluck('name', 'id'),
        ]);
    }

    /** Type in a published price for a source without a feed (the NBE rate). It waits for another reviewer. */
    public function enter(Request $request, ManualPriceService $manual): RedirectResponse
    {
        Gate::authorize('create', MarketPrice::class);
        $validated = $request->validate([
            'series_id' => ['required', 'integer', 'exists:market_series,id'],
            'value' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date_format:Y-m-d'],
            'source_url' => ['required', 'url', 'starts_with:https://', 'max:500'],
        ]);
        $manual->submit(MarketSeries::query()->findOrFail($validated['series_id']), (string) $validated['value'], $validated['date'], $validated['source_url'], $request->user());

        return back()->with('success', __('market.admin.entered'));
    }

    public function toggle(MarketSource $source): RedirectResponse
    {
        Gate::authorize('manage', $source);
        $before = $source->toArray();
        $source->update(['enabled' => !$source->enabled]);
        AuditLog::log('market_source_toggled', $source, $before, $source->fresh()->toArray());
        $this->clearCache();
        return back()->with('success', __('market.admin.source_updated'));
    }

    public function fetch(MarketSource $source, MarketIngestionService $ingestion): RedirectResponse
    {
        Gate::authorize('manage', $source);
        $result = $ingestion->fetchSource($source);
        AuditLog::log('market_source_manual_fetch', $source, [], ['result' => $result['status']]);
        return back()->with($result['status'] === 'success' ? 'success' : 'error', $result['status'] === 'success' ? __('market.admin.fetch_complete') : __('market.admin.fetch_failed'));
    }

    public function approve(Request $request, MarketPrice $price, MarketTrendService $trends): RedirectResponse
    {
        Gate::authorize('review', $price);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        abort_unless($price->status === 'needs_review', 409, __('market.admin.already_reviewed'));
        $before = $price->toArray();
        $price->update(['status' => 'published', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => $validated['reason']]);
        $trends->compute($price->series);
        AuditLog::log('market_price_approved', $price, $before, $price->fresh()->toArray());
        $this->clearCache();
        return back()->with('success', __('market.admin.price_approved'));
    }

    public function reject(Request $request, MarketPrice $price): RedirectResponse
    {
        Gate::authorize('review', $price);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        abort_unless($price->status === 'needs_review', 409, __('market.admin.already_reviewed'));
        $before = $price->toArray();
        $price->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => $validated['reason']]);
        AuditLog::log('market_price_rejected', $price, $before, $price->fresh()->toArray());
        $this->clearCache();
        return back()->with('success', __('market.admin.price_rejected'));
    }

    private function clearCache(): void
    {
        \App\Services\Market\MarketReadService::forgetCache();
    }
}
