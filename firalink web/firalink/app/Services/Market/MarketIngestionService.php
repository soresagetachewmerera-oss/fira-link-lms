<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Notifications\Catalog\NotificationType;
use App\Services\Market\Adapters\ApiNinjasCoffeeAdapter;
use App\Services\Market\Adapters\IcoDailyAdapter;
use App\Services\Market\Adapters\MarketSourceAdapter;
use App\Services\Market\Adapters\NbeDailyAdapter;
use App\Services\Notifications\Notifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetches each market source, validates every value and stores it with the source's own time.
 * Only "published" rows are ever shown; suspicious rows wait in needs_review, rejected rows are logged.
 * Dry run: fetch and validate, write nothing (also for a disabled source named explicitly).
 */
class MarketIngestionService
{
    public const ADAPTERS = [
        'api_ninjas' => ApiNinjasCoffeeAdapter::class,
        'ico' => IcoDailyAdapter::class,
        'nbe' => NbeDailyAdapter::class,
    ];

    public function __construct(
        private readonly MarketValidationService $validator,
        private readonly MarketTrendService $trends,
    ) {}

    /** @return array<int, array> one result per source */
    public function fetch(?string $slug = null, bool $dryRun = false): array
    {
        if ($slug !== null) {
            return [$this->fetchSource(MarketSource::query()->where('slug', $slug)->firstOrFail(), $dryRun)];
        }

        $results = MarketSource::query()->where('enabled', true)->orderBy('id')->get()
            ->filter(fn (MarketSource $source) => $this->isDue($source))
            ->map(fn (MarketSource $source) => $this->fetchSource($source, $dryRun))
            ->values()->all();
        if (!$dryRun) {
            $this->alertOutages();
        }

        return $results;
    }

    /**
     * Tells the staff mailbox (config market.admin_email) once per outage when an automatic source has had no
     * successful fetch for market.outage_cycles refresh intervals and its last attempt failed.
     * Sources without a fetch cycle (typed in by an admin, ECTA lists, no feed) are not checked.
     *
     * @return array<int, string> slugs alerted now
     */
    public function alertOutages(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $cycles = max(2, (int) config('market.outage_cycles', 2));
        $alerted = [];

        foreach (MarketSource::query()->where('enabled', true)->orderBy('id')->get() as $source) {
            if (!isset(self::ADAPTERS[$source->adapter]) || $source->last_attempt_at === null || $source->last_error === null) {
                continue;
            }
            if (($source->configuration['skip_weekends'] ?? false) && $now->isWeekend()) {
                continue;
            }
            $threshold = $now->subMinutes($cycles * max(1, (int) $source->refresh_minutes));
            if ($source->last_success_at !== null && $source->last_success_at->gte($threshold)) {
                continue;
            }
            // Already told about this outage (no success since the last alert).
            if ($source->alerted_at !== null && ($source->last_success_at === null || $source->alerted_at->gt($source->last_success_at))) {
                continue;
            }

            $since = $source->last_success_at ? $source->last_success_at->copy()->utc()->format('Y-m-d H:i') . ' UTC' : '—';
            Log::error('market.source_down', ['source' => $source->slug, 'last_success_at' => $since, 'error' => $source->last_error]);
            $email = config('market.admin_email');
            if ($email) {
                app(Notifier::class)->sendToAddress('mail', $email, NotificationType::StaffMarketSourceDown, [
                    'source' => $source->name,
                    'since' => $since,
                    'error' => Str::limit((string) $source->last_error, 200),
                    'action_url' => route('admin.market-data.index'),
                ], 'market_source_down:' . $source->slug . ':' . $now->format('YmdHi'));
            } else {
                Log::warning('market.admin_email is not set; source outage alert not sent.', ['source' => $source->slug]);
            }
            $source->forceFill(['alerted_at' => now()])->save();
            $alerted[] = $source->slug;
        }

        return $alerted;
    }

    public function isDue(MarketSource $source, ?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now('UTC');
        if (($source->configuration['skip_weekends'] ?? false) && $now->isWeekend()) {
            return false;
        }

        return $source->last_attempt_at === null
            || $source->last_attempt_at->lte($now->subMinutes(max(1, (int) $source->refresh_minutes))->addSeconds(30));
    }

