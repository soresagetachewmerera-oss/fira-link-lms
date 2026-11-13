<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BannedIdentity;
use App\Models\CoffeeListing;
use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Models\TrustReport;
use App\Models\User;
use App\Services\PrivateDocumentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicVerificationController extends Controller
{
    /**
     * GET /api/verify-seller/{query}
     * Public verification registry lookup by slug, company name, or TIN number.
     */
    public function verifySeller(Request $request, string $query): JsonResponse
    {
        $org = Organization::with(['verifications' => fn($q) => $q->where('status', 'approved')])
            ->where('slug', $query)
            ->orWhere('id', is_numeric($query) ? (int) $query : 0)
            ->first();

        // Also check by TIN inside verifications
        if (!$org) {
            $verification = OrganizationVerification::where('tin_number', $query)
                ->where('status', 'approved')
                ->first();
            if ($verification) {
                $org = $verification->organization;
            }
        }

        if (!$org) {
            return response()->json([
                'success' => false,
                'message' => 'No verified organization found matching this identifier.',
            ], 404);
        }

        // Check if banned
        $isBanned = BannedIdentity::where('company_name', $org->name)
            ->orWhere(function ($q) use ($org) {
                $tins = $org->verifications->pluck('tin_number')->filter();
                if ($tins->isNotEmpty()) {
                    $q->whereIn('tin_number', $tins);
                }
            })
            ->exists();

        $activeLotsCount = CoffeeListing::where('organization_id', $org->id)
            ->where('status', 'active')
            ->count();

        $verificationsData = $org->verifications->map(function ($v) {
            return [
                'role' => $v->target_role,
                'status' => $v->status,
                'issuing_authority' => $v->issuing_authority,
                'license_issued_at' => $v->license_issued_at,
                'license_expires_at' => $v->license_expires_at,
                'is_expired' => $v->isExpired(),
                'verified_attributes' => $v->verified_attributes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'business_type' => $org->business_type,
                    'country' => $org->country,
                    'is_verified' => (bool) $org->is_verified,
                    'verified_roles' => $org->verified_roles ?? [],
                    'is_banned' => $isBanned,
                    'active_listings_count' => $activeLotsCount,
                ],
                'credentials' => $verificationsData,
                'trust_summary' => [
                    'trust_score' => $isBanned ? 0 : ($org->is_verified ? 98 : 45),
                    'escrow_supported' => true,
                    'dispute_rate' => 0.0,
                    'retained_sample_protocol' => true,
                ],
            ],
        ]);
    }

    /**
     * POST /api/reports
     * Submit a trust & safety report against a listing, seller, or behavior.
     */
    public function storeReport(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'details' => 'required|string',
            'reported_user_id' => 'nullable|exists:users,id',
            'reported_organization_id' => 'nullable|exists:organizations,id',
            'reported_listing_id' => 'nullable|exists:coffee_listings,id',
            'evidence' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $documents = app(PrivateDocumentStore::class);
            $reason = $documents->rejectionReason($request->file('evidence'));
            if ($reason) {
                return response()->json($documents->errorResponse('evidence', $reason), 422);
            }
            $evidencePath = $documents->store($request->file('evidence'), 'trust_reports/evidence');
        }

        $report = TrustReport::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $request->input('reported_user_id'),
            'reported_organization_id' => $request->input('reported_organization_id'),
            'reported_listing_id' => $request->input('reported_listing_id'),
            'reason' => $request->input('reason'),
            'details' => $request->input('details'),
            'evidence_path' => $evidencePath,
            'status' => TrustReport::STATUS_PENDING,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $request->input('reported_organization_id'),
            'action' => 'trust_report_submitted',
            'auditable_type' => TrustReport::class,
            'auditable_id' => $report->id,
            'after_state' => $report->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your report. Our Trust & Safety team has been notified.',
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
                'created_at' => $report->created_at,
            ],
        ], 201);
    }
}

