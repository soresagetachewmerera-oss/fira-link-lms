<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Notification Setting Model
 *
 * This model represents the notification settings for a user.
 *
 * @package App\Models
 */
class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
