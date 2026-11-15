<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrustReport;
use App\Services\Trade\TrustReportService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PublicVerificationController extends Controller
{
    /**
     * POST /api/reports
     * Report a business, lot, chat message, deal room or sample (slice 7). The reported business
     * comes from what was reported, never from the client alone, and is never told who reported it.
     * Nothing is paused automatically: an admin decides in the review queue.
     */
    public function storeReport(Request $request, TrustReportService $reports): JsonResponse
    {
        $user = $request->user();
        if ($user->cannot('create', TrustReport::class)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('trade.errors.not_allowed')], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason_code' => ['nullable', 'required_without:reason', Rule::in(TrustReport::REASONS)],
            'reason' => 'nullable|string|max:255',
            'details' => 'nullable|string|max:5000|required_if:reason_code,other',
            'reported_user_id' => 'nullable|integer|exists:users,id',
            'reported_organization_id' => 'nullable|integer|exists:organizations,id',
            'reported_listing_id' => 'nullable|integer|exists:coffee_listings,id',
            'reported_message_id' => 'nullable|integer|exists:trade_messages,id',
            'reported_deal_room_id' => 'nullable|integer|exists:deal_rooms,id',
            'reported_sample_id' => 'nullable|integer|exists:marketplace_samples,id',
            'reported_fira_id' => 'nullable|string|max:40',
            'block' => 'nullable|boolean',
            'evidence' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        // Older app versions send a free-text reason only.
        $data['reason_code'] ??= 'other';
        if ($data['reason_code'] === 'other' && empty($data['details'])) {
            $data['details'] = $data['reason'] ?? null;
        }
        $data['block'] = $request->boolean('block');

        try {
            $report = $reports->submit($user, $user->currentOrganization(), $data, $request->file('evidence'));
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json([
            'success' => true,
            'message' => __('trade.report_sent'),
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
                'blocked' => $report->block_requested && \App\Models\BusinessBlock::where('organization_id', $report->reporter_organization_id)
                    ->where('blocked_organization_id', $report->reported_organization_id)->exists(),
                'created_at' => $report->created_at,
            ],
        ], 201);
    }
}
