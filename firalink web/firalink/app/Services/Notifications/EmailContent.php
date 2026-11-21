<?php

namespace App\Services\Notifications;

use App\Notifications\Catalog\MessageClass;
use App\Notifications\FiraNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Builds the content of a catalogue email (messaging slice 2): subject, preheader and blocks in a
 * fixed order (eyebrow, heading, paragraphs, code, button, facts, notice, help) plus the footer.
 * The same content renders the HTML (mail.message) and the plain text (mail.message-text).
 *
 * Links: only the official domain (config app.url). Any other link is dropped and logged.
 * Times: Africa/Addis_Ababa, Gregorian, with the zone named.
 */
class EmailContent
{
    public const TIMEZONE = 'Africa/Addis_Ababa';

    public function __construct(private MessageText $text)
    {
    }

    public function build(FiraNotification $n, ?object $notifiable, string $locale): array
    {
        $type = $n->type;
        $p = $n->params;
        $replace = $this->replacements($p, $locale);
        $get = fn (string $part, bool $required = false) => $this->text->get($type, $part, $locale, $replace, $required);

        $blocks = [];
        if ($eyebrow = $get('eyebrow')) {
            $blocks[] = ['type' => 'eyebrow', 'text' => $eyebrow];
        }
        $blocks[] = ['type' => 'heading', 'text' => $get('heading', true)];
        foreach (preg_split("/\n\s*\n/", (string) $get('body', true)) as $paragraph) {
            if (trim($paragraph) !== '') {
                $blocks[] = ['type' => 'paragraph', 'text' => trim($paragraph)];
            }
        }
        if (!empty($p['code']) && is_scalar($p['code'])) {
            $blocks[] = ['type' => 'code', 'code' => (string) $p['code'], 'label' => __('messages.layout.code_label', [], $locale)];
        }
        if (($cta = $get('cta')) && ($url = $this->officialUrl($p['reset_url'] ?? $p['action_url'] ?? null))) {
            $blocks[] = ['type' => 'button', 'text' => $cta, 'url' => $url, 'fallback' => __('messages.layout.button_fallback', [], $locale)];
        }
        $facts = [];
        if (!empty($p['expires_at'])) {
            $facts[] = [__('messages.layout.valid_until', [], $locale), $replace['expires']];
        }
        foreach ((array) ($p['facts'] ?? []) as $fact) {
            if (is_array($fact) && count($fact) === 2) {
                $facts[] = [(string) $fact[0], (string) $fact[1]];
            }
        }
        if ($facts) {
            $blocks[] = ['type' => 'facts', 'facts' => $facts];
        }
        if ($notice = $get('notice')) {
            $blocks[] = ['type' => 'notice', 'text' => $notice];
        }
        $blocks[] = ['type' => 'help', 'text' => $get('help') ?? __('messages.layout.help', [], $locale)];

        return [
            'locale' => $locale,
            'subject' => $get('subject', true),
            'preheader' => (string) $get('preheader'),
            'blocks' => $blocks,
            'footer' => $this->footer($type->messageClass(), $notifiable, $locale),
        ];
    }

    /** Scalars for :placeholders, plus a formatted :expires (Addis Ababa time). */
    private function replacements(array $p, string $locale): array
    {
        $out = [];
        foreach ($p as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $out[$k] = (string) $v;
            }
        }
        if (!empty($p['expires_at'])) {
            $at = Carbon::parse($p['expires_at'])->setTimezone(self::TIMEZONE);
            $out['expires'] = __('messages.layout.time_addis', ['time' => $at->format('H:i'), 'date' => $at->format('j M Y')], $locale);
        }

        return $out;
    }

    private function footer(MessageClass $class, ?object $notifiable, string $locale): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $other = $locale === 'am' ? 'en' : 'am';
        $why = match (true) {
            $notifiable instanceof AnonymousNotifiable => 'why_address',
            $class === MessageClass::Marketing => 'why_marketing',
            $class === MessageClass::Product => 'why_updates',
            default => 'why_account',
        };

        return [
            'why' => __("messages.layout.{$why}", [], $locale),
            'never_ask' => __('messages.layout.never_ask', [], $locale),
            'support_label' => __('messages.layout.support', [], $locale),
            'support_url' => $base . '/contact',
            'language_label' => __('messages.layout.language_' . $other, [], $locale),
            'language_url' => $base . '/?lang=' . $other,
            'language_code' => $other,
            // Settings link only for messages a person can turn off (product, marketing). The web
            // settings page arrives in messaging slice 6; until then these classes link to the app download page.
            'preferences_label' => __('messages.layout.preferences', [], $locale),
            'preferences_url' => in_array($class, [MessageClass::Product, MessageClass::Marketing], true) ? $base . '/' : null,
        ];
    }

    /** The URL when it is on the official domain, otherwise null (and logged). */
    public function officialUrl(mixed $url): ?string
    {
        if (!is_string($url) || $url === '') {
            return null;
        }
        $base = rtrim((string) config('app.url'), '/');
        if ($url === $base || str_starts_with($url, $base . '/') || str_starts_with($url, $base . '?')) {
            return $url;
        }
        Log::warning('messages.link_dropped', ['host' => parse_url($url, PHP_URL_HOST)]);

        return null;
    }
}
