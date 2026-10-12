<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsurePhoneIsVerified Middleware
 *
 * Enforces mandatory phone number verification across web and API requests.
 * Unverified accounts cannot access protected resources.
 *
 * @package App\Http\Middleware
 */
class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role !== 'admin' && !$user->hasVerifiedPhone()) {
            // Whitelist verification endpoints and logout
            if (
                $request->routeIs('verification.phone.*') ||
                $request->routeIs('logout') ||
                $request->is('api/auth/phone/verify/*') ||
                $request->is('api/auth/register/verify') ||
                $request->is('api/auth/register/resend') ||
                $request->is('api/auth/register/change-phone')
            ) {
                return $next($request);
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'                     => false,
                    'message'                     => 'Your phone number must be verified via SMS OTP before accessing platform features.',
                    'requires_phone_verification' => true,
                    'phone'                       => $user->phone,
                    'phone_masked'                => \App\Models\SmsLog::maskPhone($user->phone ?? ''),
                ], 403);
            }

            return redirect()->route('verification.phone.notice');
        }

        return $next($request);
    }
}
