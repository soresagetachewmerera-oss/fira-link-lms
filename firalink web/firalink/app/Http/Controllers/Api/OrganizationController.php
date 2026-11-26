<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BusinessCategory;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Trade\CapabilityService;
use App\Services\Verification\VerificationException;
use App\Services\Verification\VerificationReviewService;
use App\Support\BusinessEntityType;
use App\Support\BusinessRole;
use App\Support\Capability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Workspaces (slice 2): one account has a Personal space plus up to N business workspaces.
 * Personal = no active business (users.active_organization_id is null). Every read checks
 * an active membership; nobody switches into a business they do not belong to (admins use
 * admin screens). Workspace status comes only from verification records.
 */
class OrganizationController extends Controller
{
    public const INTENTS = ['export', 'buy', 'learn', 'events'];

    private function workspaceJson(Organization $org, OrganizationMembership $membership, ?Organization $current): array
    {
        return [
            'id' => $org->id,
            'name' => $org->name,
            'slug' => $org->slug,
            'business_role' => $org->business_role ?? $org->business_type,
            // Release 2a: the category (a label) and the activities that count right now.
            'business_category' => $org->businessCategory?->only(['code', 'label_en', 'label_am']),
            'active_capabilities' => array_values(array_filter(CapabilityService::declared($org), fn ($c) => CapabilityService::isActive($org, $c))),
            'entity_type' => $org->entity_type,
            'country' => $org->country,
            'has_tin' => !empty($org->tin_number),
            'membership_role' => $membership->role,
            'status' => $org->workspaceStatus(),
            // Legacy fields for older app versions: derived from the active tier (not expired, not
            // suspended), never the stored flags, which can lag behind an expiry or a suspension.
            'is_verified' => $org->hasActiveBadge(),
            'verified_roles' => $org->hasActiveBadge() ? ($org->verified_roles ?? []) : [],
            'visible_to_buyers' => VerificationReviewService::isApprovedSeller($org),
            'needs_manual_review' => (bool) $org->needs_manual_review,
            'is_active_workspace' => $current?->id === $org->id,
            'joined_at' => $membership->joined_at,
        ];
    }

    private function ownedCount(User $user): int
    {
        return OrganizationMembership::where('user_id', $user->id)
            ->where('role', OrganizationMembership::ROLE_OWNER)
            ->where('is_active', true)
            ->whereHas('organization')
            ->count();
    }

