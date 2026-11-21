<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One message on one channel (messaging slice 1). No bodies, codes, phone numbers or email
 * addresses are ever stored here: the notifiable is referenced by id only.
 */
class NotificationDelivery extends Model
{
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const SKIPPED_PREFERENCE = 'skipped_preference';
    public const SKIPPED_QUIET = 'skipped_quiet';
    public const SKIPPED_UNREACHABLE = 'skipped_unreachable';

    protected $fillable = [
        'type', 'channel', 'notifiable_type', 'notifiable_id', 'idempotency_key', 'status',
        'attempts', 'provider_message_id', 'error_code', 'sent_at', 'failed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
