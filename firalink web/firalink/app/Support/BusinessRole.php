<?php

namespace App\Support;

/**
 * What a business does in the marketplace (design S-103 "Your role").
 * Seller roles can list lots once verified; buyers post requests.
 */
enum BusinessRole: string
{
    case Exporter = 'exporter';
    case Supplier = 'supplier';
    case Broker = 'broker';
    case Buyer = 'buyer';

    /** Roles whose approved verification makes a business visible to buyers. */
    public const SELLER_ROLES = ['exporter', 'supplier', 'broker'];

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isSeller(): bool
    {
        return in_array($this->value, self::SELLER_ROLES, true);
    }
}
