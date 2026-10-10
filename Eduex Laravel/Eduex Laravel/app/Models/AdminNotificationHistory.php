<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotificationHistory extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'target_audience',
        'image',
        'url',
        'recipient_count',
        'sent_by',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
