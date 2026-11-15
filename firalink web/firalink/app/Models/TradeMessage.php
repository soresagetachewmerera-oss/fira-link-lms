<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One chat message. Flagged messages (contact details, off-platform invitations) are delivered with a warning. */
class TradeMessage extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['sender_id'];

    protected $casts = [
        'flagged' => 'boolean',
        'flag_kinds' => 'array',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TradeConversation::class, 'trade_conversation_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
