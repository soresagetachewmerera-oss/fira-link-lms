<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Event Booking Model
 *
 * This model represents a booking for an event.
 *
 * @package App\Models
 */
class EventBooking extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'event_id',
        'user_id',
        'reference',
        'status',
        'payment_status',
        'payment_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'seats',
        'unit_price',
        'total_amount',
        'currency',
        'contact_method',
        'notes',
        'booked_at',
        'metadata',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'seats' => 'int',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
