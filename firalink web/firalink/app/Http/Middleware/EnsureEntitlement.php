<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntitlement
{
    protected EntitlementService $entitlementService;

    public function __construct(EntitlementService $entitlementService)
    {
        $this->entitlementService = $entitlementService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $featureKey
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Platform Super Admins bypass plan entitlement caps
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Resolve Organization
        $org = $user->primaryOrganization();
        if (!$org) {
            // Check if seller profile exists to bridge
            if ($user->sellerProfile) {
                $org = Organization::firstOrCreate(
                    ['slug' => $user->sellerProfile->company_slug ?? 'org-' . $user->id],
                    [
                        'name' => $user->sellerProfile->company_name ?? 'Exporter ' . $user->name,
                        'business_type' => $user->sellerProfile->business_type ?? 'exporter',
                        'status' => 'active',
                        'billing_email' => $user->email,
                    ]
                );
                $user->sellerProfile->update(['organization_id' => $org->id]);
                $user->organizations()->syncWithoutDetaching([$org->id => ['role' => 'owner', 'is_active' => true]]);
            } else {
                if ($request->expectsJson()) {
                    return response()->json([
                        'code' => 'no_organization',
                        'message' => 'An active organization profile is required to access this feature.',
                    ], 403);
                }
                return redirect()->route('marketplace.dashboard')
                    ->with('warning', 'Please complete your organization profile to access this capability.');
            }
        }

        // Verify entitlement
        if (!$this->entitlementService->can($org, $featureKey)) {
            $errorData = $this->entitlementService->limitReachedResponse($org, $featureKey);

            if ($request->expectsJson()) {
                return response()->json($errorData, 403);
            }

            return redirect()->route('marketplace.pricing')
                ->with('warning', $errorData['message']);
        }

        // Attach resolved organization to request for downstream controllers
        $request->attributes->set('current_organization', $org);

        return $next($request);
    }
}
