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
        'status', // pending, in_progress, achieved, waived
        'due_date',
        'completed_at',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
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
