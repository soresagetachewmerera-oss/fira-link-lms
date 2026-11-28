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

    public const CATEGORY_STUDENT = 'student';
    public const CATEGORY_GENERAL = 'general';

    public const STUDENT_SECTIONS = ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10'];

    protected $fillable = [
        'event_id',
        'user_id',
        'reference',
        'verification_token',
        'status',
        'payment_status',
        'payment_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'ticket_type',
        'category',
        'student_section',
        'seats',
        'unit_price',
        'service_fee',
        'total_amount',
        'currency',
        'payment_method',
        'contact_method',
        'notes',
        'booked_at',
        'checked_in_at',
        'checked_in_by',
        'check_in_notes',
        'metadata',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'seats' => 'int',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (EventBooking $booking) {
            if (empty($booking->verification_token)) {
                $booking->verification_token = \Illuminate\Support\Str::random(40);
            }
            if (empty($booking->category)) {
                $booking->category = self::CATEGORY_GENERAL;
            }
        });
    }

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

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isCheckedIn(): bool
    {
        return !is_null($this->checked_in_at);
    }

    public function isStudent(): bool
    {
        return $this->category === self::CATEGORY_STUDENT;
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->isStudent() ? 'Fira Link Student' : 'General Attendee';
    }

    public function getStudentSectionBadgeAttribute(): ?string
    {
        if ($this->isStudent() && $this->student_section) {
            return 'Section ' . $this->student_section;
        }
        return null;
    }

    public function getSecureVerificationUrlAttribute(): string
    {
        $token = $this->verification_token ?: $this->reference;
        return route('events.bookings.verify', $token);
    }
}
