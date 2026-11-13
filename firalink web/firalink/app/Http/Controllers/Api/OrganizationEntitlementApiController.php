<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationEntitlementApiController extends Controller
{
    protected EntitlementService $entitlementService;

    public function __construct(EntitlementService $entitlementService)
    {
        $this->entitlementService = $entitlementService;
    }

    /**
     * GET /api/org/{id}/entitlements
     * Returns full entitlement snapshot for the organization.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        // Authorization check: must be a member of the organization or super admin (prevents IDOR)
        if ($user && !$user->isAdmin() && !$org->hasMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not belong to this organization.',
            ], 403);
        }

        $snapshot = $this->entitlementService->snapshot($org);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
        ]);
    }

    /**
     * GET /api/user/organization/entitlements
     * Returns entitlement snapshot for the authenticated user's current organization.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $org = $user->primaryOrganization();
        if (!$org) {
            return response()->json([
                'success' => false,
                'message' => 'No active organization found for user.',
            ], 404);
        }

        $snapshot = $this->entitlementService->snapshot($org);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
        ]);
    }
}
