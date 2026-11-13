<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSample extends Model
{
    use HasFactory;

    const STATUS_REQUESTED = 'requested';
    const STATUS_FEE_PAID = 'fee_paid';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CUPPED_APPROVED = 'cupped_approved';
    const STATUS_CUPPED_REJECTED = 'cupped_rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'sample_reference',
        'listing_id',
        'organization_id',
        'buyer_id',
        'status',
        'quantity_grams',
        'shipping_address',
        'courier_name',
        'waybill_number',
        'retained_sample_seal_no',
        'retained_sample_seal_hash',
        'dispatch_date',
        'delivery_date',
        'cupping_score',
        'cupping_notes',
        'lab_report_path',
        'fee_amount_usd',
        'payment_status',
    ];

    protected $casts = [
        'quantity_grams' => 'integer',
        'dispatch_date' => 'date',
        'delivery_date' => 'date',
        'cupping_score' => 'decimal:2',
        'fee_amount_usd' => 'decimal:2',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}

