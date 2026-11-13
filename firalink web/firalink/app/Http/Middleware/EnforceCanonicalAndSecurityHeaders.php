<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalAndSecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only inspect GET / HEAD for canonical redirects
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $path = $request->getPathInfo();
            $canonicalHost = parse_url(config('seo.canonical_domain', 'https://fira-link.com'), PHP_URL_HOST);
            $currentHost = $request->getHost();

            // 1. Redirect www to non-www in production / when canonicalHost is set
            if ($canonicalHost && !in_array($currentHost, ['localhost', '127.0.0.1']) && Str::startsWith($currentHost, 'www.')) {
                $targetHost = Str::replaceFirst('www.', '', $currentHost);
                $targetUrl = 'https://' . $targetHost . $request->getRequestUri();
                return redirect()->to($targetUrl, 301);
            }

            // 2. Trailing slash normalization (redirect /page/ -> /page)
            if ($path !== '/' && Str::endsWith($path, '/')) {
                $cleanPath = rtrim($path, '/');
                $queryString = $request->getQueryString();
                $targetUrl = $cleanPath . ($queryString ? '?' . $queryString : '');

                return redirect()->to($targetUrl, 301);
            }
        }

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');

            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data: blob:; media-src 'self' https: blob:; frame-src 'self' https: data:; frame-ancestors 'self';"
            );

        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
