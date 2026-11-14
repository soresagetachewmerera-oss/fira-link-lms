<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email to the verification admin: one per new submission ("new") or the daily summary
 * ("summary"). Names only; never document numbers, IDs or files.
 */
class VerificationAdminAlert extends Notification
{
    public function __construct(public string $kind, public array $data)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.verification-review.index');

        if ($this->kind === 'summary') {
            return (new MailMessage)
                ->subject(__('verification.notifications.admin_summary_subject', ['count' => $this->data['count']], 'en'))
                ->line(__('verification.notifications.admin_summary_line', $this->data, 'en'))
                ->action(__('verification.notifications.open_queue', [], 'en'), $url);
        }

        return (new MailMessage)
            ->subject(__('verification.notifications.admin_new_subject', ['business' => $this->data['business']], 'en'))
            ->line(__('verification.notifications.admin_new_line', $this->data, 'en'))
            ->action(__('verification.notifications.open_queue', [], 'en'), $url);
    }
}
