<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\Catalog\Channel;
use App\Notifications\Catalog\NotificationType;

/**
 * Welcome after the person confirmed a contact (phone or email code) or registered as an instructor
 * (messaging slice 2). Once per account (idempotency key). The admin switch "send welcome email"
 * (Settings, Email) still decides the email; the in-app welcome is always kept.
 */
class WelcomeMessage
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function send(User $user): void
    {
        $emailOn = (bool) (settings('email.preferences', [])['send_welcome_email'] ?? true);

        $this->notifier->send(
            $user,
            NotificationType::Welcome,
            ['name' => $user->name, 'action_url' => rtrim((string) config('app.url'), '/') . '/'],
            'account.welcome:' . $user->id,
            $emailOn ? null : [Channel::Database],
        );
    }
}
