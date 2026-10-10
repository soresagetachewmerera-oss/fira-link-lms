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

    protected $fillable = [
        'event_id',
        'user_id',
        'reference',
        'status',
        'first_name',
        'last_name',
        'email',
        'phone',
        'seats',
        'contact_method',
        'notes',
        'booked_at',
        'metadata',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'seats' => 'int',
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

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
