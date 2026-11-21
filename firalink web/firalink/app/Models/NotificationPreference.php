<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A person's switch for one message class on one channel (messaging slice 1). */
class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'message_class', 'channel', 'enabled', 'consented_at', 'source'];

    protected $casts = [
        'enabled' => 'boolean',
        'consented_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
