<?php

use App\Models\FrontendSetting;
use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Helper functions for the platform.
 *
 * @package App\Support
 * @author Mhquickdev
 */
if (!function_exists('settings')) {
    function settings(?string $key = null, $default = null)
    {
        /** @var SettingsRepository $repository */
        $repository = app(SettingsRepository::class);

        if ($key === null) {
            return $repository;
        }

        return $repository->get($key, $default);
    }
}

if (!function_exists('currency_code')) {
    /**
     * Get the globally configured currency code.
     */
    function currency_code(): string
    {
        return settings('platform.general.default_currency', config('app.currency', 'USD'));
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Resolve the currency symbol for a given code.
     */
    function currency_symbol(?string $code = null): string
    {
        $code = strtoupper($code ?? currency_code());

        $map = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'BDT' => '৳',
            'INR' => '₹',
            'PKR' => 'Rs',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'SGD' => 'S$',
            'NZD' => 'NZ$',
            'CNY' => '¥',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            'ZAR' => 'R',
            'BRL' => 'R$',
            'MXN' => 'MX$',
            'NGN' => '₦',
            'GHS' => '₵',
            'KES' => 'KSh',
            'UGX' => 'USh',
        ];

        return $map[$code] ?? $code;
    }
}

if (!function_exists('currency_format')) {
    /**
     * Format an amount using the configured currency.
     *
     * @param float|int|string $amount
     * @param int $decimals
     * @param string $style 'code', 'symbol', or 'none'
     */
    function currency_format($amount, int $decimals = 2, string $style = 'code'): string
    {
        $amount = (float) $amount;
        $formatted = number_format(abs($amount), $decimals, '.', ',');
        $sign = $amount < 0 ? '-' : '';

        $code = currency_code();
        $symbol = currency_symbol($code);

        return match ($style) {
            'symbol' => $sign . $symbol . $formatted,
            'none' => $sign . $formatted,
            default => $sign . $code . ' ' . $formatted,
        };
    }
}

if (!function_exists('platform_settings')) {
    /**
     * Access platform configuration settings.
     */
    function platform_settings(?string $key = null, $default = null)
    {
        $settings = settings('platform.general', []);

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }
}

if (!function_exists('branding_settings')) {
    /**
     * Access branding & assets settings.
     */
    function branding_settings(?string $key = null, $default = null)
    {
        $settings = settings('branding.assets', []);

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }
}

if (!function_exists('frontend_meta')) {
    /**
     * Retrieve stored SEO metadata for a frontend page.
     */
    function frontend_meta(string $slug, ?string $key = null, $default = null)
    {
        $entries = FrontendSetting::getValue('pages.seo.meta', []);
        $entry = $entries[$slug] ?? null;

        if (!$entry) {
            return $default;
        }

        if ($key !== null) {
            return $entry[$key] ?? $default;
        }

        return $entry;
    }
}

if (!function_exists('frontend_setting')) {
    /**
     * Retrieve a frontend setting value from the key-value store.
     */
    function frontend_setting(string $key, $default = null)
    {
        return FrontendSetting::getValue($key, $default);
    }
}

if (!function_exists('custom_page_url')) {
    /**
     * Generate a URL to a custom page by slug.
     */
    function custom_page_url(string $slug): string
    {
        if (Route::has('pages.show')) {
            return route('pages.show', ['customPage' => $slug]);
        }

        return url($slug);
    }
}

if (!function_exists('branding_asset')) {
    /**
     * Resolve a branding asset path (logo, favicon, etc.) with support for legacy keys.
     *
     * @param  string|array  $keys
     * @param  string|null   $fallback
     */
    function branding_asset(string|array $keys, ?string $fallback = null): string
    {
        $branding = branding_settings();
        $keys = (array) $keys;

        $path = null;
        foreach ($keys as $key) {
            $value = data_get($branding, $key);
            if (filled($value)) {
                $path = $value;
                break;
            }
        }

        return frontend_media($path, $fallback);
    }
}

