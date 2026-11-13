<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    const STATUS_TRIALING = 'trialing';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_GRACE = 'grace';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'organization_id',
        'plan_id',
        'status',
        'billing_period',
        'current_period_start',
        'current_period_end',
        'grace_until',
        'cancel_at_period_end',
        'provider',
        'provider_ref',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'grace_until' => 'datetime',
        'cancel_at_period_end' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isValid(): bool
    {
        if (in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING])) {
            if ($this->current_period_end && $this->current_period_end->isPast()) {
                // If expired but within grace
                return $this->grace_until && $this->grace_until->isFuture();
            }
            return true;
        }

        if ($this->status === self::STATUS_GRACE) {
            return $this->grace_until && $this->grace_until->isFuture();
        }

        return false;
    }

    public function isGrace(): bool
    {
        return $this->status === self::STATUS_GRACE || ($this->status === self::STATUS_PAST_DUE && $this->grace_until && $this->grace_until->isFuture());
    }
}
