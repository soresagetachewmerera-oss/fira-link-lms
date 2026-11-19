<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Get the dynamic list of supported locales from resources/lang directories.
     */
    public static function getSupportedLocales(): array
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('active_system_locales', function () {
            $allLocales = [
                'am' => ['name' => 'Amharic', 'native' => 'አማርኛ', 'flag' => ''],
                'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
                'bn' => ['name' => 'Bengali', 'native' => 'বাংলা',  'flag' => '🇧🇩'],
                'es' => ['name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸'],
                'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी',   'flag' => '🇮🇳'],
                'fr' => ['name' => 'French',  'native' => 'Français','flag' => '🇫🇷'],
                'de' => ['name' => 'German',  'native' => 'Deutsch', 'flag' => '🇩🇪'],
                'it' => ['name' => 'Italian', 'native' => 'Italiano', 'flag' => '🇮🇹'],
                'ja' => ['name' => 'Japanese','native' => '日本語',  'flag' => '🇯🇵'],
                'pt' => ['name' => 'Portuguese','native' => 'Português','flag' => '🇵🇹'],
                'ru' => ['name' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺'],
                'zh' => ['name' => 'Chinese', 'native' => '中文',    'flag' => '🇨🇳'],
                'tr' => ['name' => 'Turkish', 'native' => 'Türkçe',  'flag' => '🇹🇷'],
            ];

            $locales = [];
            $langDir = resource_path('lang');
            if (is_dir($langDir)) {
                foreach (scandir($langDir) as $dir) {
                    if ($dir === '.' || $dir === '..' || $dir === 'vendor') {
                        continue;
                    }
                    if (is_dir($langDir . '/' . $dir)) {
                        $locales[$dir] = $allLocales[$dir] ?? [
                            'name' => strtoupper($dir),
                            'native' => strtoupper($dir),
                            'flag' => '🏳️',
                        ];
                    }
                }
            }

            // Sort by name
            uasort($locales, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return $locales;
        });
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Admin area can resolve admin locale or default
        if ($request->is('admin/*') || $request->routeIs('admin.*')) {
            $locale = $this->resolveLocale($request);
            App::setLocale($locale);
            View::share('currentLocale', $locale);
            View::share('availableLocales', self::getSupportedLocales());
            return $next($request);
        }

        // Public website: English, or Amharic where pages have Amharic text (?lang=am, then the
        // remembered choice, then the browser's language). Strings without Amharic fall back to English.
        $locale = $this->resolvePublicLocale($request);
        App::setLocale($locale);
        View::share('currentLocale', $locale);
        View::share('availableLocales', []);

        return $next($request);
    }

    public const PUBLIC_LOCALES = ['en', 'am'];

    protected function resolvePublicLocale(Request $request): string
    {
        $lang = $request->query('lang') ?? $request->header('X-Locale');
        if (is_string($lang)) {
            $normalized = strtolower(substr(trim($lang), 0, 2));
            if (in_array($normalized, self::PUBLIC_LOCALES, true)) {
                if ($request->hasSession()) {
                    $request->session()->put('public_locale', $normalized);
                }
                return $normalized;
            }
        }
        if (is_string($lang) && in_array($lang, self::PUBLIC_LOCALES, true)) {
            if ($request->hasSession()) {
                $request->session()->put('public_locale', $lang);
            }

            return $lang;
        }
        if ($request->hasSession() && in_array($request->session()->get('public_locale'), self::PUBLIC_LOCALES, true)) {
            return $request->session()->get('public_locale');
        }

        return $request->getPreferredLanguage(self::PUBLIC_LOCALES) === 'am' ? 'am' : 'en';
    }

    /**
     * Resolve the locale from the request context.
     */
    protected function resolveLocale(Request $request): string
    {
        // 1. Explicit query parameter  ?lang=bn
        if ($request->has('lang') && $this->isSupported($request->query('lang'))) {
            return $request->query('lang');
        }

        // 2. Session (persisted from a previous switch)
        if (session()->has('locale') && $this->isSupported(session('locale'))) {
            return session('locale');
        }

        // 3. Authenticated user preference (if the column exists)
        if ($request->user() && !empty($request->user()->locale) && $this->isSupported($request->user()->locale)) {
            return $request->user()->locale;
        }

        // 4. Fall back to the app default
        return config('app.locale', 'en');
    }

    /**
     * Check whether the locale code is supported.
     */
    protected function isSupported(?string $locale): bool
    {
        return $locale && array_key_exists($locale, self::getSupportedLocales());
    }
}
