<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\Catalog\Channel;
use App\Notifications\Catalog\MessageClass;
use App\Notifications\Catalog\NotificationType;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Whether a message may go to a person on a channel (messaging slice 1).
 *
 * - The type must allow the channel (catalogue).
 * - Security messages and types that cannot be turned off always go, on their allowed channels.
 * - Otherwise the person's switch for (class, channel) decides; without a switch, the class default
 *   (product: in-app and push on; marketing: off until an opt-in with a consent time).
 * - The in-app inbox (database) is never switched off; it is the record of what happened.
 * - Older settings (user_notification_settings: marketing_emails, email_notifications) count until the
 *   person sets the new switches.
 */
class NotificationPreferences
{
    public const TIMEZONE = 'Africa/Addis_Ababa';

    /** True when the type's defaults include the channel or the person turned it on. */
    public function wants(User $user, NotificationType $type, string $channel): bool
    {
        if (!$type->allows($channel)) {
            return false;
        }

        $def = $type->definition();
        if ($channel === Channel::Database) {
            // The inbox is the record of what happened; marketing reaches it only after an opt-in.
            return $def->class !== MessageClass::Marketing
                || $this->switchFor($user, $def->class, Channel::Push) === true
                || $this->switchFor($user, $def->class, Channel::Mail) === true;
        }

        if (!$type->canDisable()) {
            return in_array($channel, $def->defaults, true);
        }

        $switch = $this->switchFor($user, $def->class, $channel);
        if ($switch !== null) {
            return $switch;
        }

        return in_array($channel, $def->defaults, true);
    }

    /** The person's explicit switch, or a legacy setting, or null when nothing was chosen. */
    public function switchFor(User $user, MessageClass $class, string $channel): ?bool
    {
        $row = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('message_class', $class->value)
            ->where('channel', $channel)
            ->first();
        if ($row) {
            return $row->enabled;
        }

        $legacy = $user->notificationSettings?->preferences ?? [];
        if ($channel === Channel::Mail && $class === MessageClass::Marketing && array_key_exists('marketing_emails', $legacy)) {
            return (bool) $legacy['marketing_emails'];
        }
        if ($channel === Channel::Mail && $class === MessageClass::Product && array_key_exists('email_notifications', $legacy)) {
            return (bool) $legacy['email_notifications'] ? null : false;
        }

        return null;
    }

    /**
     * Sets a switch. Security can never be switched off; the inbox is never switched. Turning a
     * marketing channel on stores the consent time; turning it off keeps the earlier time for the record.
     */
    public function set(User $user, MessageClass $class, string $channel, bool $enabled, string $source): NotificationPreference
    {
        if ($class === MessageClass::Security) {
            throw new \InvalidArgumentException('Security messages cannot be switched off.');
        }
        if (!in_array($channel, Channel::SWITCHABLE, true)) {
            throw new \InvalidArgumentException("The {$channel} channel is not a setting.");
        }

        $row = NotificationPreference::firstOrNew(['user_id' => $user->id, 'message_class' => $class->value, 'channel' => $channel]);
        $row->enabled = $enabled;
        $row->source = $source;
        if ($enabled && $class === MessageClass::Marketing) {
            $row->consented_at = now();
        }
        $row->save();

        return $row;
    }

    /** Inside the person's quiet hours (Addis Ababa time). Both ends are HH:MM; the window may cross midnight. */
    public function inQuietHours(User $user, ?CarbonInterface $at = null): bool
    {
        $from = $user->quiet_hours_from;
        $until = $user->quiet_hours_until;
        if (!$from || !$until || !preg_match('/^\d{2}:\d{2}$/', $from) || !preg_match('/^\d{2}:\d{2}$/', $until) || $from === $until) {
            return false;
        }

        $now = Carbon::instance($at ?? now())->setTimezone(self::TIMEZONE)->format('H:i');

        return $from < $until ? ($now >= $from && $now < $until) : ($now >= $from || $now < $until);
    }
}
