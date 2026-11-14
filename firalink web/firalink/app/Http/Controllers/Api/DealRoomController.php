<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\MarketplaceQuotation;
use App\Models\OrganizationMembership;
use App\Services\PrivateDocumentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DealRoomController extends Controller
{
    public function __construct(private PrivateDocumentStore $documents)
    {
    }

    private function forbidden(string $message, string $code = 'FORBIDDEN'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
        ], 403);
    }

    private function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

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
     * The buyer opens a deal room from a quote they received or from a listing.
     * The buyer is always the signed-in user; the seller organization always comes
     * from the quote or listing, never from the request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'listing_id' => 'nullable|required_without:quotation_id|exists:coffee_listings,id',
            'quotation_id' => 'nullable|required_without:listing_id|exists:marketplace_quotations,id',
            'title' => 'required|string|max:255',
            'target_value_usd' => 'required|numeric|min:0',
            'incoterm' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $quotation = $request->input('quotation_id')
            ? MarketplaceQuotation::with(['rfq', 'listing'])->find($request->input('quotation_id'))
            : null;

        if ($quotation) {
            $rfq = $quotation->rfq;
            $isBuyerSide = $rfq && ($rfq->organization_id
                ? \App\Policies\MarketplaceRfqPolicy::manages($user, $rfq->organization_id)
                : (int) $rfq->buyer_id === (int) $user->id);
            if (!$isBuyerSide) {
                return $this->forbidden('Only the buyer who posted the request can open a deal room from this quote.');
            }
            // Slice 5: a deal room starts from an accepted quote only.
            if ($quotation->status !== MarketplaceQuotation::STATUS_ACCEPTED) {
                return response()->json([
                    'success' => false,
                    'code' => 'QUOTE_NOT_ACCEPTED',
                    'message' => 'Accept this quote before opening a deal room.',
                ], 422);
            }
            $existing = DealRoom::where('rfq_id', $quotation->rfq_id)->where('organization_id', $quotation->organization_id ?? $quotation->listing?->organization_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing->load(['organization', 'listing', 'milestones'])]);
            }
        }

        $listing = $quotation?->listing
            ?? ($request->input('listing_id') ? CoffeeListing::find($request->input('listing_id')) : null);

        $orgId = $listing?->organization_id
            ?? $quotation?->seller?->primaryOrganization()?->id
            ?? $listing?->seller?->primaryOrganization()?->id;

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'code' => 'SELLER_ORGANIZATION_REQUIRED',
                'message' => 'The seller of this lot does not have an active business workspace.',
            ], 422);
        }

        $isOwnOrganization = OrganizationMembership::where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if ($isOwnOrganization) {
            return response()->json([
                'success' => false,
                'code' => 'SELF_DEAL',
                'message' => 'You cannot open a deal room with your own business.',
            ], 422);
        }

        $dealRoom = DealRoom::create([
            'deal_reference' => 'DR-' . strtoupper(Str::random(8)),
            'organization_id' => $orgId,
            'buyer_id' => $user->id,
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
            ],
            [
                'title' => 'Letter of Credit / Escrow Proof Verified',
                'milestone_key' => 'payment_escrow',
                'description' => 'Documentary letter of credit (LC) advised or milestone escrow deposit confirmed by bank.',
            ],
            [
                'title' => 'Pre-Shipment Quality & Weight Inspection',
                'milestone_key' => 'preshipment_inspection',
                'description' => 'ECTA & third-party surveyor (SGS/Bureau Veritas) green coffee quality grading and container tare verification.',
            ],
            [
                'title' => 'Port of Djibouti Bill of Lading (B/L) Released',
                'milestone_key' => 'bill_of_lading_released',
                'description' => 'Ocean carrier Bill of Lading, Phytosanitary Certificate, and Certificate of Origin uploaded and authenticated.',
            ],
            [
                'title' => 'Final Settlement & Delivery Confirmation',
                'milestone_key' => 'final_settlement',
                'description' => 'Shipment cleared at destination port, final documents released, and escrow payment settled.',
            ],
        ];

        foreach ($standardMilestones as $m) {
            DealMilestone::create([
                'deal_room_id' => $dealRoom->id,
                'title' => $m['title'],
                'milestone_key' => $m['milestone_key'],
                'description' => $m['description'],
                'status' => DealMilestone::STATUS_PENDING,
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
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::with(['organization', 'buyer', 'listing', 'milestones.verifier'])->findOrFail($id);

        if ($user->cannot('view', $dealRoom)) {
            return $this->forbidden('Unauthorized access to this deal room.');
        }

        return response()->json([
            'success' => true,
            'data' => $dealRoom,
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/sign-contract
     * Buyer or seller signs. Admins can never sign on behalf of a party.
     */
    public function signContract(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);

        if ($user->cannot('participate', $dealRoom)) {
            return $this->forbidden('Only the buyer or the seller can sign this contract.');
        }

        $validator = Validator::make($request->all(), [
            'signature' => 'nullable|string|max:255',
            'contract_document' => 'nullable|file|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        if ($request->hasFile('contract_document')) {
            $reason = $this->documents->rejectionReason($request->file('contract_document'), ['application/pdf']);
            if ($reason) {
                return response()->json($this->documents->errorResponse('contract_document', $reason), 422);
            }
            $dealRoom->contract_document_path = $this->documents->store(
                $request->file('contract_document'),
                "deal_rooms/{$dealRoom->id}"
            );
        }

        $signatureText = $request->input('signature', $user->name);
        $isBuyer = (int) $dealRoom->buyer_id === (int) $user->id;

        if ($isBuyer) {
            $dealRoom->buyer_signed_at = now();
            $dealRoom->buyer_signature = $signatureText;
        } else {
            $dealRoom->seller_signed_at = now();
            $dealRoom->seller_signature = $signatureText;
        }

        // Both signatures are the confirmation for the contract milestone.
        if ($dealRoom->isFullySigned()) {
            $dealRoom->status = DealRoom::STATUS_UNDER_CONTRACT;
            $contractMilestone = $dealRoom->milestones()->where('milestone_key', 'contract_signed')->first();
            if ($contractMilestone) {
                $contractMilestone->status = DealMilestone::STATUS_ACHIEVED;
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
     * A party marks a milestone in progress or uploads proof (bank slip, SWIFT copy,
     * inspection report). Proof only moves the milestone to "proof_submitted".
     * It becomes "achieved" only through confirmMilestone().
     */
    public function advanceMilestone(Request $request, int $id, int $milestoneId): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);
        $milestone = DealMilestone::where('deal_room_id', $dealRoom->id)->findOrFail($milestoneId);

        if ($user->cannot('participate', $dealRoom)) {
            return $this->forbidden('Only the buyer or the seller can update this deal.');
        }

        if (in_array($request->input('status'), DealMilestone::CONFIRMED_STATUSES, true)) {
            return $this->forbidden(
                'A milestone counts as done only after Fira Link confirms it with the bank. Upload your proof instead.',
                'CONFIRMATION_REQUIRED'
            );
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:in_progress',
            'notes' => 'nullable|string|max:2000',
            'document' => 'nullable|file|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        if ($milestone->milestone_key === 'contract_signed') {
            return response()->json([
                'success' => false,
                'code' => 'USE_SIGN_CONTRACT',
                'message' => 'The contract milestone completes when both parties sign.',
            ], 422);
        }

        if (in_array($milestone->status, DealMilestone::CONFIRMED_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'code' => 'MILESTONE_CLOSED',
                'message' => 'This milestone is already confirmed.',
            ], 422);
        }

        if ($request->hasFile('document')) {
            $reason = $this->documents->rejectionReason($request->file('document'));
            if ($reason) {
                return response()->json($this->documents->errorResponse('document', $reason), 422);
            }
            $milestone->document_path = $this->documents->store(
                $request->file('document'),
                "deal_rooms/{$dealRoom->id}/milestones"
            );
            $milestone->status = DealMilestone::STATUS_PROOF_SUBMITTED;
            $milestone->submitted_by = $user->id;
            $milestone->submitted_at = now();
        } elseif ($milestone->status === DealMilestone::STATUS_PENDING) {
            $milestone->status = DealMilestone::STATUS_IN_PROGRESS;
        }

        if ($request->filled('notes')) {
            $milestone->notes = $request->input('notes');
        }

        $milestone->save();

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $dealRoom->organization_id,
            'action' => 'deal_milestone_updated',
            'auditable_type' => DealMilestone::class,
            'auditable_id' => $milestone->id,
            'after_state' => ['status' => $milestone->status, 'has_proof' => !empty($milestone->document_path)],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $milestone->status === DealMilestone::STATUS_PROOF_SUBMITTED
                ? 'Proof submitted. This step counts as done after the bank confirms it.'
                : "Milestone '{$milestone->title}' updated to {$milestone->status}.",
            'data' => $milestone,
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/milestones/{milestoneId}/confirm
     * Admin or finance confirms a milestone after checking with the bank (or inspector).
     * Never available to the buyer or seller.
     */
    public function confirmMilestone(Request $request, int $id, int $milestoneId): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);
        $milestone = DealMilestone::where('deal_room_id', $dealRoom->id)->findOrFail($milestoneId);

        if ($user->cannot('confirmMilestone', $dealRoom)) {
            return $this->forbidden('Only Fira Link finance can confirm a milestone.', 'CONFIRMATION_REQUIRED');
        }

        $validator = Validator::make($request->all(), [
            'decision' => 'nullable|in:achieved,waived',
            'bank_reference' => 'required|string|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        if ($milestone->milestone_key === 'contract_signed') {
            return response()->json([
                'success' => false,
                'code' => 'USE_SIGN_CONTRACT',
                'message' => 'The contract milestone completes when both parties sign.',
            ], 422);
        }

        if (in_array($milestone->status, DealMilestone::CONFIRMED_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'code' => 'MILESTONE_CLOSED',
                'message' => 'This milestone is already confirmed.',
            ], 422);
        }

        $before = $milestone->toArray();

        $milestone->status = $request->input('decision', DealMilestone::STATUS_ACHIEVED);
        $milestone->bank_reference = $request->input('bank_reference');
        $milestone->completed_at = now();
        $milestone->verified_by = $user->id;
        $milestone->verified_at = now();
        if ($request->filled('notes')) {
            $milestone->notes = $request->input('notes');
        }
        $milestone->save();

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $dealRoom->organization_id,
            'action' => 'deal_milestone_confirmed',
            'auditable_type' => DealMilestone::class,
            'auditable_id' => $milestone->id,
            'before_state' => $before,
            'after_state' => $milestone->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Milestone '{$milestone->title}' confirmed.",
            'data' => $milestone,
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/dispute
     */
    public function raiseDispute(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);

        if ($user->cannot('participate', $dealRoom)) {
            return $this->forbidden('Only the buyer or the seller can raise a dispute on this deal.');
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'details' => 'required|string|max:5000',
            'evidence' => 'nullable|file|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        if ($request->hasFile('evidence')) {
            $reason = $this->documents->rejectionReason($request->file('evidence'));
            if ($reason) {
                return response()->json($this->documents->errorResponse('evidence', $reason), 422);
            }
            $dealRoom->escrow_proof_path = $this->documents->store(
                $request->file('evidence'),
                "deal_rooms/{$dealRoom->id}/disputes"
            );
        }

        $dealRoom->status = DealRoom::STATUS_DISPUTED;
        $dealRoom->dispute_status = 'open';
        $dealRoom->dispute_reason = $request->input('reason') . ': ' . $request->input('details');
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
     * A party closes the deal only after final settlement has been confirmed.
     */
    public function completeDeal(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);

        if ($user->cannot('participate', $dealRoom)) {
            return $this->forbidden('Only the buyer or the seller can complete this deal.');
        }

        $settled = $dealRoom->milestones()
            ->where('milestone_key', 'final_settlement')
            ->where('status', DealMilestone::STATUS_ACHIEVED)
            ->exists();

        if (!$settled) {
            return response()->json([
                'success' => false,
                'code' => 'SETTLEMENT_NOT_CONFIRMED',
                'message' => 'The deal can be completed after final settlement is confirmed.',
            ], 422);
        }

        $dealRoom->status = DealRoom::STATUS_COMPLETED;
        $dealRoom->save();

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $dealRoom->organization_id,
            'action' => 'deal_room_completed',
            'auditable_type' => DealRoom::class,
            'auditable_id' => $dealRoom->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal room completed successfully. Trade records archived into immutable ledger.',
            'data' => $dealRoom,
        ]);
    }
}
