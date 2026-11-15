<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealRoom extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_UNDER_CONTRACT = 'under_contract';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'deal_reference',
        'organization_id',
        'buyer_id',
        'listing_id',
        'rfq_id',
        'title',
        'target_value_usd',
        'incoterm',
        'contract_document_path',
        'seller_signed_at',
        'buyer_signed_at',
        'seller_signature',
        'buyer_signature',
        'status',
        'dispute_status',
        'dispute_reason',
        'escrow_proof_path',
    ];

    /** Signatures (a person's name), the buyer person and private file paths never leave the server as-is. */
    protected $hidden = [
        'buyer_id',
        'buyer',
        'seller_signature',
        'buyer_signature',
        'contract_document_path',
        'escrow_proof_path',
    ];

    protected $casts = [
        'target_value_usd' => 'decimal:2',
        'seller_signed_at' => 'datetime',
        'buyer_signed_at' => 'datetime',
        'paused_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function buyerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'buyer_organization_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(MarketplaceRfq::class, 'rfq_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(DealMilestone::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_UNDER_CONTRACT]);
    }

    public function isFullySigned(): bool
    {
        return !empty($this->seller_signed_at) && !empty($this->buyer_signed_at);
    }
}
