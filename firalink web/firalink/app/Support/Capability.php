<?php

namespace App\Support;

/**
 * What a business does in coffee trade (release 2a). Policies check these, never a category name.
 * A category lists the activities a business of that kind may declare; a declared activity counts
 * only when the business is Tier 1, not suspended, and holds the activity's required documents
 * (config/verification.php → capability_documents).
 *
 * Exporter-to-exporter ("wholesale") trade is not here: no source confirms it is allowed
 * (docs/product/07_coffee_b2b_decisions.md).
 */
enum Capability: string
{
    case Produce = 'produce';
    case Aggregate = 'aggregate';
    case Process = 'process';
    case SellDomestic = 'sell_domestic';
    case SellExport = 'sell_export';
    case BuyDomestic = 'buy_domestic';
    case BuyExport = 'buy_export';

    /** Activities that sell coffee (lots, payout account, seller home). */
    public const SELLING = ['sell_domestic', 'sell_export'];

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Today's rights of a business that has no category yet, from its role (unchanged behavior):
     * exporters and suppliers list export lots, buyers post requests, brokers do both.
     *
     * @return list<string>
     */
    public static function forLegacyRole(string $role): array
    {
        return match ($role) {
            'exporter', 'supplier' => ['sell_export'],
            'broker' => ['sell_export', 'buy_export'],
            'buyer' => ['buy_export'],
            default => [],
        };
    }
}
