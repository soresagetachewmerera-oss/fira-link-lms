<?php

namespace App\Http\Middleware;

use App\Models\BannedIdentity;
use App\Models\OrganizationVerification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationVerified
{
    /**
     * Handle an incoming request.
     * Supports single or multiple permitted roles, e.g.:
     * middleware('verified.role:buyer')
     * middleware('verified.role:buyer,broker')
     * middleware('verified.role:exporter,supplier')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Please sign in to proceed.',
                ], 401);
            }
            return redirect()->route('login')->with('warning', 'Please sign in to continue.');
        }

        // Platform administrators bypass commercial checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Parse and normalize allowed roles (handling variadic or comma-separated tokens)
        $allowedRoles = [];
        if (empty($roles)) {
            $roles = ['exporter'];
        }
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $part) {
                $trimmed = strtolower(trim($part));
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }
        if (empty($allowedRoles)) {
            $allowedRoles = ['exporter'];
        }

        $org = $user->primaryOrganization();

        // 1. Sanctions & Blacklist Check (Banned Identity Guard)
        $isBanned = BannedIdentity::where(function ($q) use ($org, $user) {
            if ($org) {
                $q->where('company_name', 'LIKE', '%' . $org->name . '%');
            }
            if ($user->phone) {
                $q->orWhere('phone', $user->phone);
            }
            if ($user->email) {
                $q->orWhere('email', $user->email);
            }
        })->exists();

        if ($isBanned) {
            $msg = 'This organization has been barred from platform transactions due to compliance enforcement.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'BANNED_IDENTITY',
                    'message' => $msg,
                ], 403);
            }
            return redirect()->route('marketplace.dashboard')->with('error', $msg);
        }

        // 2. Multi-Role Verification Check
        $isAllowed = false;
        foreach ($allowedRoles as $role) {
            // Slice 3: Tier 1+ in this business role, computed from reviewed documents only.
            // (The legacy seller-profile flag no longer counts.)
            if ($org && $org->isVerifiedAs($role)) {
                $isAllowed = true;
                break;
            }
        }

        if ($isAllowed) {
            return $next($request);
        }

        // Status from the computed tier and the documents (slice 3), never from a plan.
        $currentStatus = $org ? $org->workspaceStatus() : 'not_started';
        $msg = match ($currentStatus) {
            'in_review' => 'Your verification documents are in review.',
            'needs_changes' => 'Some verification documents need changes. Open Verification to fix them.',
            'expired' => 'Your verification expired. Upload the renewed documents.',
            'suspended' => 'This business is suspended by Fira Link.',
            default => 'Tier 1 verification is required for this action. Paid plans never buy verification.',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'VERIFICATION_REQUIRED',
                'required_roles' => $allowedRoles,
                'required_role' => $allowedRoles[0], // for single-role clients
                'verification_status' => $currentStatus,
                'message' => $msg,
            ], 403);
        }

        return redirect()->route('marketplace.verification.center')->with('warning', $msg);
    }
}
