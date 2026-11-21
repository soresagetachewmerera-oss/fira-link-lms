<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;
use App\Notifications\FiraNotification;
use Illuminate\Database\QueryException;
use Illuminate\Notifications\AnonymousNotifiable;

/**
 * Writes notification_deliveries (messaging slice 1): one row per message and channel. Stores the
 * type, channel, notifiable id, status, attempts, provider id and an error code. Never a body, a
 * code, a phone number or an email address.
 */
class DeliveryLog
{
    /**
     * A channel is about to send. False when this idempotency key was already sent on this channel
     * (also when two workers race for the same key: the unique index lets only one through).
     */
    public function begin(FiraNotification $n, object $notifiable, string $channel): bool
    {
        if ($n->idempotencyKey !== null) {
            $row = NotificationDelivery::where('idempotency_key', $n->idempotencyKey)->where('channel', $channel)->first();
            if ($row && $row->status === NotificationDelivery::SENT) {
                return false;
            }
            if ($row) {
                $row->forceFill(['status' => NotificationDelivery::SENDING, 'attempts' => $row->attempts + 1, 'error_code' => null])->save();
                $n->deliveryIds[$channel] = $row->id;

                return true;
            }
        }

        try {
            $row = NotificationDelivery::create($this->base($n, $notifiable, $channel) + [
                'status' => NotificationDelivery::SENDING,
                'attempts' => 1,
            ]);
        } catch (QueryException $e) {
            // Unique (idempotency_key, channel): another worker is sending this message.
            return false;
        }
        $n->deliveryIds[$channel] = $row->id;

        return true;
    }

    /** A channel was not used (preference, quiet hours, no address). Not written twice for one key. */
    public function skip(FiraNotification $n, object $notifiable, string $channel, string $status, ?string $errorCode = null): void
    {
        if ($n->idempotencyKey !== null
            && NotificationDelivery::where('idempotency_key', $n->idempotencyKey)->where('channel', $channel)->exists()) {
            return;
        }
        try {
            NotificationDelivery::create($this->base($n, $notifiable, $channel) + ['status' => $status, 'attempts' => 0, 'error_code' => $errorCode]);
        } catch (QueryException $e) {
            // Already recorded.
        }
    }

    /** The channel returned. Push and SMS answers are read so "sent" means the provider accepted it. */
    public function sent(FiraNotification $n, string $channel, mixed $response): void
    {
        $row = $this->row($n, $channel);
        if (!$row) {
            return;
        }

        $status = NotificationDelivery::SENT;
        $error = null;
        $provider = null;
        if ($response === null && in_array($channel, [\App\Notifications\Catalog\Channel::Sms, \App\Notifications\Catalog\Channel::Push], true)) {
            // Nothing was handed to the provider (no text, no number, no device): not "sent".
            $status = NotificationDelivery::SKIPPED_UNREACHABLE;
        } elseif (is_array($response)) {
            if (isset($response['skipped']) && (int) $response['skipped'] > 0 && (int) ($response['success'] ?? 0) === 0) {
                $status = NotificationDelivery::SKIPPED_UNREACHABLE; // no device registered
            } elseif (isset($response['failure']) && (int) $response['failure'] > 0 && (int) ($response['success'] ?? 0) === 0) {
                $status = NotificationDelivery::FAILED;
                $error = 'PROVIDER_REJECTED';
            }
            $provider = $response['message_id'] ?? $response['provider_message_id'] ?? $response['name'] ?? null;
        } elseif (is_object($response) && method_exists($response, 'getMessageId')) {
            $provider = $response->getMessageId(); // mail: the message id from the mailer
        }

        $row->forceFill([
            'status' => $status,
            'error_code' => $error,
            'provider_message_id' => is_scalar($provider) ? mb_substr((string) $provider, 0, 190) : null,
            'sent_at' => $status === NotificationDelivery::SENT ? now() : null,
            'failed_at' => $status === NotificationDelivery::FAILED ? now() : null,
        ])->save();
    }

    /** The channel failed (exception or a NotificationFailed event). */
    public function failed(FiraNotification $n, string $channel, string $code): void
    {
        $this->row($n, $channel)?->forceFill([
            'status' => NotificationDelivery::FAILED,
            'error_code' => mb_substr($code, 0, 80),
            'failed_at' => now(),
        ])->save();
    }

    /** Every channel of this message still marked "sending" failed (the send threw). */
    public function failOpen(FiraNotification $n, string $code): void
    {
        $query = NotificationDelivery::query()->where('status', NotificationDelivery::SENDING);
        if ($n->idempotencyKey !== null) {
            $query->where('idempotency_key', $n->idempotencyKey);
        } elseif (count($n->deliveryIds) > 0) {
            $query->whereIn('id', array_values($n->deliveryIds->getArrayCopy()));
        } else {
            return;
        }
        $query->update(['status' => NotificationDelivery::FAILED, 'error_code' => mb_substr($code, 0, 80), 'failed_at' => now(), 'updated_at' => now()]);
    }

    private function row(FiraNotification $n, string $channel): ?NotificationDelivery
    {
        if (isset($n->deliveryIds[$channel])) {
            return NotificationDelivery::find($n->deliveryIds[$channel]);
        }
        if ($n->idempotencyKey !== null) {
            return NotificationDelivery::where('idempotency_key', $n->idempotencyKey)->where('channel', $channel)->first();
        }

        return null;
    }

    private function base(FiraNotification $n, object $notifiable, string $channel): array
    {
        $anonymous = $notifiable instanceof AnonymousNotifiable;

        return [
            'type' => $n->type->value,
            'channel' => $channel,
            'notifiable_type' => $anonymous ? 'address' : $notifiable::class,
            'notifiable_id' => $anonymous ? null : ($notifiable->getKey() ?? null),
            'idempotency_key' => $n->idempotencyKey,
        ];
    }
}
