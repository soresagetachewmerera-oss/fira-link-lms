<?php

namespace App\Notifications\Catalog;

/**
 * Message classes (messaging 2026-11-21). Decides who may turn a message off and how it is sent.
 *
 *  security       A: codes, password, contact and device changes. Never turned off.
 *  transactional  B: something happened to the person's account, business, trade or money.
 *                 Mostly cannot be turned off (per type: NotificationType::canDisable()).
 *  product        C: reminders, digests, new courses, announcements. On by default, can be turned off.
 *  marketing      D: newsletter and promotions. Off until the person opts in (consent stored).
 */
enum MessageClass: string
{
    case Security = 'security';
    case Transactional = 'transactional';
    case Product = 'product';
    case Marketing = 'marketing';

    /** Channels that are on when the person never changed anything. */
    public function defaultOn(string $channel): bool
    {
        return match ($this) {
            self::Security, self::Transactional => true,
            self::Product => in_array($channel, [Channel::Database, Channel::Push], true),
            self::Marketing => false,
        };
    }

    /** Push may wait for quiet hours (security messages never wait). */
    public function respectsQuietHours(): bool
    {
        return $this !== self::Security;
    }
}
