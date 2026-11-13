<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    const EVENT_CREATED = 'created';
    const EVENT_UPGRADED = 'upgraded';
    const EVENT_DOWNGRADED = 'downgraded';
    const EVENT_RENEWED = 'renewed';
    const EVENT_FAILED = 'failed';
    const EVENT_GRACE_STARTED = 'grace_started';
    const EVENT_EXPIRED = 'expired';
    const EVENT_OVERRIDE_APPLIED = 'override_applied';
    const EVENT_ADMIN_CHANGED = 'admin_changed';

    protected $fillable = [
        'subscription_id',
        'organization_id',
        'event_type',
        'from_plan_id',
        'to_plan_id',
        'metadata',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
