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
use App\Services\Trade\DealRoomService;
use App\Services\Trade\TradeChatService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DealRoomController extends Controller
{
    public function __construct(private PrivateDocumentStore $documents, private DealRoomService $deals)
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
        $orgIds = OrganizationMembership::where('user_id', $user->id)->where('is_active', true)->pluck('organization_id');

        $dealRooms = DealRoom::with(['organization', 'buyerOrganization', 'buyer', 'listing', 'milestones'])
            ->where(function ($q) use ($user, $orgIds) {
                $q->where(fn ($w) => $w->whereNull('buyer_organization_id')->where('buyer_id', $user->id))
                    ->orWhereIn('buyer_organization_id', $orgIds)
                    ->orWhereIn('organization_id', $orgIds);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            // Both sides as businesses only (slice 7): no buyer person, no signatures, no file paths.
            'data' => $dealRooms->map(fn ($d) => $this->deals->json($d, $user, false))->values(),
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
                return response()->json(['success' => true, 'data' => $this->deals->json($existing, $user)]);
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

        // Slice 7: the buyer side is a business; a blocked pair cannot open a deal room.
        $buyerOrgId = $quotation?->rfq?->organization_id ?? $user->currentOrganization()?->id;
        if (!$isOwnOrganization && !$buyerOrgId) {
            return response()->json(['success' => false, 'code' => 'ORGANIZATION_REQUIRED', 'message' => __('trade.errors.business_required')], 422);
        }
        if (\App\Models\BusinessBlock::between((int) $buyerOrgId, (int) $orgId)) {
            return response()->json(['success' => false, 'code' => 'BLOCKED', 'message' => __('trade.errors.blocked')], 403);
        }

        if ($isOwnOrganization || (int) $buyerOrgId === (int) $orgId) {
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
            'deposit_percent' => (int) config('deals.deposit_percent', 30),
            'listing_id' => $listing?->id,
            'rfq_id' => $quotation?->rfq_id,
            'title' => $request->input('title'),
            'target_value_usd' => $request->input('target_value_usd'),
            'incoterm' => $request->input('incoterm', 'FOB Djibouti'),
            'status' => DealRoom::STATUS_ACTIVE,
        ]);

        $dealRoom->forceFill(['buyer_organization_id' => $buyerOrgId])->save();

        // Slice 6: an approved sample's seal ID goes into the contract, so the shipped coffee can
        // be compared with the retained copy.
        if ($seal = \App\Services\Trade\SampleService::approvedSealFor($listing?->id, $buyerOrgId)) {
            $dealRoom->forceFill(['sample_seal_id' => $seal])->save();
        }

        // Slice 7: six steps; the deposit and the balance carry their amounts.
        $this->deals->provisionMilestones($dealRoom);

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
            'data' => $this->deals->json($dealRoom->fresh(), $user),
        ], 201);
    }

    /**
     * GET /api/deal-rooms/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::with(['organization', 'buyerOrganization', 'buyer', 'listing', 'milestones'])->findOrFail($id);

        if ($user->cannot('view', $dealRoom)) {
            return $this->forbidden('Unauthorized access to this deal room.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->deals->json($dealRoom, $user),
        ]);
    }

    /**
     * POST /api/deal-rooms/{id}/chat
     * The deal room's business chat thread (created on first open).
     */
    public function chat(Request $request, int $id, TradeChatService $chat): JsonResponse
    {
        $user = $request->user();
        $dealRoom = DealRoom::findOrFail($id);
        if ($user->cannot('participate', $dealRoom)) {
            return $this->forbidden('Only the buyer or the seller can open this chat.');
        }
        try {
            $c = $chat->forDealRoom($dealRoom, $user);
        } catch (VerificationException $e) {
            return $e->toResponse();
        }
        $myOrg = \App\Policies\TradeConversationPolicy::myOrganizationId($user, $c);

        return response()->json(['success' => true, 'data' => $chat->conversationJson($c, (int) $myOrg)]);
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
        if ($dealRoom->paused_at) {
            return (new VerificationException('DEAL_PAUSED', __('trade.errors.deal_paused'), 423))->toResponse();
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
        $isBuyer = DealRoomService::sideOf($user, $dealRoom) === 'buyer';

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
            'data' => $this->deals->json($dealRoom->fresh(), $user),
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
            'payment_method' => 'nullable|in:bank_transfer,letter_of_credit',
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

        // Slice 7: only the current step, only by the side it belongs to, and money only to a
        // confirmed payout account outside its 48 hour change pause.
        try {
            $this->deals->assertCanSubmit($dealRoom->load(['milestones', 'organization']), $milestone, (string) DealRoomService::sideOf($user, $dealRoom));
        } catch (VerificationException $e) {
            return $e->toResponse();
        }
        if ($request->filled('payment_method') && $milestone->milestone_key === 'payment_escrow') {
            $dealRoom->forceFill(['payment_method' => $request->input('payment_method')])->save();
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

        try {
            $this->deals->assertOpen($dealRoom);
            $this->deals->assertCurrent($dealRoom->load('milestones'), $milestone);
        } catch (VerificationException $e) {
            return $e->toResponse();
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
        if ($dealRoom->paused_at) {
            return (new VerificationException('DEAL_PAUSED', __('trade.errors.deal_paused'), 423))->toResponse();
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
