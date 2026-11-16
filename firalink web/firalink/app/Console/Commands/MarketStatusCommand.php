<?php

namespace App\Console\Commands;

use App\Models\MarketSeries;
use App\Services\Market\MarketReadService;
use Illuminate\Console\Command;

class MarketStatusCommand extends Command
{
    protected $signature = 'market:status';
    protected $description = 'Show freshness for every registered market series';

    public function handle(MarketReadService $market): int
    {
        $rows = MarketSeries::query()->with(['source', 'trend'])->orderBy('sort_order')->get()->map(function ($series) use ($market) {
            $data = $market->serializeSeries($series);
            return [$data['symbol'], $series->source->enabled ? 'yes' : 'no', $data['value'] ?? '—', $data['freshness']['status'], $data['source_time'] ?? '—', $series->source->last_error ?? '—'];
        });
        $this->table(['Series', 'Enabled', 'Value', 'Freshness', 'Source time', 'Last error'], $rows);
        return self::SUCCESS;
    }
}
