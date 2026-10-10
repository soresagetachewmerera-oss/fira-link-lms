<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Check Approved Instructor Middleware
 *
 * This middleware checks if the user is an approved instructor.
 *
 * @package App\Http\Middleware
 */
class CheckApprovedInstructor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (session('impersonating_admin_id')) {
            return $next($request);
        }
        
        if ($user->role !== 'instructor') {
            abort(403, 'Only instructors can access this page');
        }

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        return $next($request);
    }
}

