<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bags of a lot held for one accepted quote. Written only by App\Services\Trade\StockService.
 */
class StockReservation extends Model
{
    public const STATUS_HELD = 'held';           // accepted, contract not signed yet; expires
    public const STATUS_COMMITTED = 'committed'; // both signed: the bags are sold
    public const STATUS_RELEASED = 'released';   // expired or cancelled: bags returned to the lot

    public const ACTIVE = [self::STATUS_HELD, self::STATUS_COMMITTED];

    protected $guarded = ['*'];

    protected $casts = [
        'bags' => 'integer',
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id')->withTrashed();
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(MarketplaceQuotation::class, 'quotation_id');
    }

    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', self::ACTIVE);
    }
}
