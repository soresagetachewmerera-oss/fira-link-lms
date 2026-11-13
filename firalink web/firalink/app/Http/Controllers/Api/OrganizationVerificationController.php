<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BannedIdentity;
use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Models\VerificationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganizationVerificationController extends Controller
{
    /**
     * GET /api/marketplace/verification/status
     * Fetch complete verification state, roles, and document checklist for the user's organization.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization() ?: $user->primaryOrganization();

        if (!$org) {
            return response()->json([
                'success' => false,
                'code' => 'ORGANIZATION_REQUIRED',
                'message' => 'No active organization found. Please register or join an organization first.',
            ], 404);
        }

        $verifications = $org->verifications()
            ->with(['documents' => fn($q) => $q->where('is_valid', true), 'brokerPrincipal'])
            ->get()
            ->keyBy('target_role');

        $checklist = [
            OrganizationVerification::ROLE_EXPORTER => [
                'label' => 'Licensed Coffee Exporter',
                'description' => 'ECTA-licensed export entity capable of issuing FOB/CIF contracts and managing export logistics.',
                'required_documents' => [
                    ['type' => VerificationDocument::TYPE_COMMERCIAL_REG, 'label' => 'MoTI Commercial Registration Certificate'],
                    ['type' => VerificationDocument::TYPE_SECTOR_LICENSE, 'label' => 'ECTA Coffee Export Competence Certificate (Current Season)'],
                    ['type' => VerificationDocument::TYPE_TIN_CERT, 'label' => 'Tax Identification Number (TIN) & VAT Certificate'],
                    ['type' => VerificationDocument::TYPE_REP_ID, 'label' => 'Authorized Signatory Passport / National ID'],
                ],
                'status' => $verifications->get(OrganizationVerification::ROLE_EXPORTER)?->status ?? 'unsubmitted',
                'data' => $verifications->get(OrganizationVerification::ROLE_EXPORTER),
            ],
            OrganizationVerification::ROLE_SUPPLIER => [
                'label' => 'Washing Station / Commercial Farm',
                'description' => 'Coffee producer or wet/dry mill operator with certified origin processing infrastructure.',
                'required_documents' => [
                    ['type' => VerificationDocument::TYPE_COMMERCIAL_REG, 'label' => 'Business License / Cooperative Certificate'],
                    ['type' => VerificationDocument::TYPE_SECTOR_LICENSE, 'label' => 'Washing Station / Farm Operating License'],
                    ['type' => VerificationDocument::TYPE_REP_ID, 'label' => 'Manager / Representative Kebele or National ID'],
                ],
                'status' => $verifications->get(OrganizationVerification::ROLE_SUPPLIER)?->status ?? 'unsubmitted',
                'data' => $verifications->get(OrganizationVerification::ROLE_SUPPLIER),
            ],
            OrganizationVerification::ROLE_BUYER => [
                'label' => 'International Coffee Buyer / Roaster',
                'description' => 'Commercial green coffee importer, roaster, or distributor registered outside Ethiopia.',
                'required_documents' => [
                    ['type' => VerificationDocument::TYPE_COMMERCIAL_REG, 'label' => 'Certificate of Incorporation / Good Standing'],
                    ['type' => VerificationDocument::TYPE_TIN_CERT, 'label' => 'Corporate Tax ID / VAT / EIN Registration'],
                    ['type' => VerificationDocument::TYPE_ADDRESS_PROOF, 'label' => 'Proof of Operating Business Address (< 90 Days)'],
                ],
                'status' => $verifications->get(OrganizationVerification::ROLE_BUYER)?->status ?? 'unsubmitted',
                'data' => $verifications->get(OrganizationVerification::ROLE_BUYER),
            ],
            OrganizationVerification::ROLE_BROKER => [
                'label' => 'Certified Coffee Broker / Agent',
                'description' => 'Commercial intermediary representing verified exporters or buyers under formal agency mandate.',
                'required_documents' => [
                    ['type' => VerificationDocument::TYPE_COMMERCIAL_REG, 'label' => 'Agency / Brokerage Business License'],
                    ['type' => VerificationDocument::TYPE_BROKER_MANDATE, 'label' => 'Notarized Letter of Representation (LoR) from Verified Exporter'],
                    ['type' => VerificationDocument::TYPE_REP_ID, 'label' => 'Agent Passport / National ID'],
                ],
                'status' => $verifications->get(OrganizationVerification::ROLE_BROKER)?->status ?? 'unsubmitted',
                'data' => $verifications->get(OrganizationVerification::ROLE_BROKER),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'is_verified' => $org->is_verified,
                    'verified_roles' => $org->verified_roles ?? [],
                    'country' => $org->country,
                ],
                'checklist' => $checklist,
            ],
        ]);
    }

    /**
     * POST /api/marketplace/verification/submit
     * Submit role-specific business details for compliance audit.
     */
    public function submitDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization() ?: $user->primaryOrganization();

        if (!$org || !$org->canManageBilling($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the organization owner or manager can submit verification dossiers.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'target_role' => 'required|in:exporter,supplier,buyer,broker',
            'tin_number' => 'nullable|string|max:100',
            'license_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:150',
            'license_issued_at' => 'nullable|date',
            'license_expires_at' => 'nullable|date|after:license_issued_at',
            'verified_attributes' => 'nullable|array',
            'broker_principal_org_id' => 'nullable|exists:organizations,id',
            'broker_mandate_expires_at' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 1. Sanctions Screening
        $tin = $request->input('tin_number');
        $license = $request->input('license_number');

        $isBanned = BannedIdentity::where(function ($q) use ($tin, $license, $org) {
            if ($tin) $q->orWhere('tin_number', $tin);
            if ($license) $q->orWhere('license_number', $license);
            $q->orWhere('company_name', 'LIKE', '%' . $org->name . '%');
        })->exists();

        if ($isBanned) {
            return response()->json([
                'success' => false,
                'code' => 'BANNED_IDENTITY',
                'message' => 'This business identity or tax registration has been barred from the platform.',
            ], 403);
        }

        $verification = OrganizationVerification::updateOrCreate(
            [
                'organization_id' => $org->id,
                'target_role' => $request->input('target_role'),
            ],
            [
                'status' => OrganizationVerification::STATUS_PENDING_REVIEW,
                'tin_number' => $tin,
                'license_number' => $license,
                'issuing_authority' => $request->input('issuing_authority', 'ECTA'),
                'license_issued_at' => $request->input('license_issued_at'),
                'license_expires_at' => $request->input('license_expires_at'),
                'verified_attributes' => $request->input('verified_attributes', []),
                'broker_principal_org_id' => $request->input('broker_principal_org_id'),
                'broker_mandate_expires_at' => $request->input('broker_mandate_expires_at'),
            ]
        );

        // Security Audit Trail
        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'verification_dossier_submitted',
            'auditable_type' => OrganizationVerification::class,
            'auditable_id' => $verification->id,
            'after_state' => $verification->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Verification dossier for {$verification->target_role} submitted successfully. Compliance audit in progress.",
            'data' => $verification->load('documents'),
        ]);
    }

    /**
     * POST /api/marketplace/verification/upload-document
     * Securely upload verification document to private non-public disk vault.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization() ?: $user->primaryOrganization();

        if (!$org || !$org->canManageBilling($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the organization owner or manager can upload legal verification documents.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'target_role' => 'required|in:exporter,supplier,buyer,broker',
            'document_type' => 'required|in:commercial_reg,sector_license,tin_cert,rep_id,address_proof,broker_mandate',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = OrganizationVerification::firstOrCreate(
            [
                'organization_id' => $org->id,
                'target_role' => $request->input('target_role'),
            ],
            [
                'status' => OrganizationVerification::STATUS_DRAFT,
            ]
        );

        $file = $request->file('document');
        $ext = $file->getClientOriginalExtension();
        $safeHash = hash_file('sha256', $file->getRealPath());
        $filename = Str::uuid() . '.' . $ext;
        $folder = "organizations/{$org->id}/verifications";

        // Store file strictly on private 'verification' disk
        $path = $file->storeAs($folder, $filename, 'verification');

        // Mark any previous document of this type as invalid/superseded
        $verification->documents()
            ->where('document_type', $request->input('document_type'))
            ->update(['is_valid' => false]);

        $doc = VerificationDocument::create([
            'organization_verification_id' => $verification->id,
            'document_type' => $request->input('document_type'),
            'file_path' => $path,
            'file_disk' => 'verification',
            'original_filename' => $file->getClientOriginalName(),
            'file_size_kb' => (int) round($file->getSize() / 1024),
            'mime_type' => $file->getMimeType(),
            'verification_hash' => $safeHash,
            'is_valid' => true,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'verification_document_uploaded',
            'auditable_type' => VerificationDocument::class,
            'auditable_id' => $doc->id,
            'after_state' => [
                'document_type' => $doc->document_type,
                'filename' => $doc->original_filename,
                'size_kb' => $doc->file_size_kb,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document securely vaulted and attached to verification dossier.',
            'data' => $doc,
        ], 201);
    }

    /**
     * DELETE /api/marketplace/verification/documents/{id}
     * Remove an uploaded document from draft dossier.
     */
    public function deleteDocument(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization() ?: $user->primaryOrganization();

        $doc = VerificationDocument::with('verification')->findOrFail($id);

        if (!$user->isAdmin() && (!$org || $doc->verification->organization_id !== $org->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!$user->isAdmin() && $doc->verification->status === OrganizationVerification::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'Approved documents cannot be removed. Contact compliance for amendments.',
            ], 400);
        }

        $doc->deletePhysicalFile();
        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document removed successfully.',
        ]);
    }
}

