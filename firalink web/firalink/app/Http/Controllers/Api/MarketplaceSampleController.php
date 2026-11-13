<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\MarketplaceSample;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MarketplaceSampleController extends Controller
{
    /**
     * GET /api/marketplace/samples
     * List samples requested by buyer or received by exporter.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization() ?: $user->primaryOrganization();
        $orgId = $org?->id;

        $samples = MarketplaceSample::with(['listing', 'organization', 'buyer'])
            ->where(function ($q) use ($user, $orgId) {
                $q->where('buyer_id', $user->id);
                if ($orgId) {
                    $q->orWhere('organization_id', $orgId);
                }
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $samples,
        ]);
    }

    /**
     * POST /api/marketplace/samples
     * Buyer requests green coffee pre-shipment/type sample.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|exists:coffee_listings,id',
            'quantity_grams' => 'nullable|integer|min:100|max:2000',
            'shipping_address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $listing = CoffeeListing::findOrFail($request->input('listing_id'));

        $orgId = $listing->organization_id;
        if (!$orgId && $listing->seller) {
            $orgId = $listing->seller->primaryOrganization()?->id;
        }

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'message' => 'The exporter of this listing does not have an active organization configured.',
            ], 422);
        }

        $sampleRef = 'SMP-' . strtoupper(Str::random(7));
        $feeAmount = (float) ($listing->sample_cost_usd ?? 0.0);

        $sample = MarketplaceSample::create([
            'sample_reference' => $sampleRef,
            'listing_id' => $listing->id,
            'organization_id' => $orgId,
            'buyer_id' => $user->id,
            'status' => $feeAmount > 0 ? MarketplaceSample::STATUS_REQUESTED : MarketplaceSample::STATUS_FEE_PAID,
            'quantity_grams' => $request->input('quantity_grams', 250),
            'shipping_address' => $request->input('shipping_address'),
            'fee_amount_usd' => $feeAmount,
            'payment_status' => $feeAmount > 0 ? 'unpaid' : 'waived',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $orgId,
            'action' => 'sample_requested',
            'auditable_type' => MarketplaceSample::class,
            'auditable_id' => $sample->id,
            'after_state' => $sample->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sample request '{$sample->sample_reference}' submitted successfully.",
            'data' => $sample->load(['listing', 'organization', 'buyer']),
        ], 201);
    }

    /**
     * GET /api/marketplace/samples/{id}
     * Show sample details, tracking, and cupping outcome.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $sample = MarketplaceSample::with(['listing', 'organization', 'buyer'])->findOrFail($id);

        $isBuyer = $sample->buyer_id === $user->id;
        $isOrgMember = OrganizationMembership::where('organization_id', $sample->organization_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$isBuyer && !$isOrgMember && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to sample details.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $sample,
        ]);
    }

    /**
     * POST /api/marketplace/samples/{id}/dispatch
     * Exporter dispatches sample with courier tracking and sealed hash.
     */
    public function dispatchSample(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $sample = MarketplaceSample::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'courier_name' => 'required|string|max:100', // DHL, FedEx, EMS, Aramex
            'waybill_number' => 'required|string|max:100',
            'retained_sample_seal_no' => 'required|string|max:100',
            'dispatch_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sealNo = $request->input('retained_sample_seal_no');
        $sealHash = hash('sha256', $sealNo . '-' . $sample->sample_reference . '-' . $sample->listing_id);

        $sample->update([
            'courier_name' => $request->input('courier_name'),
            'waybill_number' => $request->input('waybill_number'),
            'retained_sample_seal_no' => $sealNo,
            'retained_sample_seal_hash' => $sealHash,
            'dispatch_date' => $request->input('dispatch_date', now()),
            'status' => MarketplaceSample::STATUS_DISPATCHED,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $sample->organization_id,
            'action' => 'sample_dispatched',
            'auditable_type' => MarketplaceSample::class,
            'auditable_id' => $sample->id,
            'after_state' => [
                'waybill' => $sample->waybill_number,
                'seal_no' => $sample->retained_sample_seal_no,
                'seal_hash' => $sample->retained_sample_seal_hash,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sample dispatched with cryptographic seal recorded.',
            'data' => $sample,
        ]);
    }

    /**
     * POST /api/marketplace/samples/{id}/delivered
     * Mark sample delivered.
     */
    public function markDelivered(Request $request, int $id): JsonResponse
    {
        $sample = MarketplaceSample::findOrFail($id);
        $sample->update([
            'status' => MarketplaceSample::STATUS_DELIVERED,
            'delivery_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sample marked as delivered.',
            'data' => $sample,
        ]);
    }

    /**
     * POST /api/marketplace/samples/{id}/evaluate
     * Buyer cups and approves or rejects sample.
     */
    public function evaluateSample(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $sample = MarketplaceSample::findOrFail($id);

        if ($sample->buyer_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the buyer who requested the sample can submit evaluation.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:approved,rejected',
            'cupping_score' => 'required|numeric|min:60|max:100',
            'cupping_notes' => 'nullable|string',
            'lab_report' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $request->input('decision') === 'approved' 
            ? MarketplaceSample::STATUS_CUPPED_APPROVED 
            : MarketplaceSample::STATUS_CUPPED_REJECTED;

        $reportPath = null;
        if ($request->hasFile('lab_report')) {
            $reportPath = $request->file('lab_report')->store("samples/{$sample->id}/lab", 'public');
        }

        $sample->update([
            'status' => $status,
            'cupping_score' => $request->input('cupping_score'),
            'cupping_notes' => $request->input('cupping_notes'),
            'lab_report_path' => $reportPath ?? $sample->lab_report_path,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $sample->organization_id,
            'action' => 'sample_evaluated',
            'auditable_type' => MarketplaceSample::class,
            'auditable_id' => $sample->id,
            'after_state' => [
                'status' => $status,
                'cupping_score' => $sample->cupping_score,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sample cupping evaluation recorded as {$status}.",
            'data' => $sample,
        ]);
    }
}

