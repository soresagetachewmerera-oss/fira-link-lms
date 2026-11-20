<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Default deny for routes (auth gating 2026-11-20, S-1). A new route must either sit behind auth
 * middleware or be added on purpose to config/public_routes.php, and sensitive actions must keep
 * fresh authentication. No database needed.
 */
class RouteInventoryTest extends TestCase
{
    /** Public categories that may write (POST/PUT/PATCH/DELETE). */
    private const WRITE_OK = ['auth', 'compute', 'callback', 'session', 'install'];

    /**
     * Class D: signed in + fresh authentication (code or password within 10 minutes) + the per-user
     * "sensitive" limit. Removing the middleware from any of these must fail the build.
     */
    private const SENSITIVE = [
        'POST api/user/password',
        'POST api/user/phone',
        'POST api/user/email/code',
        'POST api/user/email',
        'POST api/user/delete-request',
        'POST api/payout-account',
        'POST api/marketplace/verification/documents',
        'POST api/deal-rooms/{id}/milestones/{milestoneId}/confirm',
        'POST api/instructor/settings/password',
        'POST api/instructor/settings/delete-request',
        'POST api/instructor/withdrawals',
        'POST instructor/withdrawals',
        'PUT instructor/settings/password',
        'POST marketplace/verification/upload-document',
        // Admin and reviewer (staff 2FA + "confirm it is you")
        'POST admin/payments/{id}/approve',
        'POST admin/payments/{id}/reject',
        'PUT admin/payments/{id}/update-status',
        'PUT admin/withdrawals/{withdrawal}/status',
        'PUT admin/account-deletions/{accountDeletionRequest}/approve',
        'DELETE admin/users/{user}',
        'POST admin/marketplace/subscriptions/{id}/approve',
        'PUT admin/settings/ai',
        'PUT admin/settings/smtp',
        'PUT admin/settings/authentication',
        'PUT admin/settings/recaptcha',
        'PUT admin/settings/revenue',
        'PUT admin/settings/withdrawals',
        'PUT admin/settings/custom-scripts',
        'PUT admin/settings/sms/provider',
        'PUT admin/settings/gateways/{identifier}',
        'POST admin/settings/gateways/{identifier}/test',
        'POST admin/verification-review/businesses/{organization}/suspend',
        'POST admin/verification-review/businesses/{organization}/unsuspend',
        'POST admin/verification-review/payouts/{payout}/confirm',
        'POST admin/verification-review/payouts/{payout}/reject',
    ];

    /** @return array<string, array{route: \Illuminate\Routing\Route, middleware: string[], methods: string[]}> */
    private function inventory(): array
    {
        $router = app('router');
        $rows = [];
        foreach (Route::getRoutes() as $route) {
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $uri = $route->uri() === '/' ? '/' : ltrim($route->uri(), '/');
            $rows[implode('|', $methods) . ' ' . $uri] = [
                'route' => $route,
                'middleware' => $router->gatherRouteMiddleware($route),
                'methods' => $methods,
            ];
        }

        return $rows;
    }

    private function hasAuthMiddleware(array $middleware): bool
    {
        foreach ($middleware as $m) {
            if (str_starts_with($m, \Illuminate\Auth\Middleware\Authenticate::class)) {
                return true;
            }
        }

        return false;
    }

    private function writes(array $methods): bool
    {
        return (bool) array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    public function test_every_route_without_auth_is_on_the_public_allow_list(): void
    {
        $allow = config('public_routes');
        $missing = [];
        foreach ($this->inventory() as $key => $row) {
            if (!$this->hasAuthMiddleware($row['middleware']) && !array_key_exists($key, $allow)) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, "Routes without auth middleware that are not in config/public_routes.php.\n"
            . "Put them inside an auth group, or add them to the allow-list on purpose:\n" . implode("\n", $missing));
    }

    public function test_the_allow_list_has_no_stale_or_protected_entries(): void
    {
        $inventory = $this->inventory();
        $stale = [];
        foreach (array_keys(config('public_routes')) as $key) {
            if (!isset($inventory[$key])) {
                $stale[] = $key . ' (no such route)';
            } elseif ($this->hasAuthMiddleware($inventory[$key]['middleware'])) {
                $stale[] = $key . ' (has auth middleware; remove it from the allow-list)';
            }
        }

        $this->assertSame([], $stale, implode("\n", $stale));
    }

    public function test_public_routes_that_write_are_only_sign_in_tools_callbacks_or_the_installer(): void
    {
        $bad = [];
        foreach (config('public_routes') as $key => $category) {
            [$methods] = explode(' ', $key, 2);
            $methods = explode('|', $methods);
            if ($this->writes($methods) && !in_array($category, self::WRITE_OK, true)) {
                $bad[] = "{$key} is '{$category}' but writes";
            }
        }

        $this->assertSame([], $bad, implode("\n", $bad));
    }

    public function test_every_public_api_route_is_rate_limited(): void
    {
        $unlimited = [];
        foreach ($this->inventory() as $key => $row) {
            if (!str_starts_with($row['route']->uri(), 'api/') || $this->hasAuthMiddleware($row['middleware'])) {
                continue;
            }
            $limited = collect($row['middleware'])->contains(fn ($m) => str_starts_with($m, \Illuminate\Routing\Middleware\ThrottleRequests::class));
            if (!$limited) {
                $unlimited[] = $key;
            }
        }

        $this->assertSame([], $unlimited, "Public API routes without a rate limit:\n" . implode("\n", $unlimited));
    }

    public function test_sensitive_actions_need_fresh_authentication_and_the_sensitive_limit(): void
    {
        $inventory = $this->inventory();
        $problems = [];
        foreach (self::SENSITIVE as $key) {
            if (!isset($inventory[$key])) {
                $problems[] = "{$key}: route not found";
                continue;
            }
            $mw = $inventory[$key]['middleware'];
            if (!$this->hasAuthMiddleware($mw)) {
                $problems[] = "{$key}: no auth";
            }
            if (!in_array(\App\Http\Middleware\EnsureFreshAuth::class, $mw, true)) {
                $problems[] = "{$key}: no fresh authentication";
            }
            if (!collect($mw)->contains(fn ($m) => str_ends_with($m, 'ThrottleRequests:sensitive'))) {
                $problems[] = "{$key}: no per-user sensitive limit";
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_admin_and_reviewer_routes_need_staff_two_factor(): void
    {
        $missing = [];
        foreach ($this->inventory() as $key => $row) {
            $uri = $row['route']->uri();
            if (!str_starts_with($uri, 'admin/') && !str_starts_with($uri, 'staff/confirm')) {
                continue;
            }
            if (!in_array(\App\Http\Middleware\EnsureStaffTwoFactor::class, $row['middleware'], true)) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, "Admin routes without staff two-factor:\n" . implode("\n", $missing));
    }

    public function test_impersonation_is_gone(): void
    {
        foreach (array_keys($this->inventory()) as $key) {
            $this->assertStringNotContainsString('impersonat', $key);
        }
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Admin/ImpersonationController.php'));
    }
}
