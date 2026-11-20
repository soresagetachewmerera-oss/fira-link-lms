<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
/**
 * Check Role Middleware
 *
 * This middleware checks if the user has the required role.
 *
 * @package App\Http\Middleware
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if ($user->role !== $role) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}

