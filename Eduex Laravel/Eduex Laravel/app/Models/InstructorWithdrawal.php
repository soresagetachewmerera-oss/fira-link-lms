<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Instructor Withdrawal Model
 *
 * This model represents a withdrawal request for an instructor.
 *
 * @package App\Models
 */
class InstructorWithdrawal extends Model
{
    protected $fillable = [
        'instructor_id',
        'amount',
        'currency',
        'method',
        'method_details',
        'status',
        'reference',
        'notes',
        'admin_notes',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSING = 'processing';

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING || $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}

