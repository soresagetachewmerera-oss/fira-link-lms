<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TrustReport;
use App\Services\PrivateDocumentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicVerificationController extends Controller
{
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

