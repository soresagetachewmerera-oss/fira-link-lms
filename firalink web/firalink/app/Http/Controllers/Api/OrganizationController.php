<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    /**
     * GET /api/user/organizations
     * List all workspaces/organizations the authenticated user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentOrg = $user->currentOrganization();

        $memberships = OrganizationMembership::with('organization')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $organizations = $memberships->map(function ($membership) use ($currentOrg) {
            $org = $membership->organization;
            if (!$org) {
                return null;
            }
            return [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'business_type' => $org->business_type,
                'is_verified' => (bool) $org->is_verified,
                'verified_roles' => $org->verified_roles ?? [],
                'country' => $org->country,
                'role' => $membership->role,
                'is_active_workspace' => $currentOrg && $currentOrg->id === $org->id,
                'joined_at' => $membership->joined_at,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'current_organization' => $currentOrg ? [
                    'id' => $currentOrg->id,
                    'name' => $currentOrg->name,
                    'slug' => $currentOrg->slug,
                    'business_type' => $currentOrg->business_type,
                    'is_verified' => (bool) $currentOrg->is_verified,
                    'verified_roles' => $currentOrg->verified_roles ?? [],
                    'country' => $currentOrg->country,
                ] : null,
                'organizations' => $organizations,
                'onboarding_intent' => $user->onboarding_intent,
            ],
        ]);
    }

    /**
     * POST /api/user/organizations
     * Create a new organization / business workspace.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'business_type' => 'required|string|in:exporter,supplier,buyer,broker,cooperative,commercial_farm,union,roaster',
            'country' => 'nullable|string|max:100',
            'billing_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $slug = Str::slug($request->input('name'));
        if (Organization::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(5);
        }

        $org = Organization::create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'business_type' => $request->input('business_type'),
            'country' => $request->input('country', 'Ethiopia'),
            'billing_email' => $request->input('billing_email', $user->email),
            'status' => 'active',
            'is_verified' => false,
            'verified_roles' => [],
        ]);

        OrganizationMembership::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => OrganizationMembership::ROLE_OWNER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Auto-switch to newly created workspace
        $user->active_organization_id = $org->id;
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'organization_created',
            'auditable_type' => Organization::class,
            'auditable_id' => $org->id,
            'after_state' => $org->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Workspace '{$org->name}' created successfully.",
            'data' => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'business_type' => $org->business_type,
                'is_verified' => false,
                'verified_roles' => [],
            ],
        ], 201);
    }

    /**
     * POST /api/user/organizations/switch
     * Switch active workspace for the authenticated session.
     */
    public function switchWorkspace(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|integer|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $orgId = (int) $request->input('organization_id');
        $success = $user->switchOrganization($orgId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have active access to this organization.',
            ], 403);
        }

        $org = Organization::find($orgId);

        return response()->json([
            'success' => true,
            'message' => "Switched active workspace to '{$org->name}'.",
            'data' => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'business_type' => $org->business_type,
                'is_verified' => (bool) $org->is_verified,
                'verified_roles' => $org->verified_roles ?? [],
            ],
        ]);
    }

    /**
     * GET /api/organizations/{id}/members
     * List members of a workspace.
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        // Authorization check
        $userMembership = OrganizationMembership::where('organization_id', $org->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$userMembership && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to organization roster.',
            ], 403);
        }

        $members = OrganizationMembership::with('user')
            ->where('organization_id', $org->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $m->user?->name,
                    'email' => $m->user?->email,
                    'phone' => $m->user?->phone,
                    'role' => $m->role,
                    'joined_at' => $m->joined_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'members' => $members,
            ],
        ]);
    }

    /**
     * POST /api/organizations/{id}/members
     * Invite/add a member to a workspace.
     */
    public function addMember(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        if (!$org->canManageBilling($user) && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workspace owners and managers can add members.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string|in:owner,manager,staff',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetUser = User::where('email', $request->input('email'))->first();

        $membership = OrganizationMembership::updateOrCreate(
            [
                'organization_id' => $org->id,
                'user_id' => $targetUser->id,
            ],
            [
                'role' => $request->input('role'),
                'is_active' => true,
                'invited_by' => $user->id,
                'joined_at' => now(),
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'member_added',
            'auditable_type' => OrganizationMembership::class,
            'auditable_id' => $membership->id,
            'after_state' => $membership->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$targetUser->name} added as {$membership->role}.",
            'data' => [
                'id' => $membership->id,
                'user_id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $membership->role,
            ],
        ]);
    }

    /**
     * DELETE /api/organizations/{id}/members/{userId}
     * Remove a member from a workspace.
     */
    public function removeMember(Request $request, int $id, int $userId): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        if (!$org->canManageBilling($user) && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workspace owners and managers can remove members.',
            ], 403);
        }

        if ($user->id === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove yourself from the workspace. Transfer ownership first.',
            ], 422);
        }

        $membership = OrganizationMembership::where('organization_id', $org->id)
            ->where('user_id', $userId)
            ->first();

        if ($membership) {
            $membership->update(['is_active' => false]);
            AuditLog::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'action' => 'member_removed',
                'auditable_type' => OrganizationMembership::class,
                'auditable_id' => $membership->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully.',
        ]);
    }

    /**
     * POST /api/user/onboarding-intent
     * Save initial user intent (export, buy, learn, events) after registration.
     */
    public function saveIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'intent' => 'required|string|in:export,buy,learn,events',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->onboarding_intent = $request->input('intent');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Onboarding intent saved.',
            'data' => [
                'intent' => $user->onboarding_intent,
            ],
        ]);
    }
}

