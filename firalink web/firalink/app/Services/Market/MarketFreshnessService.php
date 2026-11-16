<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;

class MarketFreshnessService
{
    public function for(MarketSeries $series, ?MarketPrice $price): array
    {
        if (!$price || !$series->source || !$series->source->enabled) {
            return ['status' => 'unavailable', 'delay_minutes' => null, 'label' => __('market.freshness.unavailable')];
        }

        $age = max(0, (int) $price->source_time->diffInMinutes(now()));
        $maxAge = max(1, (int) $series->source->max_age_minutes);

        if ($age > $maxAge * 3) {
            return ['status' => 'unavailable', 'delay_minutes' => $age, 'label' => __('market.freshness.unavailable')];
        }
        if ($age > $maxAge) {
            return ['status' => 'stale', 'delay_minutes' => $age, 'label' => __('market.freshness.stale', ['minutes' => $age])];
        }
        if (in_array($series->price_type, ['daily_close', 'weekly_regulatory_floor'], true)) {
            return ['status' => 'daily_close', 'delay_minutes' => $age, 'label' => __('market.freshness.daily_close', ['date' => $price->source_time->toDateString()])];
        }
        if (($series->source->configuration['real_time'] ?? false) === true) {
            return ['status' => 'live', 'delay_minutes' => $age, 'label' => __('market.freshness.live')];
        }

        return ['status' => 'delayed', 'delay_minutes' => $age, 'label' => __('market.freshness.delayed', ['minutes' => $age])];
    }
}
