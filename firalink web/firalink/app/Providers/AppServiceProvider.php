<?php

namespace App\Providers;

use App\Models\FrontendSetting;
use App\Models\Menu;
use App\Services\SettingsRepository;
use App\Services\RecaptchaService;
use App\Services\OtpService;
use App\Services\SmtpConfigurator;
use App\Services\RevenueShareService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * App Service Provider
 *
 * This service provider registers the application services.
 *
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fira AI market answers: Gemini behind MARKET_AI_ENABLED and GEMINI_ENABLED (tests bind a fake).
        $this->app->bind(\App\Services\Market\Ai\MarketLlmClient::class, \App\Services\Market\Ai\GeminiMarketClient::class);

        $this->app->singleton(SettingsRepository::class, function () {
            return new SettingsRepository();
        });

        $this->app->singleton(RecaptchaService::class, function () {
            return new RecaptchaService();
        });

        $this->app->singleton(OtpService::class, function () {
            return new OtpService();
        });

        $this->app->singleton(SmtpConfigurator::class, function ($app) {
            return new SmtpConfigurator($app->make(SettingsRepository::class));
        });

        $this->app->singleton(RevenueShareService::class, function ($app) {
            return new RevenueShareService($app->make(SettingsRepository::class));
        });

        $this->app->singleton(\App\Services\ThemeService::class, function () {
            return new \App\Services\ThemeService();
        });

        $helpers = app_path('Support/helpers.php');

        if (file_exists($helpers)) {
            require_once $helpers;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(160)->by($request->user()?->id ?: $request->ip());
        });
        // Every public (class A) API route: per IP, guests and signed-in callers alike.
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute((int) config('security.public_rate_per_minute', 120))->by('public|' . $request->ip());
        });
        // Sensitive (class D) actions: per user and per IP, on top of fresh authentication.
        RateLimiter::for('sensitive', function (Request $request) {
            $limits = [Limit::perMinute((int) config('security.sensitive_per_ip_per_minute', 30))->by('sensitive-ip|' . $request->ip())];
            if ($id = $request->user()?->id) {
                $limits[] = Limit::perMinute((int) config('security.sensitive_per_user_per_minute', 10))->by('sensitive-user|' . $id);
            }

            return $limits;
        });
        // Fira AI for guests: per IP per day, on top of the per-minute limit in the controller (decision D-1).
        RateLimiter::for('ai-guest-daily', function (Request $request) {
            if ($request->user('sanctum') || \Illuminate\Support\Facades\Auth::user()) {
                return Limit::none();
            }

            return Limit::perDay((int) config('security.ai_guest_per_day', 30))->by('ai-guest|' . $request->ip());
        });
        // Public web forms (contact, newsletter, blog comments): per IP.
        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(5)->by('public-form|' . $request->ip());
        });
        // Public coffee market data (guests included): per IP.
        RateLimiter::for('market-public', function (Request $request) {
            return Limit::perMinute((int) config('market.public_rate_per_minute', 60))->by('market-public|' . $request->ip());
        });
        // Public seller lookup by Fira ID (API and web page): per IP, no listing endpoint exists.
        RateLimiter::for('public-seller', function (Request $request) {
            return Limit::perMinute((int) config('verification.public_lookup_per_minute', 30))->by('public-seller|' . $request->ip());
        });
        // Verification review: admins, plus users given the Spatie "reviewer" role.
        \Illuminate\Support\Facades\Gate::define('review-verifications', fn ($user) => \App\Services\Auth\StaffTwoFactor::isStaff($user));
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

        if ($this->shouldDeferBootstrapping()) {
            return;
        }

        if (!$this->settingsTablesAreReady()) {
            return;
        }

        $this->app->make(SmtpConfigurator::class)->apply();

        View::share('platformSettings', platform_settings());
        View::share('brandingSettings', branding_settings());
        View::share('currencyCode', currency_code());
        View::share('currencySymbol', currency_symbol());

        Blade::directive('currency', function ($expression) {
            return "<?php echo currency_format($expression); ?>";
        });

        View::composer('partials.marquee', function ($view) {
            $items = collect(frontend_setting('pages.marquee.items', []))
                ->filter(fn($item) => filled($item['text'] ?? null))
                ->map(function ($item) {
                    $image = $item['image'] ?? '';

                    if (blank($image)) {
                        $imageUrl = asset('assets/front/img/home-1/icon1.png');
                    } elseif (Str::startsWith($image, ['http://', 'https://'])) {
                        $imageUrl = $image;
                    } else {
                        $imageUrl = asset(ltrim($image, '/'));
                    }

                    return [
                        'text' => $item['text'],
                        'image' => $imageUrl,
                    ];
                })
                ->values();

            $view->with([
                'marqueeItems' => $items,
                'marqueeChunks' => $items->chunk(7),
            ]);
        });

        View::composer([
            'partials.header',
            'partials.header-two',
            'partials.header-three',
            'partials.mobile-nav',
            'partials.footer',
            'home.*.partials.header',
            'home.*.partials.footer',
        ], function ($view) {
            $menuLocations = [
                'main-navigation',
                'mobile-navigation',
                'footer-links',
                'quick-links',
            ];

            $menus = Cache::remember('frontend.menus.locations', now()->addHour(), function () use ($menuLocations) {
                return Menu::query()
                    ->active()
                    ->whereIn('location', $menuLocations)
                    ->with([
                        'allItems' => function ($query) {
                            $query->active()->orderBy('order');
                        }
                    ])
                    ->get()
                    ->keyBy('location');
            });

            $menuTrees = [];
            foreach ($menuLocations as $location) {
                $tree = isset($menus[$location])
                    ? $this->buildMenuTree($menus[$location]->allItems)
                    : [];

                if (in_array($location, ['main-navigation', 'mobile-navigation'])) {
                    // Header strictly displays: Home, Courses, Events, Tours, Free Tools
                    $allowedKeywords = ['home', 'course', 'event', 'tour', 'free tool'];
                    $tree = collect($tree)->filter(function ($item) use ($allowedKeywords) {
                        $title = strtolower($item['title'] ?? '');
                        $url = strtolower($item['url'] ?? '');
                        
                        // Explicitly reject about us, subscription, contact, blog, instructor, ai
                        if (Str::contains($title, ['about', 'subscri', 'contact', 'blog', 'instructor', 'ai'])
                            || Str::contains($url, ['about', 'subscri', 'contact', 'blog', 'instructor', 'ai-chat'])) {
                            return false;
                        }

                        // Allow only requested items
                        foreach ($allowedKeywords as $kw) {
                            if (Str::contains($title, $kw) || Str::contains($url, $kw)) {
                                return true;
                            }
                        }

                        return false;
                    })->values()->all();
                }

                $menuTrees[$location] = $tree;
            }

            $view->with('frontendMenus', $menuTrees);

            $view->with('footerBranding', FrontendSetting::getValue('footer.branding', [
                'about_text' => 'Fira Link Business Solution is Ethiopia’s premier import-export training and business development hub, dedicated to building global trade capabilities and empowering entrepreneurs.',
                'tagline' => 'Art of Excellence!!',
                'copyright' => '© ' . date('Y') . ' Fira Link Business Solution. All rights reserved.',
            ]));

            $view->with('footerContact', FrontendSetting::getValue('footer.contact', [
                'email' => 'info@firalink.com',
                'phone' => '+251 911 123456',
                'address' => 'Bole Sub-City, Addis Ababa, Ethiopia',
                'hours' => 'Mon - Sat: 8:30am - 5:30pm (EAT)',
            ]));

            $view->with('footerSocialLinks', FrontendSetting::getValue('footer.social_links', []));
        });
    }

    private function shouldDeferBootstrapping(): bool
    {
        if (!app()->runningInConsole()) {
            return false;
        }

        $arguments = collect($_SERVER['argv'] ?? [])
            ->implode(' ');

        return Str::contains($arguments, [
            'migrate',
            'db:seed',
            'db:wipe',
            'db:refresh',
            'db:fresh',
            'db:install',
        ]);
    }

    private function settingsTablesAreReady(): bool
    {
        try {
            return Schema::hasTable('platform_settings')
                && Schema::hasTable('frontend_settings');
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function buildMenuTree(Collection $items, ?int $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->sortBy('order')
            ->map(function ($item) use ($items) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $item->resolved_url,
                    'target' => $item->target,
                    'children' => $this->buildMenuTree($items, $item->id),
                ];
            })
            ->values()
            ->all();
    }

}
