<?php

namespace App\Console\Commands;

use App\Models\MarketSeries;
use App\Models\User;
use App\Services\Market\ManualPriceService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/** Types in a price for a source without a feed (the NBE rate). It waits for a second person's review. */
class MarketEnterPriceCommand extends Command
{
    protected $signature = 'market:enter-price
        {symbol : Series symbol, for example ETB_USD}
        {value : The value exactly as published}
        {--date= : Date the source published it (YYYY-MM-DD)}
        {--source-url= : https link where the value was published}
        {--user= : Email of the market reviewer entering it}';

    protected $description = 'Enter a published price by hand for review (sources without a feed)';

    public function handle(ManualPriceService $manual): int
    {
        $series = MarketSeries::query()->where('symbol', $this->argument('symbol'))->first();
        $user = User::query()->where('email', (string) $this->option('user'))->first();
        if (!$series || !$user) {
            $this->error(!$series ? 'Unknown series symbol.' : 'Unknown user: pass --user=<reviewer email>.');

            return self::FAILURE;
        }

        try {
            $price = $manual->submit($series, (string) $this->argument('value'), (string) $this->option('date'), (string) $this->option('source-url'), $user);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                $this->error(implode(' ', $messages));
            }

            return self::FAILURE;
        }
        $this->info("Saved {$series->symbol} {$price->value} for {$price->source_time->toDateString()}. It waits in Admin -> Coffee market sources for another reviewer.");

        return self::SUCCESS;
    }
}
