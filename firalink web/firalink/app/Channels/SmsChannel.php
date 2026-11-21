<?php

namespace App\Channels;

use App\Services\SmsEthiopiaService;
use Illuminate\Notifications\Notification;

/**
 * SMS through SMS Ethiopia for catalogue notifications (messaging slice 1). The only place, together
 * with OtpService during the migration, that hands text to the SMS gateway.
 *
 * The notification gives the text with toSms($notifiable) (null: nothing to send). The number comes
 * from routeNotificationForSms() or the person's phone. A gateway failure throws, so the delivery is
 * logged as failed and a queued notification is retried: never "sent" when it was not.
 */
class SmsChannel
{
    public function __construct(private SmsEthiopiaService $sms)
    {
    }

    public function send($notifiable, Notification $notification): ?array
    {
        if (!method_exists($notification, 'toSms')) {
            return null;
        }
        $text = $notification->toSms($notifiable);
        $to = null;
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $to = $notifiable->routeNotificationFor(self::class, $notification) ?? $notifiable->routeNotificationFor('sms', $notification);
        }
        if ($to === null && $notifiable instanceof \App\Models\User) {
            $to = $notifiable->phone;
        }
        if ($text === null || $text === '' || empty($to)) {
            return null;
        }

        $user = $notifiable instanceof \App\Models\User ? $notifiable : null;
        $type = property_exists($notification, 'type') && $notification->type instanceof \BackedEnum ? $notification->type->value : null;
        $result = $this->sms->send((string) $to, $text, $type, $user);

        if (!($result['success'] ?? false)) {
            throw new SmsDeliveryFailed((string) ($result['code'] ?? 'SMS_FAILED'));
        }

        return $result;
    }
}
