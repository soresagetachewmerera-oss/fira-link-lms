<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketPriceAlert;
use App\Models\MarketSeries;
use App\Models\User;
use App\Notifications\Catalog\Channel;
use App\Notifications\Catalog\NotificationType;
use App\Services\Notifications\Notifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Price alerts: "tell me when <instrument> is above/below <threshold>" in the instrument's stored unit.
 * Checked whenever a price is published. An alert fires once when the latest published price reaches its
 * side of the threshold and re-arms when the price goes back. Only published, current prices count.
 */
class MarketAlertService
{
    public function __construct(private readonly MarketFreshnessService $freshness) {}

    public function create(User $user, MarketSeries $series, string $direction, float $threshold, array $channels): MarketPriceAlert
    {
        if (MarketPriceAlert::query()->where('user_id', $user->id)->count() >= MarketPriceAlert::MAX_PER_USER) {
            throw ValidationException::withMessages(['threshold' => __('market.watch.account.limit', ['max' => MarketPriceAlert::MAX_PER_USER])]);
        }
        $alert = new MarketPriceAlert(['direction' => $direction, 'threshold' => $threshold, 'channels' => array_values(array_unique($channels))]);
        $alert->user_id = $user->id;
        $alert->series_id = $series->id;
        $alert->active = true;
        // Start from where the price is now: an alert never fires for the price it was created at.
        $latest = $this->currentPrice($series);
        $alert->last_side = $latest === null ? null : ($this->hits($alert, (float) $latest->value) ? 'hit' : 'clear');
        $alert->save();

        return $alert;
    }

    /** @return int alerts sent */
    public function evaluate(MarketSeries $series): int
    {
        $latest = $this->currentPrice($series);
        if ($latest === null) {
            return 0;
        }
        $sent = 0;
        $alerts = MarketPriceAlert::query()->where('series_id', $series->id)->where('active', true)->with('user')->get();
        foreach ($alerts as $alert) {
            $hit = $this->hits($alert, (float) $latest->value);
            $wasHit = $alert->last_side === 'hit';
            $alert->last_side = $hit ? 'hit' : 'clear';
            if ($hit && !$wasHit && $alert->user) {
                $alert->triggered_at = now();
                $sent += $this->notify($alert, $series, $latest) ? 1 : 0;
            }
            $alert->save();
        }

        return $sent;
    }

    private function hits(MarketPriceAlert $alert, float $value): bool
    {
        return $alert->direction === 'above' ? $value >= (float) $alert->threshold : $value <= (float) $alert->threshold;
    }

    private function currentPrice(MarketSeries $series): ?MarketPrice
    {
        $series->loadMissing('source');
        $latest = $series->prices()->where('status', 'published')->latest('source_time')->first();
        if ($latest === null || in_array($this->freshness->for($series, $latest)['status'], ['unavailable', 'stale'], true)) {
            return null; // an old price never triggers an alert
        }

        return $latest;
    }

    private function notify(MarketPriceAlert $alert, MarketSeries $series, MarketPrice $price): bool
    {
        $channels = [];
        foreach ((array) $alert->channels as $choice) {
            if ($choice === 'push') {
                array_push($channels, Channel::Push, Channel::Database);
            } elseif ($choice === 'mail') {
                $channels[] = Channel::Mail;
            }
        }
        $decimals = (int) ($series->decimals ?? 2);
        $locale = in_array($alert->user->locale ?? null, ['en', 'am'], true) ? $alert->user->locale : 'en';
        $unitKey = $series->currency . '_' . $series->unit;
        try {
            return app(Notifier::class)->send($alert->user, NotificationType::MarketPriceAlert, [
                'name' => \Illuminate\Support\Facades\Lang::has("market.watch.short.{$series->symbol}", $locale) ? __("market.watch.short.{$series->symbol}", [], $locale) : ($series->short_name ?: $series->name),
                'value' => number_format((float) $price->value, $decimals, '.', ','),
                'threshold' => number_format((float) $alert->threshold, $decimals, '.', ','),
                'unit' => __("market.watch.units.{$unitKey}", [], $locale),
                'direction' => __("market.watch.account.{$alert->direction}", [], $locale),
                'date' => $price->source_time->toDateString(),
                'market_key' => $series->symbol,
            ], 'market_alert:' . $alert->id . ':' . $price->id, array_values(array_unique($channels)));
        } catch (\Throwable $e) {
            Log::warning('market.alert_send_failed', ['alert' => $alert->id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
