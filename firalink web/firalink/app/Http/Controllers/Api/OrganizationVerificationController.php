<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Verification\DocumentReviewService;
use App\Services\Verification\PublicSellerProfile;
use App\Services\Verification\TierService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Verification dossier (slice 3) for the active business: tiers, per-document states and
 * uploads. Status and tier are read-only here; they change only through review services.
 */
class OrganizationVerificationController extends Controller
{
    private function organization(Request $request): ?Organization
    {
        $user = $request->user();

        return $user->currentOrganization() ?: $user->primaryOrganization();
    }

    /** Owner or manager of the business (no admin bypass: admins review, they do not upload). */
    private function canManage(User $user, Organization $org): bool
    {
        return OrganizationMembership::where('organization_id', $org->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('role', [OrganizationMembership::ROLE_OWNER, OrganizationMembership::ROLE_MANAGER])
            ->exists();
    }

    private function noBusiness(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ORGANIZATION_REQUIRED',
            'message' => 'Create or switch to a business workspace first.',
        ], 404);
    }

    private function documentJson(string $type, int $tier, $docs): array
    {
        $config = TierService::documentTypeConfig($type) ?? [];
        $latest = $docs->firstWhere('document_type', $type);
        $counting = $docs->first(fn ($d) => $d->document_type === $type
            && $d->status === VerificationDocument::STATUS_APPROVED && !$d->isExpiredNow());

        $state = $latest ? $latest->status : 'not_uploaded';
        if ($state === VerificationDocument::STATUS_APPROVED && $latest->isExpiredNow()) {
            $state = VerificationDocument::STATUS_EXPIRED;
        }

        return [
            'type' => $type,
            'tier' => $tier,
            'state' => $state,
            'document_id' => $latest?->id,
            'reason' => in_array($state, ['needs_changes', 'rejected'], true) ? $latest->review_reason : null,
            'reason_code' => in_array($state, ['needs_changes', 'rejected'], true) ? $latest->review_reason_code : null,
            'submitted_at' => $latest?->submitted_at?->toIso8601String(),
            'reviewed_at' => $latest?->reviewed_at?->toIso8601String(),
            'expires_at' => $latest?->expires_at?->toDateString(),
            'pages' => $latest ? $latest->pages()->count() : 0,
            // An earlier approved copy keeps counting while a replacement is in review.
            'approved_until' => $counting && $counting->id !== $latest?->id ? $counting->expires_at?->toDateString() ?? 'no_expiry' : null,
            'requires_expiry' => (bool) ($config['expires'] ?? false),
            'requires_number' => (bool) ($config['number'] ?? false),
            'fields' => $config['fields'] ?? [],
            'camera_only' => (bool) ($config['camera_only'] ?? false),
            'min_pages' => (int) ($config['min_pages'] ?? 1),
            'max_pages' => (int) config('verification.upload.max_pages', 10),
        ];
    }

    /**
     * GET /api/marketplace/verification/status
     */
    public function status(Request $request): JsonResponse
    {
        $org = $this->organization($request);
        if (!$org) {
            return $this->noBusiness();
        }

        $docs = $org->verificationDocuments()->current()->orderByDesc('id')->get();
        $tiers = [];
        $counts = ['approved' => 0, 'in_review' => 0, 'needs_changes' => 0, 'not_uploaded' => 0];

        foreach ([1, 2] as $t) {
            $required = TierService::requiredDocuments($org, $t);
            if ($t === 2 && $required === []) {
                continue;
            }
            $items = array_map(fn ($type) => $this->documentJson($type, $t, $docs), $required);
            foreach ($items as $item) {
                $bucket = match ($item['state']) {
                    'approved' => 'approved',
                    'in_review' => 'in_review',
                    'needs_changes', 'rejected', 'expired' => 'needs_changes',
                    default => 'not_uploaded',
                };
                $counts[$bucket]++;
            }
            $tiers[] = [
                'tier' => $t,
                'being_confirmed' => (bool) config("verification.tiers.{$t}.being_confirmed", false),
                'documents' => $items,
            ];
        }

        $maxTier = TierService::maxTier($org);
        if ($maxTier === 3) {
            $grant = $org->tier3Grants()->active()->latest('granted_at')->first();
            $tiers[] = ['tier' => 3, 'requires_tier' => 2, 'active_until' => $grant?->expires_at?->toDateString(), 'documents' => []];
        }

        $hasPublicPage = $org->fira_id && $org->tier1_first_granted_at;

        return response()->json([
            'success' => true,
            'data' => [
                'business' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'business_role' => $org->roleKey(),
                    'entity_type' => $org->entity_type,
                    'status' => $org->workspaceStatus(),
                ],
                'tier' => $org->hasActiveTier(1) ? (int) $org->verification_tier : 0,
                'max_tier' => $maxTier,
                'valid_until' => $org->verification_valid_until?->toDateString(),
                'reviewed_at' => $org->verification_reviewed_at?->toDateString(),
                'public_status' => $hasPublicPage ? PublicSellerProfile::status($org) : null,
                'fira_id' => $hasPublicPage ? $org->fira_id : null,
                'public_url' => $hasPublicPage ? route('verify.show', $org->fira_id) : null,
                'qr_svg_url' => $hasPublicPage ? route('verify.qr', $org->fira_id) : null,
                'expected_review_business_days' => (int) config('verification.expected_review_business_days'),
                'counts' => $counts,
                'tiers' => $tiers,
                'can_manage' => $this->canManage($request->user(), $org),
            ],
        ]);
    }

    /**
     * POST /api/marketplace/verification/documents (multipart)
     * document_type, pages[] (1–10 images or PDFs), document_number, issued_at, expires_at, details[...]
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $this->organization($request);
        if (!$org) {
            return $this->noBusiness();
        }
        if (!$this->canManage($user, $org)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => 'Only the owner or a manager can upload verification documents.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|max:60',
            'pages' => 'required|array|min:1|max:' . (int) config('verification.upload.max_pages', 10),
            'pages.*' => 'required|file|max:10240',
            'document_number' => 'nullable|string|max:80',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'details' => 'nullable|array',
            'details.*' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $doc = app(DocumentReviewService::class)->submit(
                $org,
                $user,
                $request->input('document_type'),
                $request->file('pages', []),
                $request->input('document_number'),
                $request->input('issued_at'),
                $request->input('expires_at'),
                (array) $request->input('details', [])
            );
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'document_id' => $doc->id,
                'type' => $doc->document_type,
                'state' => $doc->status,
                'pages' => $doc->pages()->count(),
                'expected_review_business_days' => (int) config('verification.expected_review_business_days'),
            ],
        ], 201);
    }

    /**
     * DELETE /api/marketplace/verification/documents/{id} — withdraw a document still in review.
     */
    public function deleteDocument(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = $this->organization($request);
        $doc = VerificationDocument::findOrFail($id);

        if (!$org || (int) $doc->organization_id !== (int) $org->id || !$this->canManage($user, $org)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }
        if ($doc->status !== VerificationDocument::STATUS_IN_REVIEW) {
            return response()->json(['success' => false, 'code' => 'NOT_IN_REVIEW', 'message' => 'Only documents still in review can be withdrawn.'], 409);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'verification_document_withdrawn',
            'auditable_type' => VerificationDocument::class,
            'auditable_id' => $doc->id,
            'before_state' => ['document_type' => $doc->document_type, 'status' => $doc->status],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        $doc->deletePhysicalFile();
        $doc->delete();

        return response()->json(['success' => true]);
    }
}
