<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes denied attempts to the audit log: 403 answers, and 401 answers to a request that sent a
 * bearer token (an invalid, expired, revoked or leftover token). Plain guests without a token are
 * not logged. Stores who, which route, when and from where; never the token, body or query string.
 *
 * At most one entry per user (or IP), route and status in `security.denied_audit_window_minutes`.
 */
class LogDeniedRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->record($request, $response);
        } catch (\Throwable $e) {
            report($e); // logging must never change the answer
        }

        return $response;
    }

    private function record(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();
        $hasBearer = $request->bearerToken() !== null;
        if (!($status === 403 || ($status === 401 && $hasBearer))) {
            return;
        }

        $user = $status === 403 ? $request->user() : null;
        $route = $request->route()?->uri() ?? $request->path();
        $who = $user ? 'u' . $user->id : 'ip' . $request->ip();
        $window = (int) config('security.denied_audit_window_minutes', 10);

        if (!Cache::add('denied-audit|' . sha1($who . '|' . $request->method() . '|' . $route . '|' . $status), 1, now()->addMinutes($window))) {
            return;
        }

        $code = null;
        if (str_contains((string) $response->headers->get('Content-Type'), 'json')) {
            $code = json_decode((string) $response->getContent(), true)['code'] ?? null;
        }

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $status === 401 ? 'security.token_rejected' : 'security.denied',
            'before_state' => null,
            'after_state' => [
                'method' => $request->method(),
                'route' => $route,
                'status' => $status,
                'code' => is_string($code) ? mb_substr($code, 0, 60) : null,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}
