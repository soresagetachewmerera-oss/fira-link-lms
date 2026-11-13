<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MarketplaceSavedListing Model
 *
 * Pivot model for bookmarked/saved coffee listings.
 */
class MarketplaceSavedListing extends Model
{
    use HasFactory;

    protected $table = 'marketplace_saved_listings';

    protected $fillable = [
        'user_id',
        'listing_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }
}

