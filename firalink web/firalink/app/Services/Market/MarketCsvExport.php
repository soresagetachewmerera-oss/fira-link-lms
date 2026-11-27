<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV of the stored, published closes of one instrument (signed-in people only).
 * One row per day: date, value, currency, unit, source, status. Nothing that is held or rejected.
 */
class MarketCsvExport
{
    public const RANGES = ['1M' => 1, '3M' => 3, '1Y' => 12, '5Y' => 60];

    public function response(MarketSeries $series, string $range): StreamedResponse
    {
        $series->loadMissing('source');
        $months = self::RANGES[$range] ?? 12;
        $since = now()->subMonths($months)->startOfDay();
        $lastPerDay = $series->prices()->where('status', 'published')->where('source_time', '>=', $since)
            ->selectRaw('DATE(source_time) as day, MAX(source_time) as last_time')->groupBy('day')->pluck('last_time');
        $prices = $lastPerDay->isEmpty() ? collect() : $series->prices()->where('status', 'published')
            ->whereIn('source_time', $lastPerDay->all())->orderBy('source_time')->get(['value', 'source_time']);
        $filename = sprintf('%s-%s-%s.csv', strtolower($series->symbol), strtolower($range), now()->format('Ymd'));
        $source = (string) $series->source->attribution;

        return response()->streamDownload(function () use ($prices, $series, $source) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'value', 'currency', 'unit', 'source', 'note']);
            foreach ($prices as $p) {
                /** @var MarketPrice $p */
                fputcsv($out, [$p->source_time->toDateString(), (string) $p->value + 0, $series->currency, $series->unit, $source, '']);
            }
            fputcsv($out, ['', '', '', '', '', __('market.watch.disclaimer')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
