<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_room_id',
        'title',
        'milestone_key',
        'description',
        'document_path',
        'notes',
        'status', // pending, in_progress, proof_submitted, achieved, waived
        'due_date',
        'completed_at',
        'verified_by',
        'verified_at',
        'submitted_by',
        'submitted_at',
        'bank_reference',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PROOF_SUBMITTED = 'proof_submitted';
    public const STATUS_ACHIEVED = 'achieved';
    public const STATUS_WAIVED = 'waived';

    /** Statuses only a confirmation by admin or finance can set. */
    public const CONFIRMED_STATUSES = [self::STATUS_ACHIEVED, self::STATUS_WAIVED];

    /** Private file path and the people behind each action stay on the server. */
    protected $hidden = ['document_path', 'submitted_by', 'verified_by', 'verifier'];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
