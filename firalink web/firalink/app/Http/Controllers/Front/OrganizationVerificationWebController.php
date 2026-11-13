<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BannedIdentity;
use App\Models\OrganizationVerification;
use App\Models\VerificationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganizationVerificationWebController extends Controller
{
    /**
     * Show Organization Verification & Compliance Center.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('warning', 'Please sign in to access verification.');
        }

        $org = $user->primaryOrganization();
        if (!$org) {
            return redirect()->route('marketplace.dashboard')->with('warning', 'Please create or join an organization first.');
        }

        $verifications = $org->verifications()
            ->with(['documents' => fn($q) => $q->where('is_valid', true)])
            ->get()
            ->keyBy('target_role');

        return view('marketplace.verification.center', compact('org', 'verifications'));
    }

    /**
     * Submit role verification details from web.
     */
    public function submitDetails(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $org = $user?->primaryOrganization();

        if (!$org || !$org->canManageBilling($user)) {
            return redirect()->back()->with('error', 'Only the organization owner or manager can submit verification dossiers.');
        }

        $validator = Validator::make($request->all(), [
            'target_role' => 'required|in:exporter,supplier,buyer,broker',
            'tin_number' => 'nullable|string|max:100',
            'license_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:150',
            'license_issued_at' => 'nullable|date',
            'license_expires_at' => 'nullable|date|after:license_issued_at',
            'verified_attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tin = $request->input('tin_number');
        $license = $request->input('license_number');

        $isBanned = BannedIdentity::where(function ($q) use ($tin, $license, $org) {
            if ($tin) $q->orWhere('tin_number', $tin);
            if ($license) $q->orWhere('license_number', $license);
            $q->orWhere('company_name', 'LIKE', '%' . $org->name . '%');
        })->exists();

        if ($isBanned) {
            return redirect()->back()->with('error', 'Compliance Block: This identity or license has been barred from the platform.');
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
            ]
        );

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

        return redirect()->back()->with('success', "Your {$verification->target_role} verification dossier was submitted for compliance review.");
    }

    /**
     * Upload verification document from web.
     */
    public function uploadDocument(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $org = $user?->primaryOrganization();

        if (!$org || !$org->canManageBilling($user)) {
            return redirect()->back()->with('error', 'Only the organization owner or manager can upload compliance documents.');
        }

        $request->validate([
            'target_role' => 'required|in:exporter,supplier,buyer,broker',
            'document_type' => 'required|in:commercial_reg,sector_license,tin_cert,rep_id,address_proof,broker_mandate',
            'document' => 'required|file|max:10240',
        ]);

        $documents = app(\App\Services\PrivateDocumentStore::class);
        $reason = $documents->rejectionReason($request->file('document'));
        if ($reason) {
            return redirect()->back()->withErrors(['document' => $documents->message($reason)])->withInput();
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
        $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$file->getMimeType()];
        $safeHash = hash_file('sha256', $file->getRealPath());
        $filename = Str::uuid() . '.' . $ext;
        $folder = "organizations/{$org->id}/verifications";

        $path = $file->storeAs($folder, $filename, 'verification');

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
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Document securely vaulted and attached to your dossier.');
    }

    /**
     * Delete document from web.
     */
    public function deleteDocument(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $org = $user?->primaryOrganization();

        $doc = VerificationDocument::with('verification')->findOrFail($id);

        if (!$user->isAdmin() && (!$org || $doc->verification->organization_id !== $org->id)) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $doc->deletePhysicalFile();
        $doc->delete();

        return redirect()->back()->with('success', 'Document removed successfully.');
    }
}

