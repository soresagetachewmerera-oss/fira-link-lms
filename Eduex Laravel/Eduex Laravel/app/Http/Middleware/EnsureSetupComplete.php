<?php

namespace App\Http\Middleware;

use App\Services\Installer\SetupState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupComplete
{
    public function __construct(
        protected SetupState $state
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->isInstalled() || $this->isSetupRoute($request)) {
            return $next($request);
        }

        return redirect()->route('setup.welcome');
    }

    protected function isSetupRoute(Request $request): bool
    {
        return $request->is('install', 'install/*')
            || $request->routeIs('setup.*');
    }
}

