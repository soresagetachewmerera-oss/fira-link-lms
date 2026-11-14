<?php

namespace App\Console\Commands;

use App\Models\CoffeeListing;
use Illuminate\Console\Command;

/**
 * ONE-TIME report after slice 4. Lots must belong to a business; lots without one are hidden
 * from buyers and cannot be edited by anyone. Report only: decide each lot by hand.
 */
class ReportLotsWithoutBusiness extends Command
{
    protected $signature = 'marketplace:report-lots-without-business';

    protected $description = 'Report only: lots that do not belong to a business workspace';

    public function handle(): int
    {
        $lots = CoffeeListing::withTrashed()->whereNull('organization_id')->with('seller:id,name,email')->orderBy('id')->get();

        $this->table(['Lot id', 'Lot code', 'Title', 'Status', 'Created by (user id)', 'Created', 'Deleted'], $lots->map(fn ($l) => [
            $l->id,
            $l->lot_code,
            mb_strimwidth((string) $l->title, 0, 40, '…'),
            $l->status,
            $l->seller_id,
            $l->created_at?->toDateString(),
            $l->deleted_at ? 'yes' : '',
        ])->all());

        $this->info("{$lots->count()} lot(s) without a business. Nothing was changed.");

        return self::SUCCESS;
    }
}