    public function fetchSource(MarketSource $source, bool $dryRun = false): array
    {
        $result = ['source' => $source->slug, 'status' => 'success', 'rows' => [], 'dry_run' => $dryRun];

        if (!$dryRun && !$source->enabled) {
            return ['status' => 'disabled'] + $result;
        }
        if ($source->adapter === EctaScheduleSync::SOURCE) {
            // ECTA minimum prices come from the price lists admins publish, not from a remote feed.
            $result['rows'] = app(EctaScheduleSync::class)->syncAll($dryRun);
            if (!$dryRun) {
                $source->forceFill(['last_attempt_at' => now(), 'last_success_at' => now(), 'last_error' => null])->save();
            }

            return $result;
        }
        if ($source->adapter === 'manual') {
            return ['status' => 'manual', 'error' => 'Prices for this source are typed in by an admin and reviewed (market:enter-price).'] + $result;
        }
        $adapter = $this->adapter($source);
        if (!$adapter) {
            return ['status' => 'unavailable', 'error' => 'No official machine-readable feed is configured for this source.'] + $result;
        }

        $lock = Cache::lock('market:fetch:' . $source->slug, 120);
        if (!$lock->get()) {
            return ['status' => 'locked'] + $result;
        }

        try {
            try {
                $quotes = $adapter->fetch($source);
            } catch (Throwable $e) {
                $error = Str::limit($e->getMessage(), 500);
                if (!$dryRun) {
                    $source->forceFill(['last_attempt_at' => now(), 'last_error' => $error])->save();
                }
                Log::warning('market.fetch_failed', ['source' => $source->slug, 'error' => $error]);

                return ['status' => 'failed', 'error' => $error] + $result;
            }

            $touched = [];
            foreach ($quotes as $quote) {
                $row = $this->ingestQuote($source, $quote, $dryRun);
                $result['rows'][] = $row;
                if (!$dryRun && in_array($row['status'], ['published', 'needs_review'], true)) {
                    $touched[$row['symbol']] = true;
                }
            }

            if (!$dryRun) {
                $source->forceFill(['last_attempt_at' => now(), 'last_success_at' => now(), 'last_error' => null])->save();
                MarketSeries::query()->where('source_id', $source->id)->whereIn('symbol', array_keys($touched))->get()
                    ->each(fn (MarketSeries $series) => $this->trends->compute($series));
                MarketReadService::forgetCache();
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function ingestQuote(MarketSource $source, array $quote, bool $dryRun): array
    {
        $row = [
            'symbol' => (string) ($quote['symbol'] ?? ''),
            'value' => isset($quote['value']) ? (string) $quote['value'] : '',
            'currency' => (string) ($quote['currency'] ?? ''),
            'unit' => (string) ($quote['unit'] ?? ''),
            'source_time' => (string) ($quote['source_time'] ?? ''),
            'status' => 'rejected',
            'reason' => null,
        ];

        $series = MarketSeries::query()->where('source_id', $source->id)->where('symbol', $row['symbol'])->where('enabled', true)->first();
        if (!$series) {
            return $this->reject($source, $row, 'Unknown or disabled series for this source.');
        }

        try {
            $sourceTime = CarbonImmutable::parse($row['source_time'])->utc();
        } catch (Throwable) {
            return $this->reject($source, $row, 'Source time is not a valid date.');
        }
        if ($sourceTime->gt(now()->addMinutes(10))) {
            return $this->reject($source, $row, 'Source time is in the future.');
        }

        $check = $this->validator->validate($series, $quote);
        $row['status'] = $check['status'];
        $row['reason'] = $check['reason'];
        if ($check['status'] === 'rejected') {
            return $this->reject($source, $row, $check['reason']);
        }

        if (MarketPrice::query()->where('series_id', $series->id)->where('source_time', $sourceTime)->exists()) {
            return ['status' => 'duplicate', 'reason' => 'Already stored for this source time.'] + $row;
        }
        if ($dryRun) {
            return $row;
        }

        MarketPrice::query()->create([
            'series_id' => $series->id,
            'value' => $row['value'],
            'currency' => $row['currency'],
            'unit' => $row['unit'],
            'source_time' => $sourceTime,
            'fetched_at' => now(),
            'status' => $check['status'],
            'raw_ref' => $quote['raw_ref'] ?? null,
            'review_reason' => $check['reason'],
        ]);
        if ($check['status'] === 'needs_review') {
            Log::warning('market.needs_review', ['source' => $source->slug, 'symbol' => $row['symbol'], 'value' => $row['value'], 'reason' => $check['reason']]);
        }

        return $row;
    }

    private function reject(MarketSource $source, array $row, string $reason): array
    {
        Log::warning('market.rejected', ['source' => $source->slug, 'symbol' => $row['symbol'], 'value' => $row['value'], 'reason' => $reason]);

        return ['status' => 'rejected', 'reason' => $reason] + $row;
    }

    private function adapter(MarketSource $source): ?MarketSourceAdapter
    {
        $class = self::ADAPTERS[$source->adapter] ?? null;

        return $class ? app($class) : null;
    }
}
