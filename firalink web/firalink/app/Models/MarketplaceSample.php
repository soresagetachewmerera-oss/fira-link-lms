<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sample between a buyer business and a seller business. Status, payment, seal and review
 * fields are not fillable: only App\Services\Trade\SampleService changes them.
 */
class MarketplaceSample extends Model
{
    use HasFactory;

    const STATUS_REQUESTED = 'requested';     // waiting for the buyer's payment
    const STATUS_FEE_PAID = 'fee_paid';       // paid (or free): the seller prepares, seals and ships
    const STATUS_DISPATCHED = 'dispatched';   // sealed and handed to the courier
    const STATUS_DELIVERED = 'delivered';     // the buyer received it and checked the seal
    const STATUS_CUPPED_APPROVED = 'cupped_approved';
    const STATUS_CUPPED_REJECTED = 'cupped_rejected';
    const STATUS_CANCELLED = 'cancelled';

    public const SIZES = [250, 500];
    public const CARRIERS = ['dhl', 'fedex'];

    protected $fillable = [
        'listing_id',
        'quantity_grams',
        'delivery_address',
        'delivery_company',
        'delivery_country',
        'delivery_phone',
        'courier_carrier',
        'courier_account',
    ];

    // Contact details and private files never go out in JSON; SampleService builds what each side sees.
    protected $hidden = [
        'shipping_address', 'delivery_address', 'delivery_company', 'delivery_phone', 'courier_account',
        'sealed_photo_path', 'retained_photo_path', 'lab_report_path', 'retained_sample_seal_hash', 'buyer',
    ];

    protected $casts = [
        'quantity_grams' => 'integer',
        'dispatch_date' => 'date',
        'delivery_date' => 'date',
        'ship_by' => 'date',
        'cupping_score' => 'decimal:2',
        'fee_amount_usd' => 'decimal:2',
        'delivery_address' => 'encrypted',
        'delivery_company' => 'encrypted',
        'delivery_phone' => 'encrypted',
        'courier_account' => 'encrypted',
        'paid_at' => 'datetime',
        'sealed_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'seal_intact' => 'boolean',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    /** The seller business. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function buyerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'buyer_organization_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
