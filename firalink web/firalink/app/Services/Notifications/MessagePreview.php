<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\Catalog\Channel;
use App\Notifications\Catalog\NotificationType;
use App\Notifications\FiraNotification;
use App\Support\SmsSegments;

/**
 * Renders any catalogue message with sample data (messaging slice 2): email HTML, plain text, SMS
 * with its segment count, push and the in-app row. Used by the admin preview page and
 * `php artisan messages:preview`. Never sends anything and never touches the database.
 */
class MessagePreview
{
    public function __construct(private MessageText $text)
    {
    }

    /** Sample values for every placeholder the texts use. Plausible, clearly not real people. */
    public function sampleParams(NotificationType $type): array
    {
        $base = rtrim((string) config('app.url'), '/');

        // The same parameters the real senders pass, so a preview looks like the real message.
        if ($type === NotificationType::PasswordResetLink) {
            return ['reset_url' => $base . '/reset-password/preview-token', 'minutes' => 60];
        }
        if ($type->sendsImmediately()) {
            return ['code' => '482913', 'minutes' => 10, 'expires_at' => now()->addMinutes(10)->toIso8601String()];
        }

        return [
            'name' => 'Abebe Kebede',
            'action_url' => $base . '/',
            'sample_id' => 42,
            'deal_room_id' => 7,
        ];
    }

    public function notifiable(string $locale): User
    {
        $user = new User(['name' => 'Abebe Kebede', 'email' => 'abebe.preview@example.test']);
        $user->forceFill(['locale' => $locale, 'phone' => '251911000000']);

        return $user;
    }

    public function notification(NotificationType $type, string $locale): FiraNotification
    {
        return (new FiraNotification($type, $this->sampleParams($type)))->locale($locale);
    }

    /** Which channels have their texts written (en), for the index. */
    public function templates(NotificationType $type): array
    {
        $out = [];
        foreach ($type->definition()->channels as $ch) {
            $out[$ch] = match ($ch) {
                Channel::Mail => $this->text->has($type, 'subject', 'en') && $this->text->has($type, 'body', 'en'),
                Channel::Sms => $this->text->has($type, 'sms', 'en'),
                Channel::Push, Channel::Database => $this->text->has($type, 'push_title', 'en'),
                default => false,
            };
        }

        return $out;
    }

    public function mailHtml(NotificationType $type, string $locale): string
    {
        return $this->notification($type, $locale)->toMail($this->notifiable($locale))->render();
    }

    public function mailText(NotificationType $type, string $locale): string
    {
        $mail = $this->notification($type, $locale)->toMail($this->notifiable($locale));

        return view('mail.message-text', ['content' => $mail->content])->render();
    }

    public function mailSubject(NotificationType $type, string $locale): string
    {
        return $this->notification($type, $locale)->toMail($this->notifiable($locale))->content['subject'];
    }

    /** @return array{text: ?string, encoding?: string, characters?: int, segments?: int} */
    public function sms(NotificationType $type, string $locale): array
    {
        $text = $this->notification($type, $locale)->toSms($this->notifiable($locale));

        return $text === null ? ['text' => null] : ['text' => $text] + SmsSegments::analyse($text);
    }

    public function push(NotificationType $type, string $locale): array
    {
        $fcm = $this->notification($type, $locale)->toFcm($this->notifiable($locale));

        return ['title' => $fcm['title'], 'body' => $fcm['body'], 'data' => $fcm['data']];
    }

    public function inApp(NotificationType $type): array
    {
        return $this->notification($type, 'en')->toArray($this->notifiable('en'));
    }
}
