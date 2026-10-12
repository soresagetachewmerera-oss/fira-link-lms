<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient',
        'recipient_masked',
        'message_preview',
        'template_key',
        'status',
        'provider_response',
        'error_message',
        'ip_address',
    ];

    protected $casts = [
        'provider_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to mask sensitive phone numbers.
     * e.g. 251911234567 -> 251911***567
     */
    public static function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 6) {
            return '***' . substr($phone, -2);
        }

        $prefix = substr($phone, 0, 6);
        $suffix = substr($phone, -3);

        return $prefix . '***' . $suffix;
    }

    /**
     * Helper to mask OTP or sensitive credentials from message previews.
     */
    public static function sanitizeMessage(string $message): string
    {
        // Mask 4-8 digit codes in message preview (e.g. "code is: 123456" -> "code is: 12****")
        return preg_replace('/(\b\d{2})\d{2,6}(\b)/', '$1****', $message);
    }
}
