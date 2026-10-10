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
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (session('impersonating_admin_id')) {
            if ($role === 'instructor' && session('impersonating_instructor_id')) {
                return $next($request);
            }
            if ($role === 'student' && session('impersonating_student_id')) {
                return $next($request);
            }
        }
        
        if ($user->role !== $role) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}

