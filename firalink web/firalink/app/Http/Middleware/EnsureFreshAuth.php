<?php

namespace App\Http\Middleware;

use App\Services\Auth\FreshAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sensitive actions (bank or payout details, verification documents, payment confirmation,
 * password or phone changes) need fresh authentication even when signed in:
 * a code or the password within the last 10 minutes, or the current password in the request.
 *
 * API clients get 403 FRESH_AUTH_REQUIRED and should call
 * POST /api/auth/step-up/code then POST /api/auth/step-up/verify, then retry.
 */
class EnsureFreshAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (FreshAuth::isFresh($request) || FreshAuth::passwordInRequest($request, $user))) {
            return $next($request);
        }

        $message = 'For your security, confirm it is you before doing this.';

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'FRESH_AUTH_REQUIRED',
                'message' => $message,
                'window_minutes' => FreshAuth::WINDOW_MINUTES,
            ], 403);
        }

        return back()->withErrors([
            'fresh_auth' => 'For your security, sign out and sign in again, then repeat this action within '
                . FreshAuth::WINDOW_MINUTES . ' minutes.',
        ]);
    }
}
