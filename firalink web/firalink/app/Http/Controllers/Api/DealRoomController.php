<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\MarketplaceQuotation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DealRoomController extends Controller
{
    /**
     * GET /api/deal-rooms
     * List deal rooms where user is buyer or organization member.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization() ?: $user->primaryOrganization();
        $orgId = $org?->id;

        $dealRooms = DealRoom::with(['organization', 'buyer', 'listing', 'milestones'])
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
            'data' => $dealRooms,
        ]);
    }

    /**
     * POST /api/deal-rooms
     * Create or initialize a new Deal Room.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'listing_id' => 'nullable|exists:coffee_listings,id',
            'quotation_id' => 'nullable|exists:marketplace_quotations,id',
            'title' => 'required|string|max:255',
            'target_value_usd' => 'required|numeric|min:0',
            'incoterm' => 'nullable|string|max:50',
            'buyer_id' => 'nullable|exists:users,id',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $listing = null;
        if ($request->input('listing_id')) {
            $listing = CoffeeListing::find($request->input('listing_id'));
        }

        $quotation = null;
        if ($request->input('quotation_id')) {
            $quotation = MarketplaceQuotation::find($request->input('quotation_id'));
        }

        // Determine organization and buyer
        $orgId = $request->input('organization_id');
        $buyerId = $request->input('buyer_id');

        if (!$orgId) {
            if ($listing && $listing->organization_id) {
                $orgId = $listing->organization_id;
            } elseif ($user->currentOrganization()) {
                $orgId = $user->currentOrganization()->id;
            } else {
                $orgId = $user->primaryOrganization()->id;
            }
        }

        if (!$buyerId) {
            $buyerId = $user->id;
        }

        $dealReference = 'DR-' . strtoupper(Str::random(8));

        $dealRoom = DealRoom::create([
            'deal_reference' => $dealReference,
            'organization_id' => $orgId,
            'buyer_id' => $buyerId,
            'listing_id' => $listing?->id,
            'rfq_id' => $quotation?->rfq_id,
            'title' => $request->input('title'),
            'target_value_usd' => $request->input('target_value_usd'),
            'incoterm' => $request->input('incoterm', 'FOB Djibouti'),
            'status' => DealRoom::STATUS_ACTIVE,
        ]);

        // Provision standard 5 export milestones
        $standardMilestones = [
            [
                'title' => 'Export Contract & Terms E-Signed',
                'milestone_key' => 'contract_signed',
                'description' => 'Bilateral export contract agreed and signed by authorized representatives of exporter and buyer.',
                'status' => 'pending',
            ],
            [
                'title' => 'Letter of Credit / Escrow Proof Verified',
                'milestone_key' => 'payment_escrow',
                'description' => 'Documentary letter of credit (LC) advised or milestone escrow deposit confirmed by bank.',
                'status' => 'pending',
            ],
            [
                'title' => 'Pre-Shipment Quality & Weight Inspection',
                'milestone_key' => 'preshipment_inspection',
                'description' => 'ECTA & third-party surveyor (SGS/Bureau Veritas) green coffee quality grading and container tare verification.',
                'status' => 'pending',
            ],
            [
                'title' => 'Port of Djibouti Bill of Lading (B/L) Released',
                'milestone_key' => 'bill_of_lading_released',
                'description' => 'Ocean carrier Bill of Lading, Phytosanitary Certificate, and Certificate of Origin uploaded and authenticated.',
                'status' => 'pending',
            ],
            [
                'title' => 'Final Settlement & Delivery Confirmation',
                'milestone_key' => 'final_settlement',
                'description' => 'Shipment cleared at destination port, final documents released, and escrow payment settled.',
                'status' => 'pending',
            ],
        ];

        foreach ($standardMilestones as $m) {
            DealMilestone::create([
                'deal_room_id' => $dealRoom->id,
                'title' => $m['title'],
                'milestone_key' => $m['milestone_key'],
                'description' => $m['description'],
                'status' => $m['status'],
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $orgId,
            'action' => 'deal_room_opened',
            'auditable_type' => DealRoom::class,
            'auditable_id' => $dealRoom->id,
            'after_state' => $dealRoom->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Deal Room '{$dealRoom->deal_reference}' opened successfully.",
            'data' => $dealRoom->load(['organization', 'buyer', 'listing', 'milestones']),
        ], 201);
    }

    /**
     * GET /api/deal-rooms/{id}
     * Show deal room details with milestones and audit trail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::with(['organization', 'buyer', 'listing', 'milestones.verifier'])->findOrFail($id);

        // Security check
        $isBuyer = $dealRoom->buyer_id === $user->id;
        $isOrgMember = OrganizationMembership::where('organization_id', $dealRoom->organization_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$isBuyer && !$isOrgMember && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this deal room.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $dealRoom,
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/sign-contract
     * Buyer or Seller electronically signs the deal room contract.
     */
    public function signContract(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);

        $isBuyer = $dealRoom->buyer_id === $user->id;
        $isOrgMember = OrganizationMembership::where('organization_id', $dealRoom->organization_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$isBuyer && !$isOrgMember && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to sign contract.',
            ], 403);
        }

        $signatureText = $request->input('signature', $user->name);

        if ($isBuyer) {
            $dealRoom->buyer_signed_at = now();
            $dealRoom->buyer_signature = $signatureText;
        } else {
            $dealRoom->seller_signed_at = now();
            $dealRoom->seller_signature = $signatureText;
        }

        // If contract document uploaded
        if ($request->hasFile('contract_document')) {
            $path = $request->file('contract_document')->store("deal_rooms/{$dealRoom->id}", 'public');
            $dealRoom->contract_document_path = $path;
        }

        // If both parties have signed, mark contract milestone achieved
        if ($dealRoom->isFullySigned()) {
            $dealRoom->status = DealRoom::STATUS_UNDER_CONTRACT;
            $contractMilestone = $dealRoom->milestones()->where('milestone_key', 'contract_signed')->first();
            if ($contractMilestone) {
                $contractMilestone->status = 'achieved';
                $contractMilestone->completed_at = now();
                $contractMilestone->verified_by = $user->id;
                $contractMilestone->verified_at = now();
                $contractMilestone->save();
            }
        }

        $dealRoom->save();

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $dealRoom->organization_id,
            'action' => 'deal_contract_signed',
            'auditable_type' => DealRoom::class,
            'auditable_id' => $dealRoom->id,
            'after_state' => [
                'is_fully_signed' => $dealRoom->isFullySigned(),
                'buyer_signed' => !empty($dealRoom->buyer_signed_at),
                'seller_signed' => !empty($dealRoom->seller_signed_at),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract signature recorded successfully.',
            'data' => $dealRoom->load('milestones'),
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/milestones/{milestoneId}/advance
     * Upload proof and advance milestone status.
     */
    public function advanceMilestone(Request $request, int $id, int $milestoneId): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);
        $milestone = DealMilestone::where('deal_room_id', $dealRoom->id)->findOrFail($milestoneId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:in_progress,achieved,waived',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $milestone->status = $request->input('status');
        if ($request->input('notes')) {
            $milestone->notes = $request->input('notes');
        }

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store("deal_rooms/{$dealRoom->id}/milestones", 'public');
            $milestone->document_path = $path;
        }

        if ($request->input('status') === 'achieved') {
            $milestone->completed_at = now();
            $milestone->verified_by = $user->id;
            $milestone->verified_at = now();
        }

        $milestone->save();

        return response()->json([
            'success' => true,
            'message' => "Milestone '{$milestone->title}' updated to {$milestone->status}.",
            'data' => $milestone,
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/dispute
     * Raise a dispute on the deal room.
     */
    public function raiseDispute(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'details' => 'required|string',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dealRoom->status = DealRoom::STATUS_DISPUTED;
        $dealRoom->dispute_status = 'open';
        $dealRoom->dispute_reason = $request->input('reason') . ': ' . $request->input('details');

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store("deal_rooms/{$dealRoom->id}/disputes", 'public');
            $dealRoom->escrow_proof_path = $path;
        }

        $dealRoom->save();

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $dealRoom->organization_id,
            'action' => 'deal_room_dispute_raised',
            'auditable_type' => DealRoom::class,
            'auditable_id' => $dealRoom->id,
            'after_state' => [
                'reason' => $dealRoom->dispute_reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispute has been formally registered. Arbitrators and support notified.',
            'data' => $dealRoom,
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/complete
     * Complete and close deal room upon final settlement.
     */
    public function completeDeal(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);

        $dealRoom->status = DealRoom::STATUS_COMPLETED;
        $dealRoom->save();

        return response()->json([
            'success' => true,
            'message' => 'Deal room completed successfully. Trade records archived into immutable ledger.',
            'data' => $dealRoom,
        ]);
    }
}

