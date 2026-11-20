<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class OrganizationApiKeyController extends Controller
{
    protected EntitlementService $entitlementService;

    public function __construct(EntitlementService $entitlementService)
    {
        $this->entitlementService = $entitlementService;
    }

    private function resolveOrg(Request $request, int $id): ?Organization
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $org = Organization::find($id);
        if (!$org) {
            return null;
        }

        if ($user->isAdmin()) {
            return $org;
        }

        $membership = $org->memberships()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'], true)) {
            return null;
        }

        return $org;
    }

    /**
     * GET /api/organizations/{id}/api-tokens
     * List active API keys for this organization.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $org = $this->resolveOrg($request, $id);
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        $tokenAbility = "org:{$org->id}";
        $tokens = PersonalAccessToken::where('tokenable_id', $request->user()->id)
            ->where('tokenable_type', get_class($request->user()))
            ->get()
            ->filter(fn (PersonalAccessToken $t) => in_array($tokenAbility, $t->abilities ?? [], true))
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
                'expires_at' => $t->expires_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * POST /api/organizations/{id}/api-tokens
     * Generate new REST API key. Gated by 'api_access' entitlement.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $org = $this->resolveOrg($request, $id);
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        // Enforce SaaS plan entitlement
        if (!$this->entitlementService->can($org, 'api_access')) {
            return response()->json(
                array_merge(['success' => false], $this->entitlementService->limitReachedResponse($org, 'api_access')),
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_FAILED',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $name = trim($request->input('name'));
        $abilities = ["org:{$org->id}"];

        $newAccessToken = $user->createToken($name, $abilities);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'api_token_created',
            'metadata' => ['token_id' => $newAccessToken->accessToken->id, 'name' => $name],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $newAccessToken->accessToken->id,
                'name' => $name,
                'token' => $newAccessToken->plainTextToken,
                'created_at' => $newAccessToken->accessToken->created_at?->toIso8601String(),
            ],
            'message' => 'API token created successfully. Store this secret safely; it will not be displayed again.',
        ], 201);
    }

    /**
     * DELETE /api/organizations/{id}/api-tokens/{tokenId}
     * Revoke personal access token.
     */
    public function destroy(Request $request, int $id, int $tokenId): JsonResponse
    {
        $org = $this->resolveOrg($request, $id);
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        $tokenAbility = "org:{$org->id}";
        $token = PersonalAccessToken::where('id', $tokenId)
            ->where('tokenable_id', $request->user()->id)
            ->where('tokenable_type', get_class($request->user()))
            ->first();

        if (!$token || !in_array($tokenAbility, $token->abilities ?? [], true)) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        $tokenName = $token->name;
        $token->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $org->id,
            'action' => 'api_token_revoked',
            'metadata' => ['token_id' => $tokenId, 'name' => $tokenName],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API token revoked successfully.',
        ]);
    }
}