if (!function_exists('frontend_media')) {
    /**
     * Resolve media path for frontend usage supporting storage, CDN, and asset fallbacks.
     */
    function frontend_media(?string $path, ?string $fallback = null): string
    {
        if (blank($path)) {
            return $fallback ? asset($fallback) : '#';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim(str_replace(['storage/', 'public/'], '', $path), '/');

        if ($normalized && Storage::disk('public')->exists($normalized)) {
            return Storage::url($normalized);
        }

        if (Str::startsWith($path, 'assets/')) {
            return asset($path);
        }

        return $fallback ? asset($fallback) : asset($path);
    }
}

if (!function_exists('ensure_storage_link')) {
    /**
     * Ensure the public storage symbolic link exists and is correct.
     */
    function ensure_storage_link()
    {
        $link = public_path('storage');

        if (file_exists($link) && !is_link($link)) {
            if (is_dir($link)) {
                @rmdir($link);
            } else {
                @unlink($link);
            }
        }

        if (!file_exists($link)) {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
        }
    }
}

if (!function_exists('site_name')) {
    /**
     * Get the globally configured site name.
     */
    function site_name(): string
    {
        return branding_settings('site_name', platform_settings('site_name', config('app.name', 'Eduex')));
    }
}

if (!function_exists('site_tagline')) {
    /**
     * Get the globally configured site tagline.
     */
    function site_tagline(): string
    {
        return platform_settings('tagline', 'Empower your learning journey');
    }
}

if (!function_exists('custom_scripts')) {
    /**
     * Get custom scripts configuration.
     */
    function custom_scripts(?string $key = null, $default = null)
    {
        $settings = settings('platform.custom_scripts', []);

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }
}

if (!function_exists('active_theme')) {
    /**
     * Get the active homepage theme slug.
     *
     * @return string|null Null means default (home.blade.php).
     */
    function active_theme(): ?string
    {
        return app(\App\Services\ThemeService::class)->getActiveTheme();
    }
}

if (!function_exists('active_variant')) {
    /**
     * Get the active homepage variant file name.
     */
    function active_variant(): string
    {
        return app(\App\Services\ThemeService::class)->getActiveVariant();
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Resolve a theme asset URL.
     *
     * Usage: theme_asset('img/hero/hero-bg.jpg')
     * Returns: "/themes/eduna/img/hero/hero-bg.jpg"
     *
     * @param string      $path  Relative path within the theme's assets folder.
     * @param string|null $theme Theme slug (defaults to active theme).
     */
    function theme_asset(string $path, ?string $theme = null): string
    {
        $themeService = app(\App\Services\ThemeService::class);
        $baseUrl = $themeService->getAssetBaseUrl($theme);

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('theme_styles')) {
    /**
     * Get HTML tags for all CSS files defined in the active theme's theme.json.
     */
    function theme_styles(): string
    {
        $themeService = app(\App\Services\ThemeService::class);
        $theme = $themeService->getActiveTheme();
        if (!$theme) {
            return '';
        }

        $meta = $themeService->getThemeMeta($theme);
        $cssFiles = $meta['css'] ?? [];

        $html = '';
        foreach ($cssFiles as $css) {
            $url = theme_asset($css, $theme);
            $html .= '    <link rel="stylesheet" href="' . $url . '">' . "\n";
        }
        return $html;
    }
}

if (!function_exists('theme_scripts')) {
    /**
     * Get HTML tags for all JS files defined in the active theme's theme.json.
     */
    function theme_scripts(): string
    {
        $themeService = app(\App\Services\ThemeService::class);
        $theme = $themeService->getActiveTheme();
        if (!$theme) {
            return '';
        }

        $meta = $themeService->getThemeMeta($theme);
        $jsFiles = $meta['js'] ?? [];

        $html = '';
        foreach ($jsFiles as $js) {
            $url = theme_asset($js, $theme);
            $html .= '    <script src="' . $url . '"></script>' . "\n";
        }
        return $html;
    }
}
