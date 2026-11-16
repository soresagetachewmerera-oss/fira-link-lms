<?php

namespace App\Console\Commands;

use App\Services\Market\MarketIngestionService;
use Illuminate\Console\Command;

class MarketFetchCommand extends Command
{
    protected $signature = 'market:fetch {source? : Source slug} {--dry-run : Fetch and validate without writing}';
    protected $description = 'Fetch configured coffee market sources';

    public function handle(MarketIngestionService $ingestion): int
    {
        $results = $ingestion->fetch($this->argument('source'), (bool) $this->option('dry-run'));
        foreach ($results as $result) {
            $this->line(sprintf('%s: %s', $result['source'], $result['status']));
            foreach ($result['rows'] ?? [] as $row) {
                $this->line(sprintf('  %s %s %s/%s | source time %s | %s%s', $row['symbol'], $row['value'], $row['currency'], $row['unit'], $row['source_time'], $row['status'], $row['reason'] ? ' — '.$row['reason'] : ''));
            }
            if (isset($result['error'])) $this->error('  '.$result['error']);
        }
        return collect($results)->contains(fn ($result) => $result['status'] === 'failed') ? self::FAILURE : self::SUCCESS;
    }
}
