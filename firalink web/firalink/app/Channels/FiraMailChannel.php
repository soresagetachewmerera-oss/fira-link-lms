<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * Email for catalogue notifications (messaging slice 2). Sends the FiraMail through the Mail
 * facade, so the mailer configured for the app (and Mail::fake() in tests) handles it like any
 * other Mailable, and returns the sent message (its id goes to the delivery log).
 * A transport error is thrown: the delivery is recorded as failed, never as sent.
 */
class FiraMailChannel
{
    public function send($notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toMail')) {
            return null;
        }
        $mail = $notification->toMail($notifiable);

        return $mail ? Mail::send($mail) : null;
    }
}
