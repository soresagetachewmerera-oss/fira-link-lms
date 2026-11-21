<?php

namespace App\Services\Notifications;

use App\Models\User;

/**
 * The language for a message (messaging slice 3): the person's saved language; before there is
 * one (sign-up, a new number), the language the app or browser asks for (Accept-Language); else English.
 */
final class MessageLocale
{
    public static function for(?User $user): string
    {
        if ($user && in_array($user->locale, ['en', 'am'], true)) {
            return $user->locale;
        }

        $request = request();
        if ($request) {
            $header = strtolower(substr((string) $request->header('Accept-Language', ''), 0, 2));
            if (in_array($header, ['en', 'am'], true)) {
                return $header;
            }
        }

        return 'en';
    }
}
