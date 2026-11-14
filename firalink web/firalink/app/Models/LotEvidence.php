<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One piece of lot evidence (an in-app photo or a lab report). Review fields are not fillable;
 * only App\Services\Lots\LotEvidenceService changes them.
 */
class LotEvidence extends Model
{
    protected $table = 'lot_evidence';

    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NEEDS_CHANGES = 'needs_changes';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUPERSEDED = 'superseded';

    public const KIND_LAB_REPORT = 'lab_report';

    protected $fillable = [
        'listing_id',
        'organization_id',
        'kind',
        'captured_at',
        'latitude',
        'longitude',
        'location_accuracy_m',
        'details',
        'submitted_by',
        'submitted_at',
    ];

    protected $hidden = ['latitude', 'longitude', 'location_accuracy_m'];

    protected $casts = [
        'captured_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'details' => 'array',
        'checks' => 'array',
        'reminders_sent' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(LotEvidencePage::class)->orderBy('page_number');
    }

    public function isPhoto(): bool
    {
        return $this->kind !== self::KIND_LAB_REPORT;
    }

    public function isExpiredNow(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(now()->startOfDay());
    }

    /** Approved and not past its expiry date. */
    public function scopeCounting(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_APPROVED)
            ->where(fn ($e) => $e->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString()));
    }

    /** The item shown for its kind: anything not replaced. */
    public function scopeCurrent(Builder $q): Builder
    {
        return $q->where('status', '!=', self::STATUS_SUPERSEDED);
    }
}
