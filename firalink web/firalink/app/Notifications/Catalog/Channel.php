<?php

namespace App\Notifications\Catalog;

use App\Channels\FcmChannel;
use App\Channels\SmsChannel;

/** Logical channel names used by the catalogue, preferences and the delivery log. */
final class Channel
{
    public const Mail = 'mail';
    public const Database = 'database'; // in-app inbox
    public const Push = 'push';
    public const Sms = 'sms';
    public const Telegram = 'telegram'; // reserved: no provider yet (decision D-8)

    public const ALL = [self::Mail, self::Database, self::Push, self::Sms, self::Telegram];

    /** Channels a person can switch in Settings. The in-app inbox is always kept. */
    public const SWITCHABLE = [self::Mail, self::Push, self::Sms];

    /** The Laravel channel driver for a logical channel. */
    public static function driver(string $channel): ?string
    {
        return match ($channel) {
            self::Mail => \App\Channels\FiraMailChannel::class,
            self::Database => 'database',
            self::Push => FcmChannel::class,
            self::Sms => SmsChannel::class,
            default => null,
        };
    }

    /** The logical name for a Laravel channel driver. */
    public static function fromDriver(string $driver): ?string
    {
        foreach (self::ALL as $c) {
            if (self::driver($c) === $driver) {
                return $c;
            }
        }

        return null;
    }
}
