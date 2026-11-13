<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * MarketplaceSellerProfile Model
 *
 * Represents an Ethiopian exporter, cooperative, union, or commercial farm business profile.
 */
class MarketplaceSellerProfile extends Model
{
    use HasFactory;

    protected $table = 'marketplace_seller_profiles';

    protected $fillable = [
        'user_id',
        'organization_id',
        'company_name',
        'company_slug',
        'business_type',
        'export_license_no',
        'tin_number',
        'origin_regions',
        'washing_stations',
        'washing_station_count',
        'annual_export_volume_mt',
        'certifications',
        'company_bio',
        'website_url',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'country',
        'logo_path',
        'banner_path',
        'license_document_path',
        // verification_status, verified_at and verification_notes are NOT fillable:
        // they change only through App\Services\Verification\VerificationReviewService.
    ];

    protected $casts = [
        'origin_regions' => 'array',
        'washing_stations' => 'array',
        'certifications' => 'array',
        'annual_export_volume_mt' => 'decimal:2',
        'washing_station_count' => 'integer',
        'verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            if (empty($profile->company_slug) && !empty($profile->company_name)) {
                $profile->company_slug = Str::slug($profile->company_name) . '-' . Str::random(5);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(CoffeeListing::class, 'seller_id', 'user_id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /** Supplier pages buyers may see: same rule as lots (approved seller business, not suspended). */
    public function scopeVisibleToBuyers($query)
    {
        return $query->whereHas('organization', \App\Services\Verification\VerificationReviewService::approvedSellerConstraint());
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }
}

