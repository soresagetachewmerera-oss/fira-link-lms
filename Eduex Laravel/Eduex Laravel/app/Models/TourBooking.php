<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TourBooking extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_FAILED = 'failed';

    protected $fillable = [
        'tour_id',
        'user_id',
        'reference',
        'first_name',
        'last_name',
        'email',
        'phone',
        'participants',
        'schedule_date',
        'departure_time',
        'unit_price',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_id',
        'special_requests',
        'notes',
        'booked_at',
        'metadata',
    ];

    protected $casts = [
        'participants' => 'int',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'schedule_date' => 'date',
        'booked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
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

    public static function generateReference(): string
    {
        do {
            $ref = 'TR-' . strtoupper(Str::random(8));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }
}
