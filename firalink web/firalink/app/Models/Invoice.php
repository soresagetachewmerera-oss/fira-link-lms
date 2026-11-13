<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_VOID = 'void';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'currency',
        'exchange_rate',
        'status',
        'provider',
        'provider_ref',
        'tax_amount',
        'tax_rate',
        'tax_notes',
        'issued_at',
        'paid_at',
        'due_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
