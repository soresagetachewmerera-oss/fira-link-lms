<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustReport extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reported_organization_id',
        'reported_listing_id',
        'reason',
        'details',
        'evidence_path',
        'status',
        'action_taken_notes',
        'resolved_by',
        'resolved_at',
    ];

    /** Slice 7 reason codes, in the order the report sheet shows them. */
    public const REASONS = ['fake_photos', 'pay_outside', 'fake_documents', 'impersonation', 'not_as_described', 'other'];

    /** The reporter is never shown to the reported business. */
    protected $hidden = ['reporter_id', 'reporter_organization_id', 'evidence_path'];

    protected $casts = [
        'resolved_at' => 'datetime',
        'block_requested' => 'boolean',
    ];

    public function reporterOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'reporter_organization_id');
    }

    public function reportedMessage(): BelongsTo
    {
        return $this->belongsTo(TradeMessage::class, 'reported_message_id');
    }

    public function reportedDealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class, 'reported_deal_room_id');
    }

    public function reportedSample(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSample::class, 'reported_sample_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reportedOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'reported_organization_id');
    }

    public function reportedListing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'reported_listing_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

