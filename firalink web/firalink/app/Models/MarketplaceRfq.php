<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * MarketplaceRfq Model
 *
 * Represents an international or domestic buyer Request for Quotation (tender).
 */
class MarketplaceRfq extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketplace_rfqs';

    // The buyer is shown as a business (BusinessSummary), never as a user record.
    protected $hidden = ['buyer'];

    protected $fillable = [
        'buyer_id',
        'rfq_number',
        'title',
        'target_origin_region',
        'target_grade',
        'target_processing_method',
        'target_cupping_score_min',
        'volume_bags',
        'bag_type_preference',
        'target_price_usd_per_lb',
        'destination_country',
        'destination_port',
        'preferred_incoterm',
        'preferred_payment_terms',
        'target_delivery_date',
        'closing_date',
        'specifications',
        'volume_value',
        'volume_unit',
        'sample_first',
    ];

    /** Volume units a buyer may use; quotes are compared in kg. */
    public const UNITS_KG = ['bags' => 60, 'kg' => 1, 'mt' => 1000];

    protected $casts = [
        'target_cupping_score_min' => 'decimal:2',
        'target_price_usd_per_lb' => 'decimal:2',
        'volume_bags' => 'integer',
        'quotes_count' => 'integer',
        'target_delivery_date' => 'date',
        'closing_date' => 'date',
        'closed_at' => 'datetime',
        'volume_value' => 'decimal:2',
        'volume_kg' => 'decimal:2',
        'sample_first' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rfq) {
            if (empty($rfq->rfq_number)) {
                $rfq->rfq_number = 'RFQ-' . date('Y') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && ($this->closing_date === null || $this->closing_date->gte(now()->startOfDay()));
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(MarketplaceQuotation::class, 'rfq_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('closing_date')->orWhere('closing_date', '>=', now()->toDateString());
            });
    }
}

