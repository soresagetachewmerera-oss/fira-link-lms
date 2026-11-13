<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MarketplaceInquiry Model
 *
 * Represents direct trade inquiries and green coffee sample requests.
 */
class MarketplaceInquiry extends Model
{
    use HasFactory;

    protected $table = 'marketplace_inquiries';

    protected $fillable = [
        'listing_id',
        'seller_id',
        'buyer_id',
        'inquiry_type',
        'subject',
        'message',
        'quantity_interested_bags',
        'shipping_address',
        'courier_account',
        'sample_status',
        'tracking_number',
        'status',
    ];

    protected $casts = [
        'quantity_interested_bags' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($inquiry) {
            if ($inquiry->listing_id) {
                CoffeeListing::where('id', $inquiry->listing_id)->increment('inquiries_count');
            }
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}

