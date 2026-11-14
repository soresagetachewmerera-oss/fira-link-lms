<?php

namespace App\Console\Commands;

use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use Illuminate\Console\Command;

/**
 * ONE-TIME report after slice 5. Requests and quotes now belong to businesses. Requests without a
 * business are hidden from sellers; quotes without a lot cannot be accepted. Report only.
 */
class ReportTradeWithoutBusiness extends Command
{
    protected $signature = 'marketplace:report-trade-without-business';

    protected $description = 'Report only: requests and quotes made before they belonged to a business';

    public function handle(): int
    {
        $rfqs = MarketplaceRfq::withTrashed()->whereNull('organization_id')->orderBy('id')->get(['id', 'rfq_number', 'title', 'status', 'buyer_id', 'created_at']);
        $this->info('Requests without a business (hidden from sellers):');
        $this->table(['Id', 'Number', 'Title', 'Status', 'Created by (user id)', 'Created'], $rfqs->map(fn ($r) => [
            $r->id, $r->rfq_number, mb_strimwidth((string) $r->title, 0, 40, '…'), $r->status, $r->buyer_id, $r->created_at?->toDateString(),
        ])->all());

        $quotes = MarketplaceQuotation::where(fn ($q) => $q->whereNull('organization_id')->orWhereNull('listing_id'))->orderBy('id')
            ->get(['id', 'quote_number', 'rfq_id', 'listing_id', 'status', 'seller_id']);
        $this->info('Quotes without a business or a lot (cannot be accepted):');
        $this->table(['Id', 'Number', 'Request id', 'Lot id', 'Status', 'Created by (user id)'], $quotes->map(fn ($q) => [
            $q->id, $q->quote_number, $q->rfq_id, $q->listing_id ?? '—', $q->status, $q->seller_id,
        ])->all());

        $this->info("{$rfqs->count()} request(s), {$quotes->count()} quote(s). Nothing was changed.");

        return self::SUCCESS;
    }
}
