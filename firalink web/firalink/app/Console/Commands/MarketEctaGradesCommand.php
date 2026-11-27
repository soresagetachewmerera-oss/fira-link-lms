<?php

namespace App\Console\Commands;

use App\Models\MarketSeries;
use App\Services\Market\EctaScheduleSync;
use Illuminate\Console\Command;

/**
 * Lists the grades in published ECTA minimum price lists and starts tracking one as a market watch row.
 * Tracking only creates the row; prices appear after `market:fetch ecta_lists` while the ECTA source is on.
 */
class MarketEctaGradesCommand extends Command
{
    protected $signature = 'market:ecta-grades
        {--track= : Start tracking "coffee type|origin|grade|processing method", as listed}
        {--home : Make the tracked grade the ECTA row on Home}';

    protected $description = 'List ECTA grades in published price lists, or track one as a market watch row';

    public function handle(EctaScheduleSync $ecta): int
    {
        $grades = $ecta->grades();
        $track = $this->option('track');

        if (!$track) {
            if ($grades->isEmpty()) {
                $this->warn('No published ECTA price list has valid records. Publish a list in Admin -> Coffee prices first.');

                return self::SUCCESS;
            }
            $tracked = MarketSeries::query()->pluck('on_home', 'symbol');
            $this->table(['Track as (--track=)', 'Latest', 'Effective', 'Row'], $grades->map(fn (array $g) => [
                $g['key'],
                $g['latest_price'] . ' ' . $g['price_unit'],
                $g['effective_date'],
                $tracked->has($g['symbol']) ? ($tracked[$g['symbol']] ? 'tracked, on Home' : 'tracked') : '—',
            ])->all());

            return self::SUCCESS;
        }

        $grade = $grades->firstWhere('key', trim($track));
        if (!$grade) {
            $this->error('That grade is not in any published list. Copy it exactly from the list above.');

            return self::FAILURE;
        }
        $series = $ecta->track($grade, (bool) $this->option('home'));
        $this->info("Tracking {$series->symbol}: {$series->name}" . ($series->on_home ? ' (Home row)' : ''));
        $this->line('Prices appear after `php artisan market:fetch ecta_lists` while the ECTA source is on.');

        return self::SUCCESS;
    }
}
