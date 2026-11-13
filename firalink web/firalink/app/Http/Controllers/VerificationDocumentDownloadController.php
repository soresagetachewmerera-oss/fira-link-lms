<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\VerificationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationDocumentDownloadController extends Controller
{
    /**
     * Download or view a private verification document securely.
     */
    public function download(Request $request, int $id): StreamedResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Please sign in to access compliance records.');
        }

        $document = VerificationDocument::with('verification.organization')->findOrFail($id);
        $org = $document->verification->organization;

        // Authorization check: Only Admin or Organization Member can view verification documents
        $isAuthorized = $user->isAdmin() || $org->hasMember($user);

        if (!$isAuthorized) {
            AuditLog::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'action' => 'unauthorized_document_access_attempt',
                'auditable_type' => VerificationDocument::class,
                'auditable_id' => $document->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(403, 'Access denied: You do not have permission to view this legal compliance document.');
        }

        if (!Storage::disk($document->file_disk)->exists($document->file_path)) {
            abort(404, 'The requested document file could not be located in the secure vault.');
        }

        // Record compliance audit log
        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'verification_document_viewed',
            'auditable_type' => VerificationDocument::class,
            'auditable_id' => $document->id,
            'after_state' => [
                'document_type' => $document->document_type,
                'original_filename' => $document->original_filename,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Storage::disk($document->file_disk)->response(
            $document->file_path,
            $document->original_filename,
            [
                'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}

