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
            // Check modern organization verification
            if ($org && $org->isVerifiedAs($role)) {
                $isAllowed = true;
                break;
            }

            // Legacy backward-compatibility for sellers
            if (($role === 'exporter' || $role === 'supplier' || $role === 'seller') && ($user->sellerProfile?->isVerified() ?? false)) {
                $isAllowed = true;
                break;
            }
        }

        if ($isAllowed) {
            return $next($request);
        }

        // Determine current verification status across the allowed roles
        $verifications = $org ? $org->verifications()->whereIn('target_role', $allowedRoles)->get() : collect();
        
        $currentStatus = 'unsubmitted';
        $activeRecord = null;

        // Priority order for status reporting
        $statusPriority = [
            OrganizationVerification::STATUS_UNDER_SCRUTINY => 5,
            OrganizationVerification::STATUS_PENDING_REVIEW => 4,
            OrganizationVerification::STATUS_SUSPENDED => 3,
            OrganizationVerification::STATUS_EXPIRED => 2,
            OrganizationVerification::STATUS_REJECTED => 1,
        ];

        $highestPriority = 0;
        foreach ($verifications as $v) {
            $p = $statusPriority[$v->status] ?? 0;
            if ($p > $highestPriority) {
                $highestPriority = $p;
                $currentStatus = $v->status;
                $activeRecord = $v;
            }
        }

        $roleLabels = array_map(function ($r) {
            return match ($r) {
                'exporter' => 'Licensed Exporter',
                'supplier' => 'Coffee Supplier',
                'buyer' => 'Coffee Buyer',
                'broker' => 'Coffee Broker',
                default => ucfirst($r),
            };
        }, $allowedRoles);

        $joinedRoles = implode(' or ', $roleLabels);

        $msg = match ($currentStatus) {
            OrganizationVerification::STATUS_PENDING_REVIEW => "Your {$joinedRoles} verification dossier is under compliance review by Fira Link.",
            OrganizationVerification::STATUS_UNDER_SCRUTINY => "Your {$joinedRoles} credentials are being audited with regulatory authorities (ECTA/MoTI).",
            OrganizationVerification::STATUS_REJECTED => "Your {$joinedRoles} verification was rejected. Reason: " . ($activeRecord?->rejection_reason ?? 'Please resubmit valid credentials.'),
            OrganizationVerification::STATUS_EXPIRED => "Your {$joinedRoles} credentials or license have expired. Please submit renewal documents.",
            OrganizationVerification::STATUS_SUSPENDED => "Your {$joinedRoles} trading permissions are temporarily suspended.",
            default => "Trust is Never For Sale: Only verified {$joinedRoles} accounts may execute this action. Please complete verification onboarding.",
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