    /**
     * GET /api/workspaces/options — entity types, roles and countries for Business setup.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'entity_types' => BusinessEntityType::options(),
                'business_roles' => BusinessRole::values(),
                // Release 2a: admin-managed categories (labels in both languages) and the activities each allows.
                'business_categories' => BusinessCategory::active()->orderBy('sort_order')->orderBy('id')->get()->map->toOption()->values(),
                'capabilities' => array_map(fn ($c) => ['code' => $c, 'required_documents' => CapabilityService::requiredDocuments($c)], Capability::values()),
                'countries' => config('workspaces.countries'),
                'default_country' => 'Ethiopia',
                'max_owned_businesses' => (int) config('workspaces.max_owned_businesses'),
            ],
        ]);
    }

    /**
     * GET /api/user/organizations — Personal space plus every business the user is an active member of.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentOrganization();

        $workspaces = OrganizationMembership::with('organization')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($m) => $m->organization !== null)
            ->map(fn ($m) => $this->workspaceJson($m->organization, $m, $current))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'personal' => [
                    'name' => $user->name,
                    'is_active_workspace' => $current === null,
                ],
                'current_organization' => $current
                    ? $workspaces->firstWhere('id', $current->id)
                    : null,
                'organizations' => $workspaces,
                'limits' => [
                    'max_owned_businesses' => (int) config('workspaces.max_owned_businesses'),
                    'owned_businesses' => $this->ownedCount($user),
                ],
                'onboarding_intents' => $user->onboarding_intents ?? ($user->onboarding_intent ? [$user->onboarding_intent] : []),
                'onboarding_intent' => $user->onboarding_intent,
            ],
        ]);
    }

    /**
     * POST /api/user/organizations — Business setup (S-103).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $isEthiopia = $request->input('country', 'Ethiopia') === 'Ethiopia';
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:160', 'regex:/\S/'],
            // Newer apps send a category; its role is used. Older apps send the role only.
            'business_category' => ['nullable', 'string', Rule::exists('business_categories', 'code')->where('is_active', true)],
            'business_role' => ['required_without:business_category', 'nullable', Rule::in(BusinessRole::values())],
            'entity_type' => ['required', Rule::in(BusinessEntityType::values())],
            'country' => ['nullable', Rule::in(config('workspaces.countries'))],
            // Ethiopian TIN: 10 digits. Elsewhere: the local tax/VAT number, letters, digits and dashes.
            'tin_number' => $isEthiopia
                ? ['nullable', 'digits:10']
                : ['nullable', 'string', 'min:4', 'max:30', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'billing_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $max = (int) config('workspaces.max_owned_businesses');
        if ($this->ownedCount($user) >= $max) {
            return response()->json([
                'success' => false,
                'code' => 'WORKSPACE_LIMIT',
                'limit' => $max,
                'message' => "You can own up to {$max} business workspaces. Ask support if you need more.",
            ], 422);
        }

        $name = trim(preg_replace('/\s+/', ' ', (string) $request->input('name')));
        $slug = Str::slug($name) ?: 'business';
        if (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug .= '-' . Str::lower(Str::random(6));
        }

        $category = $request->filled('business_category') ? BusinessCategory::where('code', $request->input('business_category'))->first() : null;
        $role = $category?->business_role ?? $request->input('business_role');
        $entity = BusinessEntityType::from($request->input('entity_type'));
        $tin = $request->filled('tin_number') ? preg_replace('/\s+/', '', (string) $request->input('tin_number')) : null;

        $org = Organization::create([
            'name' => $name,
            'slug' => $slug,
            'business_role' => $role,
            'business_type' => $role, // legacy column read by older screens
            'entity_type' => $entity->value,
            'tin_number' => $tin,
            'country' => $request->input('country', 'Ethiopia'),
            'billing_email' => $request->input('billing_email', $user->email),
            'created_by' => $user->id,
        ]);

        // Server-set review flag: entity type "Other" must be checked by an admin.
        if ($entity->needsManualReview()) {
            $org->forceFill(['needs_manual_review' => true])->save();
        }

        OrganizationMembership::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => OrganizationMembership::ROLE_OWNER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->switchOrganization($org->id);

        // The category and all the activities it allows (the owner can switch some off on Activities).
        if ($category) {
            app(CapabilityService::class)->setCategory($org, $category, $user);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'organization_created',
            'auditable_type' => Organization::class,
            'auditable_id' => $org->id,
            'after_state' => $org->only(['name', 'business_role', 'entity_type', 'country', 'needs_manual_review']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->flagMultipleBusinesses($user, $org, $request);

        $membership = OrganizationMembership::where('organization_id', $org->id)->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'message' => "Workspace '{$org->name}' created.",
            'data' => $this->workspaceJson($org->fresh(), $membership, $org),
        ], 201);
    }

    /**
     * One person behind several businesses is normal but worth a look: the same account,
     * the same phone on another account, or the same TIN on another business.
     * Logged for admin review; nothing is blocked.
     */
    private function flagMultipleBusinesses(User $user, Organization $org, Request $request): void
    {
        $ownedIds = OrganizationMembership::where('user_id', $user->id)
            ->where('role', OrganizationMembership::ROLE_OWNER)
            ->where('is_active', true)
            ->pluck('organization_id')
            ->all();

        $signals = [];
        if (count($ownedIds) > 1) {
            $signals['same_owner_account'] = $ownedIds;
        }

        if ($user->phone) {
            $samePhoneOwners = User::where('phone', $user->phone)->where('id', '!=', $user->id)->pluck('id');
            $otherOrgs = $samePhoneOwners->isEmpty() ? collect() : OrganizationMembership::whereIn('user_id', $samePhoneOwners)
                ->where('role', OrganizationMembership::ROLE_OWNER)
                ->pluck('organization_id');
            if ($otherOrgs->isNotEmpty()) {
                $signals['same_owner_phone'] = $otherOrgs->all();
            }
        }

        if ($org->tin_number) {
            $sameTin = Organization::where('tin_number', $org->tin_number)->where('id', '!=', $org->id)->pluck('id');
            if ($sameTin->isNotEmpty()) {
                $signals['same_tin'] = $sameTin->all();
            }
        }

        if ($signals) {
            AuditLog::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'action' => 'owner_multiple_businesses',
                'auditable_type' => Organization::class,
                'auditable_id' => $org->id,
                'after_state' => $signals + ($user->phone ? ['phone_masked' => SmsLog::maskPhone($user->phone)] : []),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * POST /api/user/organizations/switch — {organization_id} or null for Personal.
     */
    public function switchWorkspace(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'organization_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $orgId = $request->filled('organization_id') ? (int) $request->input('organization_id') : null;

        if (!$user->switchOrganization($orgId)) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_A_MEMBER',
                'message' => 'You do not have active access to this workspace.',
            ], 403);
        }

