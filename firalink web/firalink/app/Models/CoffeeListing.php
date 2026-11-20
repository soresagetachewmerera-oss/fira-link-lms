<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * CoffeeListing Model
 *
 * Represents a green coffee export lot available for international/domestic buyers.
 */
class CoffeeListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coffee_listings';

    /** Lot evidence levels, lowest to highest. Set only by an admin review action. */
    public const EVIDENCE_LEVELS = ['claimed', 'documented', 'lab_backed', 'inspected'];

    /**
     * evidence_level is intentionally NOT fillable: no request may set it.
     * MarketplaceAdminController::reviewEvidenceLevel() uses forceFill().
     */
    protected $fillable = [
        'seller_id',
        'organization_id',
        'title',
        'slug',
        'origin_region',
        'sub_region',
        'washing_station',
        'coffee_type',
        'variety',
        'processing_method',
        'grade',
        'screen_size',
        'cupping_score',
        'evidence_document_path',
        'moisture_content',
        'water_activity',
        'crop_year',
        'altitude_min',
        'altitude_max',
        'total_quantity_bags',
        'available_quantity_bags',
        'minimum_order_bags',
        'bag_type',
        'packaging_type',
        'bag_weight_kg',
        'indicative_price_usd_per_lb',
        'indicative_price_usd_per_mt',
        'incoterm',
        'port_of_loading',
        'sample_available',
        'sample_cost_usd',
        'tasting_notes',
        'sensory_attributes',
        'description',
        'status',
        'is_featured',
        'featured_until',
        'expires_at',
        'views_count',
        'inquiries_count',
    ];

    // Never in JSON: a private file path, and the person behind the lot (their user record and
    // seller profile hold email, phone, TIN and document paths). Buyers see the business only.
    // seller_id is a user id (lets anyone enumerate accounts); buyers need the business, not the person.
    protected $hidden = ['evidence_document_path', 'seller', 'seller_profile', 'sellerProfile', 'seller_id'];

    /**
     * The business is always sent as BusinessSummary: the tier only while it is active and the business
     * is not suspended, and never the raw verification or legacy columns (is_verified, verified_roles,
     * verification_valid_until, ...). Auth gating 2026-11-20, S-11 / B-1.
     */
    public function toArray()
    {
        $array = parent::toArray();
        if ($this->relationLoaded('organization')) {
            $array['organization'] = \App\Support\BusinessSummary::of($this->organization);
        }

        return $array;
    }

    protected $casts = [
        'evidence_valid_until' => 'datetime',
        'published_at' => 'datetime',
        'cupping_score' => 'decimal:2',
        'moisture_content' => 'decimal:2',
        'water_activity' => 'decimal:2',
        'indicative_price_usd_per_lb' => 'decimal:2',
        'indicative_price_usd_per_mt' => 'decimal:2',
        'sample_cost_usd' => 'decimal:2',
        'bag_weight_kg' => 'decimal:2',
        'total_quantity_bags' => 'integer',
        'available_quantity_bags' => 'integer',
        'minimum_order_bags' => 'integer',
        'altitude_min' => 'integer',
        'altitude_max' => 'integer',
        'views_count' => 'integer',
        'inquiries_count' => 'integer',
        'sample_available' => 'boolean',
        'is_featured' => 'boolean',
        'featured_until' => 'datetime',
        'expires_at' => 'datetime',
        'tasting_notes' => 'array',
        'sensory_attributes' => 'array',
    ];

    /** Published lot statuses buyers can see (drafts, paused and archived lots never show). */
    public const BUYER_VISIBLE_STATUSES = ['active'];

    /**
     * Lots buyers may see: published, and the seller's business is Tier 1 or higher, still valid,
     * not suspended and a seller role. Lots without a business are hidden (fail closed).
     */
    public function scopeVisibleToBuyers($query)
    {
        return $query->whereIn('status', self::BUYER_VISIBLE_STATUSES)
            ->whereHas('organization', \App\Services\Verification\VerificationReviewService::approvedSellerConstraint());
    }

    public function isVisibleToBuyers(): bool
    {
        return in_array($this->status, self::BUYER_VISIBLE_STATUSES, true)
            && \App\Services\Verification\VerificationReviewService::isApprovedSeller($this->organization);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(LotEvidence::class, 'listing_id');
    }

    public static function generateLotCode(): string
    {
        $alphabet = \App\Services\Verification\FiraId::ALPHABET;
        do {
            $code = 'L-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::withTrashed()->where('lot_code', $code)->exists());

        return $code;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($listing) {
            if (empty($listing->slug) && !empty($listing->title)) {
                $listing->slug = Str::slug($listing->title) . '-' . Str::random(6);
            }
            if (empty($listing->lot_code)) {
                $listing->lot_code = static::generateLotCode();
            }
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(MarketplaceSample::class, 'listing_id');
    }

    public function sellerProfile()
    {
        return $this->hasOneThrough(
            MarketplaceSellerProfile::class,
            User::class,
            'id', // users.id
            'user_id', // marketplace_seller_profiles.user_id
            'seller_id', // coffee_listings.seller_id
            'id' // users.id
        );
    }

    public function media(): HasMany
    {
        return $this->hasMany(CoffeeListingMedia::class, 'listing_id')->orderBy('sort_order', 'asc');
    }

    public function primaryImage()
    {
        return $this->hasOne(CoffeeListingMedia::class, 'listing_id')
            ->where('media_type', 'image')
            ->orderByDesc('is_primary')
            ->oldest('sort_order');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(MarketplaceInquiry::class, 'listing_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(MarketplaceQuotation::class, 'listing_id');
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'marketplace_saved_listings', 'listing_id', 'user_id')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')->orWhere('featured_until', '>=', now());
            });
    }

    public function scopeFilterByOrigin($query, $origin)
    {
        if ($origin && $origin !== 'all') {
            return $query->where('origin_region', 'LIKE', "%{$origin}%");
        }
        return $query;
    }

    public function scopeFilterByProcess($query, $process)
    {
        if ($process && $process !== 'all') {
            return $query->where('processing_method', $process);
        }
        return $query;
    }

    public function scopeFilterByGrade($query, $grade)
    {
        if ($grade && $grade !== 'all') {
            return $query->where('grade', $grade);
        }
        return $query;
    }

    public function scopeFilterByMinScore($query, $minScore)
    {
        if ($minScore > 0) {
            return $query->where('cupping_score', '>=', $minScore);
        }
        return $query;
    }
}

