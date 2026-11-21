<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Catalog\Channel;
use App\Notifications\Catalog\NotificationType;
use App\Services\Notifications\DeliveryLog;
use App\Services\Notifications\MessageText;
use App\Services\Notifications\NotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

/**
 * The one notification class for every catalogue type (messaging slice 1).
 *
 *   app(Notifier::class)->send($user, NotificationType::SampleShipped, ['sample_id' => $s->id, 'lot' => $lot->title], "sample.shipped:{$s->id}");
 *
 * - Channels: the catalogue's channels for the type, filtered by the person's preferences
 *   (NotificationPreferences; security and "cannot disable" types ignore opt-outs).
 * - Queued (after the database transaction commits), except codes and reset links, which the
 *   Notifier sends immediately. Stale messages are dropped after the type's TTL.
 * - Every channel is written to the delivery log (DeliveryLog); a repeated idempotency key never
 *   sends twice on the same channel.
 * - The in-app row stores the catalogue key and safe parameters; the app localises it when it
 *   shows it. English and Amharic text are added for older app versions.
 * - Parameters never carry trust: keys such as "verified" or "tier" are refused. A badge comes from
 *   BusinessSummary / TierService when the template renders, never from the caller.
 */
class FiraNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Never stored (in-app row, push data, delivery log); used only to render the message itself. */
    public const SECRET_PARAMS = ['code', 'otp', 'password', 'token', 'reset_url', 'url_signed'];

    /** Trust is decided by the server at render time, never passed in by a caller. */
    public const FORBIDDEN_PARAMS = ['verified', 'is_verified', 'verified_roles', 'tier', 'verification_tier', 'badge', 'evidence_level'];

    /**
     * Delivery log row id per logical channel, set while sending. An object, so the copy Laravel
     * makes for each recipient shares it with the original (needed to mark a thrown send as failed).
     */
    public \ArrayObject $deliveryIds;

    /**
     * @param array<string, scalar|null> $params values for the texts and the route id
     * @param string|null $idempotencyKey one message per key and channel, e.g. "sample.shipped:42"
     * @param string[]|null $onlyChannels send on these channels only (still limited by the catalogue),
     *                                    e.g. a code that the person asked to get by email
     */
    public function __construct(
        public NotificationType $type,
        public array $params = [],
        public ?string $idempotencyKey = null,
        public ?array $onlyChannels = null,
    ) {
        foreach (array_keys($params) as $key) {
            if (in_array($key, self::FORBIDDEN_PARAMS, true)) {
                throw new \InvalidArgumentException("Parameter '{$key}' is not allowed: trust is computed by the server.");
            }
        }
        $this->deliveryIds = new \ArrayObject();
        $this->afterCommit();
    }

    /** @return string[] Laravel channel drivers */
    public function via(object $notifiable): array
    {
        $prefs = app(NotificationPreferences::class);
        $channels = $this->onlyChannels
            ? array_values(array_intersect($this->onlyChannels, $this->type->definition()->channels))
            : $this->type->definition()->channels;

        $out = [];
        foreach ($channels as $channel) {
            if (!$this->reachable($notifiable, $channel)) {
                continue;
            }
            if ($notifiable instanceof User && !$this->onlyChannels && !$prefs->wants($notifiable, $this->type, $channel)) {
                app(DeliveryLog::class)->skip($this, $notifiable, $channel, \App\Models\NotificationDelivery::SKIPPED_PREFERENCE);
                continue;
            }
            if (!$this->hasTemplate($channel)) {
                // The texts for this channel arrive with the type's group (slices 2 to 5): skip, never fake.
                app(DeliveryLog::class)->skip($this, $notifiable, $channel, \App\Models\NotificationDelivery::SKIPPED_UNREACHABLE, 'NO_TEMPLATE');
                continue;
            }
            if ($driver = Channel::driver($channel)) {
                $out[] = $driver;
            }
        }

        return $out;
    }

    /** Last check right before a channel sends (also in the queue worker): quiet hours and duplicates. */
    public function shouldSend(object $notifiable, string $driver): bool
    {
        $channel = Channel::fromDriver($driver) ?? $driver;
        $log = app(DeliveryLog::class);

        if ($channel === Channel::Push && $notifiable instanceof User
            && $this->type->messageClass()->respectsQuietHours()
            && app(NotificationPreferences::class)->inQuietHours($notifiable)) {
            $log->skip($this, $notifiable, $channel, \App\Models\NotificationDelivery::SKIPPED_QUIET);

            return false;
        }

        return $log->begin($this, $notifiable, $channel);
    }

    /** Email needs a subject and a body, SMS needs its text; push and the inbox fall back to a safe generic text. */
    private function hasTemplate(string $channel): bool
    {
        $text = app(MessageText::class);

        return match ($channel) {
            Channel::Mail => $text->has($this->type, 'subject', 'en') && $text->has($this->type, 'body', 'en'),
            Channel::Sms => $text->has($this->type, 'sms', 'en'),
            default => true,
        };
    }

    /** Whether the person or address can receive on the channel at all. */
    private function reachable(object $notifiable, string $channel): bool
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return match ($channel) {
                Channel::Mail => !empty($notifiable->routes['mail'] ?? null),
                Channel::Sms => !empty($notifiable->routes['sms'] ?? null) || !empty($notifiable->routes[\App\Channels\SmsChannel::class] ?? null),
                default => false,
            };
        }

        return match ($channel) {
            Channel::Mail => !empty($notifiable->email),
            Channel::Sms => !empty($notifiable->phone),
            Channel::Push => method_exists($notifiable, 'deviceTokens'),
            Channel::Database => method_exists($notifiable, 'notifications'),
            default => false,
        };
    }

    /** Parameters that may be stored (in-app row, push data): no secrets. */
    public function safeParams(): array
    {
        return array_filter(
            array_diff_key($this->params, array_flip(self::SECRET_PARAMS)),
            fn ($v) => is_scalar($v) || $v === null,
        );
    }

    /** The id the deep link opens, if the type has one. */
    public function routeId(): ?string
    {
        $param = $this->type->definition()->routeParam;

        return $param && isset($this->params[$param]) ? (string) $this->params[$param] : null;
    }

    public function localeFor(object $notifiable): string
    {
        $locale = $notifiable instanceof User ? $notifiable->preferredLocale() : ($this->locale ?? 'en');

        return in_array($locale, ['en', 'am'], true) ? $locale : 'en';
    }

    /** In-app inbox row: the catalogue key and safe parameters (the app localises), plus both texts for older apps. */
    public function toArray(object $notifiable): array
    {
        $text = app(MessageText::class);
        $params = $this->safeParams();

        return [
            'type' => $this->type->value,
            'class' => $this->type->messageClass()->value,
            'params' => $params,
            'route' => $this->type->definition()->appRoute,
            'route_id' => $this->routeId(),
            'title' => $text->get($this->type, 'push_title', 'en', $params),
            'message' => $text->get($this->type, 'push_body', 'en', $params),
            'title_am' => $text->get($this->type, 'push_title', 'am', $params),
            'message_am' => $text->get($this->type, 'push_body', 'am', $params),
            'url' => null,
        ];
    }

    /** Lock-screen safe push: the catalogue's short title and body, an id and a route; details after sign-in. */
    public function toFcm(object $notifiable): array
    {
        $locale = $this->localeFor($notifiable);
        $text = app(MessageText::class);
        $def = $this->type->definition();

        return [
            'title' => $text->get($this->type, 'push_title', $locale, $this->safeParams()),
            'body' => $text->get($this->type, 'push_body', $locale, $this->safeParams()),
            'data' => array_filter([
                'type' => $this->type->value,
                'route' => $def->appRoute,
                'route_id' => $this->routeId(),
                'android_channel' => $def->androidChannel,
                'thread' => $def->class->value,
            ], fn ($v) => $v !== null),
        ];
    }

    /** SMS text in the person's language (codes included only here, never stored). */
    public function toSms(object $notifiable): ?string
    {
        return app(MessageText::class)->get($this->type, 'sms', $this->localeFor($notifiable), $this->params, required: false);
    }

    /** Email arrives in slice 2 (shared layout and components). Until then a catalogue type cannot email. */
    public function toMail(object $notifiable)
    {
        throw new \LogicException("Email for {$this->type->value} is built in messaging slice 2 (shared layout).");
    }

    /** Stale messages are dropped instead of arriving late. */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addSeconds($this->type->definition()->ttlSeconds);
    }

    /** The queued job gave up: the delivery log says so (never "sent"). */
    public function failed(\Throwable $e): void
    {
        app(DeliveryLog::class)->failOpen($this, class_basename($e));
    }
}