        if ($orgId === null) {
            return response()->json(['success' => true, 'data' => ['personal' => true, 'organization' => null]]);
        }

        $org = Organization::findOrFail($orgId);
        $membership = OrganizationMembership::where('organization_id', $orgId)->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => ['personal' => false, 'organization' => $this->workspaceJson($org, $membership, $org)],
        ]);
    }

    /**
     * GET /api/organizations/{id}/members — read-only roster for active members.
     * Only the owner gets can_remove. Admins use admin screens, not this endpoint.
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        if (!$user->isActiveMemberOf($org->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to organization roster.',
            ], 403);
        }

        $isOwner = $org->isOwner($user);
        $entitlements = app(\App\Services\EntitlementService::class);
        $seatLimit = $entitlements->limit($org, 'seats');
        $seatUsed = $entitlements->used($org, 'seats');
        $canAddMembers = $isOwner && ($seatLimit === -1 || $seatUsed < $seatLimit);

        $members = OrganizationMembership::with('user')
            ->where('organization_id', $org->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'name' => $m->user?->name,
                'email' => $m->user?->email,
                'phone_masked' => $m->user?->phone ? SmsLog::maskPhone($m->user->phone) : null,
                'role' => $m->role,
                'joined_at' => $m->joined_at,
                'can_remove' => $isOwner && $m->user_id !== $user->id,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'viewer_is_owner' => $isOwner,
                'can_add_members' => $canAddMembers,
                'seats' => [
                    'limit' => $seatLimit,
                    'used' => $seatUsed,
                    'remaining' => $seatLimit === -1 ? -1 : max(0, $seatLimit - $seatUsed),
                ],
                'members' => $members,
            ],
        ]);
    }

    /**
     * POST /api/organizations/{id}/members — owner only (the app hides this until seats exist).
     */
    public function addMember(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        if (!$org->isOwner($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the workspace owner can add members.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string|in:manager,staff',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetUser = User::where('email', $request->input('email'))->first();

        // Enforce SaaS Team Seats Quota
        $alreadyActive = OrganizationMembership::where('organization_id', $org->id)
            ->where('user_id', $targetUser->id)
            ->where('is_active', true)
            ->exists();

        $entitlements = app(\App\Services\EntitlementService::class);
        if (!$alreadyActive && !$entitlements->can($org, 'seats')) {
            return response()->json(array_merge([
                'success' => false,
                'code' => 'SEAT_LIMIT_REACHED',
                'message' => 'Team seat limit reached. Upgrade your plan to invite more team members.',
            ], $entitlements->limitReachedResponse($org, 'seats')), 403);
        }

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

        $entitlements->invalidate($org);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'member_added',
            'auditable_type' => OrganizationMembership::class,
            'auditable_id' => $membership->id,
            'after_state' => $membership->only(['user_id', 'role', 'is_active']),
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
                'role' => $membership->role,
            ],
        ]);
    }

    /**
     * DELETE /api/organizations/{id}/members/{userId} — owner only; never yourself.
     */
    public function removeMember(Request $request, int $id, int $userId): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($id);

        if (!$org->isOwner($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the workspace owner can remove members.',
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
            ->where('is_active', true)
            ->first();

        if (!$membership) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        $membership->update(['is_active' => false]);
        app(\App\Services\EntitlementService::class)->invalidate($org);

        // The removed member loses this workspace at once (also enforced on every read).
        User::where('id', $userId)->where('active_organization_id', $org->id)->update(['active_organization_id' => null]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'member_removed',
            'auditable_type' => OrganizationMembership::class,
            'auditable_id' => $membership->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Member removed.']);
    }

    /**
     * POST /api/user/onboarding-intent — Intent (S-102), multi-select and skippable.
     * {intents: ["export","learn"]} (empty = skipped). Legacy {intent: "export"} still accepted.
     */
    public function saveIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'intents' => 'nullable|array|max:4',
            'intents.*' => ['string', Rule::in(self::INTENTS)],
            'intent' => ['nullable', 'string', Rule::in(self::INTENTS)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $intents = $request->has('intents')
            ? array_values(array_unique((array) $request->input('intents', [])))
            : array_filter([$request->input('intent')]);

        $user->forceFill([
            'onboarding_intents' => $intents,
            'onboarding_intent' => $intents[0] ?? null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Onboarding intent saved.',
            'data' => [
                'intents' => $intents,
                'intent' => $intents[0] ?? null,
                // Selling or buying leads to Business setup; everything else goes Home.
                'needs_business_setup' => (bool) array_intersect($intents, ['export', 'buy']),
            ],
        ]);
    }
    /** Active member of this business, or the 403 to return. */
    private function memberOr403(Request $request, int $id): Organization|JsonResponse
    {
        $org = Organization::with('businessCategory')->findOrFail($id);
        if (!$request->user()->isActiveMemberOf($org->id)) {
            return response()->json(['success' => false, 'code' => 'NOT_A_MEMBER', 'message' => __('trade.errors.not_allowed')], 403);
        }

        return $org;
    }

    /** GET /api/organizations/{id}/capabilities — the business's category and activities (members). */
    public function capabilities(Request $request, int $id): JsonResponse
    {
        $org = $this->memberOr403($request, $id);
        if ($org instanceof JsonResponse) {
            return $org;
        }

        return response()->json(['success' => true, 'data' => CapabilityService::summary($org) + [
            'can_edit' => $org->isOwner($request->user()),
        ]]);
    }

    /** PUT /api/organizations/{id}/capabilities {capabilities: [...]} — owner only. */
    public function updateCapabilities(Request $request, int $id, CapabilityService $service): JsonResponse
    {
        $org = $this->memberOr403($request, $id);
        if ($org instanceof JsonResponse) {
            return $org;
        }
        if (!$org->isOwner($request->user())) {
            return response()->json(['success' => false, 'code' => 'OWNER_ONLY', 'message' => __('trade.errors.capabilities_owner_only')], 403);
        }
        $v = Validator::make($request->all(), [
            'capabilities' => 'present|array',
            'capabilities.*' => ['string', Rule::in(Capability::values())],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $v->errors()], 422);
        }
        try {
            $service->setDeclared($org, $request->input('capabilities', []), $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => CapabilityService::summary($org->fresh('businessCategory')) + ['can_edit' => true]]);
    }

    /** PUT /api/organizations/{id}/category {business_category} — owner only; role locked once verified. */
    public function updateCategory(Request $request, int $id, CapabilityService $service): JsonResponse
    {
        $org = $this->memberOr403($request, $id);
        if ($org instanceof JsonResponse) {
            return $org;
        }
        if (!$org->isOwner($request->user())) {
            return response()->json(['success' => false, 'code' => 'OWNER_ONLY', 'message' => __('trade.errors.capabilities_owner_only')], 403);
        }
        $v = Validator::make($request->all(), [
            'business_category' => ['required', 'string', Rule::exists('business_categories', 'code')->where('is_active', true)],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $v->errors()], 422);
        }
        try {
            $org = $service->setCategory($org, BusinessCategory::where('code', $request->input('business_category'))->firstOrFail(), $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => CapabilityService::summary($org->load('businessCategory')) + ['can_edit' => true]]);
    }
}
