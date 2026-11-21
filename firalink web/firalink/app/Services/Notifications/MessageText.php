<?php

namespace App\Services\Notifications;

use App\Notifications\Catalog\NotificationType;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

/**
 * Texts for catalogue messages (messaging slice 1): resources/lang/{en,am}/messages.php,
 * messages.<type key>.<part> where part is subject, preheader, eyebrow, heading, body, cta,
 * push_title, push_body or sms.
 *
 * A missing Amharic text falls back to English; a missing English text falls back to the generic,
 * lock-screen safe "You have an update" text. Each missing key is logged once per process, never
 * with parameter values (they can hold codes).
 */
class MessageText
{
    /** @var array<string,bool> */
    private static array $reported = [];

    public function get(NotificationType $type, string $part, string $locale, array $params = [], bool $required = true): ?string
    {
        $locale = in_array($locale, ['en', 'am'], true) ? $locale : 'en';
        $key = $type->translationPrefix() . '.' . $part;
        $replace = array_map(fn ($v) => is_scalar($v) ? (string) $v : '', array_filter($params, fn ($v) => is_scalar($v) || $v === null));

        if (Lang::has($key, $locale, false)) {
            return __($key, $replace, $locale);
        }
        $this->report($key, $locale);
        if ($locale !== 'en' && Lang::has($key, 'en', false)) {
            return __($key, $replace, 'en');
        }
        if (!$required) {
            return null;
        }
        $fallback = 'messages.fallback.' . (in_array($part, ['push_title', 'subject', 'heading', 'eyebrow'], true) ? 'title' : 'body');

        return __($fallback, [], $locale);
    }

    public function has(NotificationType $type, string $part, string $locale): bool
    {
        return Lang::has($type->translationPrefix() . '.' . $part, $locale, false);
    }

    private function report(string $key, string $locale): void
    {
        $id = $locale . '|' . $key;
        if (isset(self::$reported[$id])) {
            return;
        }
        self::$reported[$id] = true;
        Log::warning('messages.missing_translation', ['key' => $key, 'locale' => $locale]);
    }
}
