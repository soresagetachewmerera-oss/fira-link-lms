<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MarketplaceQuotation Model
 *
 * Represents an exporter's binding or indicative commercial offer in response to an RFQ or listing inquiry.
 */
class MarketplaceQuotation extends Model
{
    use HasFactory;

    protected $table = 'marketplace_quotations';

    protected $fillable = [
        'rfq_id',
        'listing_id',
        'seller_id',
        'quote_number',
        'offered_price_usd_per_lb',
        'total_price_usd',
        'quantity_bags',
        'incoterm',
        'port_of_loading',
        'port_of_destination',
        'payment_terms',
        'estimated_shipping_date',
        'validity_date',
        'terms_and_conditions',
        'sample_available',
        'status',
        'accepted_at',
        'pdf_path',
    ];

    protected $casts = [
        'offered_price_usd_per_lb' => 'decimal:2',
        'total_price_usd' => 'decimal:2',
        'quantity_bags' => 'integer',
        'sample_available' => 'boolean',
        'estimated_shipping_date' => 'date',
        'validity_date' => 'date',
        'accepted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = 'QUO-' . date('Y') . '-' . strtoupper(Str::random(6));
            }

            // Update RFQ quotes count if attached
            if ($quote->rfq_id) {
                MarketplaceRfq::where('id', $quote->rfq_id)->increment('quotes_count');
            }
        });
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(MarketplaceRfq::class, 'rfq_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}

