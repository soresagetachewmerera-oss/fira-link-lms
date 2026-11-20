<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a person that a sensitive change was made on their account: password changed, email changed,
 * phone changed, account deletion requested. In the app (database + push) and by email when the
 * account has an address. An email change is also sent to the OLD address (as an anonymous notifiable).
 *
 * Never contains the new password, codes, full phone numbers or full email addresses.
 */
class SecurityNotice extends Notification
{
    public const EVENTS = ['password_changed', 'email_changed', 'phone_changed', 'deletion_requested'];

    public function __construct(public string $event, public array $params = [])
    {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        $channels = ['database', FcmChannel::class];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function texts(string $locale): array
    {
        $replace = [
            'when' => now()->timezone('Africa/Addis_Ababa')->format('Y-m-d H:i'),
            'ip' => $this->params['ip'] ?? '',
            'phone' => $this->params['phone_masked'] ?? '',
            'domain' => $this->params['email_domain'] ?? '',
        ];

        return [
            'title' => __("security.notice.{$this->event}_title", [], $locale),
            'message' => __("security.notice.{$this->event}", $replace, $locale),
            'not_you' => __('security.notice.not_you', [], $locale),
        ];
    }

    public function toArray(object $notifiable): array
    {
        $en = $this->texts('en');
        $am = $this->texts('am');

        return [
            'type' => 'security',
            'event' => $this->event,
            'title' => $en['title'],
            'message' => $en['message'] . ' ' . $en['not_you'],
            'title_am' => $am['title'],
            'message_am' => $am['message'] . ' ' . $am['not_you'],
            'url' => null,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $arr = $this->toArray($notifiable);
        $am = ($notifiable->locale ?? app()->getLocale()) === 'am';

        return [
            'title' => $am ? $arr['title_am'] : $arr['title'],
            'body' => $am ? $arr['message_am'] : $arr['message'],
            'data' => ['type' => 'security', 'event' => $this->event],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable instanceof AnonymousNotifiable ? 'en' : ($notifiable->locale ?? 'en');
        $t = $this->texts($locale === 'am' ? 'am' : 'en');

        return (new MailMessage())
            ->subject($t['title'])
            ->line($t['message'])
            ->line($t['not_you']);
    }
}
