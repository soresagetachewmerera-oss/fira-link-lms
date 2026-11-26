<?php

namespace App\Console\Commands;

use App\Services\Trade\StockService;
use Illuminate\Console\Command;

/**
 * Release 1 (D1): an accepted quote holds its bags for DEAL_RESERVATION_HOLD_DAYS. When the contract is
 * not signed by then, the bags go back to the lot and an unsigned deal room is cancelled (audited).
 */
class ReleaseExpiredStockHolds extends Command
{
    protected $signature = 'marketplace:release-expired-holds';

    protected $description = 'Return bags held by accepted quotes whose contract was not signed in time';

    public function handle(StockService $stock): int
    {
        $count = $stock->releaseExpired();
        $this->info("Released {$count} expired hold(s).");

        return self::SUCCESS;
    }
}
