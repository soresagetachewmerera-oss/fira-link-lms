<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;

class MarketValidationService
{
    public function validate(MarketSeries $series, array $quote): array
    {
        if (!isset($quote['value']) || !is_numeric($quote['value'])) {
            return ['status' => 'rejected', 'reason' => 'Value is not numeric.'];
        }
        $value = (float) $quote['value'];
        if ($value <= 0) {
            return ['status' => 'rejected', 'reason' => 'Value must be positive.'];
        }
        if (($quote['currency'] ?? null) !== $series->currency || ($quote['unit'] ?? null) !== $series->unit) {
            return ['status' => 'rejected', 'reason' => 'Source currency or unit does not match the registered series.'];
        }
        if ($series->minimum_value !== null && $value < (float) $series->minimum_value) {
            return ['status' => 'needs_review', 'reason' => 'Value is below the configured range.'];
        }
        if ($series->maximum_value !== null && $value > (float) $series->maximum_value) {
            return ['status' => 'needs_review', 'reason' => 'Value is above the configured range.'];
        }

        $previous = MarketPrice::query()->where('series_id', $series->id)->where('status', 'published')->latest('source_time')->first();
        if ($previous && (float) $previous->value > 0) {
            $change = abs(($value - (float) $previous->value) / (float) $previous->value) * 100;
            if ($change > (float) $series->maximum_change_percent) {
                return ['status' => 'needs_review', 'reason' => sprintf('Change %.4f%% exceeds the configured %.4f%% limit.', $change, (float) $series->maximum_change_percent)];
            }
        }

        $crossSymbol = $series->configuration['cross_check_symbol'] ?? null;
        if ($crossSymbol) {
            $reference = MarketSeries::query()->where('symbol', $crossSymbol)->first()?->prices()->where('status', 'published')->latest('source_time')->first();
            $tolerance = (float) ($series->configuration['cross_check_tolerance_percent'] ?? 25);
            if ($reference && (float) $reference->value > 0) {
                $difference = abs(($value - (float) $reference->value) / (float) $reference->value) * 100;
                if ($difference > $tolerance) {
                    return ['status' => 'needs_review', 'reason' => sprintf('Cross-check differs by %.4f%%, above %.4f%%.', $difference, $tolerance)];
                }
            }
        }

        return ['status' => 'published', 'reason' => null];
    }
}
