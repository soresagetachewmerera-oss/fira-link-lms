<?php

namespace App\Providers;

use App\Models\Event;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Auth Service Provider
 *
 * This service provider registers the authentication policies.
 *
 * @package App\Providers
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Event::class => EventPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
