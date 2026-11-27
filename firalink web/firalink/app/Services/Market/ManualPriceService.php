<?php

namespace App\Services\Market;

use App\Models\AuditLog;
use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Prices typed in by an admin for a source without a feed (the NBE birr/US dollar rate).
 * Every entry needs the https link where the value was published, is validated like a fetched value and
 * always waits in the review queue: a second person approves it (MarketPricePolicy), never the one who typed it.
 */
class ManualPriceService
{
    public function __construct(private readonly MarketValidationService $validator) {}

    public function submit(MarketSeries $series, string $value, string $date, string $sourceUrl, User $user): MarketPrice
    {
        $series->loadMissing('source');
        if ($series->source?->adapter !== 'manual') {
            throw ValidationException::withMessages(['symbol' => 'This series is fetched automatically; prices cannot be typed in.']);
        }
        if (!$user->can('review-verifications')) {
            throw ValidationException::withMessages(['user' => 'Only market reviewers may enter prices.']);
        }
        if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !str_starts_with(strtolower($sourceUrl), 'https://')) {
            throw ValidationException::withMessages(['source_url' => 'Give the https link where this value was published.']);
        }
        try {
            $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, 'UTC');
        } catch (Throwable) {
            $day = false;
        }
        if (!$day || $day->format('Y-m-d') !== $date) {
            throw ValidationException::withMessages(['date' => 'Use the date the source published the value, as YYYY-MM-DD.']);
        }
        if ($day->gt(CarbonImmutable::now('UTC')->startOfDay())) {
            throw ValidationException::withMessages(['date' => 'The date cannot be in the future.']);
        }
        if ($series->prices()->where('source_time', $day)->whereIn('status', ['published', 'needs_review'])->exists()) {
            throw ValidationException::withMessages(['date' => 'A value for this date is already stored or waiting for review.']);
        }

        $check = $this->validator->validate($series, ['value' => $value, 'currency' => $series->currency, 'unit' => $series->unit]);
        if ($check['status'] === 'rejected') {
            throw ValidationException::withMessages(['value' => $check['reason']]);
        }

        $price = MarketPrice::query()->updateOrCreate(
            ['series_id' => $series->id, 'source_time' => $day],
            [
                'value' => $value, 'currency' => $series->currency, 'unit' => $series->unit,
                'fetched_at' => now(), 'status' => 'needs_review',
                'raw_ref' => ['entered_by' => $user->id, 'source_url' => $sourceUrl, 'entered_at' => now()->toIso8601String()],
                'review_reason' => $check['reason'] ?? 'Typed in by hand: a second person checks it against the source link.',
                'reviewed_by' => null, 'reviewed_at' => null,
            ],
        );
        AuditLog::log('market_price_entered', $price, [], $price->toArray(), null, $user->id);

        return $price;
    }
}
