<?php

namespace App\Services\Notifications;

use App\Notifications\Catalog\MessageClass;
use App\Notifications\Catalog\NotificationType;
use App\Notifications\FiraNotification;
use Illuminate\Support\Facades\Notification;

/**
 * The one way code sends a message (messaging slice 1).
 *
 *   $notifier->send($user, NotificationType::SampleShipped, ['sample_id' => $s->id], "sample.shipped:{$s->id}");
 *   $notifier->sendToAddress('mail', $oldEmail, NotificationType::EmailChanged, [...]);
 *
 * Codes and reset links are sent immediately (in the request); everything else is queued.
 * An immediate send that fails returns false and the delivery log says "failed": the caller tells
 * the person "could not send", never "sent".
 */
class Notifier
{
    public function __construct(private DeliveryLog $log)
    {
    }

    /**
     * @param mixed $notifiables a User, a collection of users or an array
     * @param string[]|null $onlyChannels
     * @return bool false when an immediate send failed; queued sends return true once queued
     */
    public function send(mixed $notifiables, NotificationType $type, array $params = [], ?string $idempotencyKey = null, ?array $onlyChannels = null): bool
    {
        $notification = new FiraNotification($type, $params, $idempotencyKey, $onlyChannels);

        try {
            // Queued types go to the worker; with the sync queue (local, tests) they run here, so a
            // failing channel must not break the action that sent the message.
            $type->sendsImmediately()
                ? Notification::sendNow($notifiables, $notification)
                : Notification::send($notifiables, $notification);
        } catch (\Throwable $e) {
            $this->log->failOpen($notification, $e instanceof \App\Channels\SmsDeliveryFailed ? $e->errorCode : class_basename($e));
            report($e);

            return false;
        }

        return true;
    }

    /**
     * To an address that is not (or not only) an account: the old email after a change, a staff mailbox.
     * Marketing never goes to bare addresses (no consent record).
     */
    public function sendToAddress(string $channel, string $address, NotificationType $type, array $params = [], ?string $idempotencyKey = null, string $locale = 'en'): bool
    {
        if ($type->messageClass() === MessageClass::Marketing) {
            throw new \InvalidArgumentException('Marketing messages go only to people who opted in.');
        }
        $driver = \App\Notifications\Catalog\Channel::driver($channel);
        if ($driver === null || !$type->allows($channel)) {
            throw new \InvalidArgumentException("{$type->value} cannot be sent by {$channel}.");
        }

        $notification = (new FiraNotification($type, $params, $idempotencyKey, [$channel]))->locale($locale);
        $route = Notification::route($channel === 'sms' ? $driver : $channel, $address);

        if (!$type->sendsImmediately()) {
            $route->notify($notification);

            return true;
        }
        try {
            $route->notifyNow($notification);
        } catch (\Throwable $e) {
            $this->log->failOpen($notification, $e instanceof \App\Channels\SmsDeliveryFailed ? $e->errorCode : class_basename($e));
            report($e);

            return false;
        }

        return true;
    }
}
