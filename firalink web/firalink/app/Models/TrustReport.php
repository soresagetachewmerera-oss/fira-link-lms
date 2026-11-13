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

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

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

