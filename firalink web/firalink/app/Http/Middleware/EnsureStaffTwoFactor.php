<?php

namespace App\Http\Middleware;

use App\Services\Auth\StaffTwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins and reviewers must enrol an authenticator app and pass its code after every sign-in
 * before any admin or review page works. JSON callers get 403 TWO_FACTOR_REQUIRED.
 */
class EnsureStaffTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !StaffTwoFactor::isStaff($user)) {
            return $next($request);
        }
        if (StaffTwoFactor::sessionPassed($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'TWO_FACTOR_REQUIRED',
                'message' => __('verification.admin.two_factor_required'),
            ], 403);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return StaffTwoFactor::enrolled($user)
            ? redirect()->route('staff.two-factor.challenge')
            : redirect()->route('staff.two-factor.setup')->with('warning', __('verification.admin.two_factor_required'));
    }
}
