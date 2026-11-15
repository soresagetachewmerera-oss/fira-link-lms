<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\OrganizationMembership;
use App\Models\Organization;
use App\Models\TradeConversation;
use App\Models\TradeMessage;
use App\Policies\TradeConversationPolicy;
use App\Services\Trade\TradeChatService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Business chat (slice 7): deal room threads and "Message seller" threads. Both sides see each
 * other as businesses. Messages with contact details are delivered and flagged with a warning.
 */
class TradeChatController extends Controller
{
    public function __construct(private TradeChatService $chat)
    {
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('trade.errors.not_allowed')], 403);
    }

    /** GET /trade-chats: the active business's threads, newest first. */
    public function index(Request $request): JsonResponse
    {
        $org = $request->user()->currentOrganization();
        if (!$org || !OrganizationMembership::where('organization_id', $org->id)->where('user_id', $request->user()->id)->where('is_active', true)->exists()) {
            return response()->json(['success' => false, 'code' => 'ORGANIZATION_REQUIRED', 'message' => __('trade.errors.business_required')], 404);
        }
        $threads = TradeConversation::with(['buyerOrganization', 'sellerOrganization', 'listing', 'dealRoom'])
            ->where(fn ($q) => $q->where('buyer_organization_id', $org->id)->orWhere('seller_organization_id', $org->id))
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->limit(100)->get();

        return response()->json(['success' => true, 'data' => $threads->map(fn ($c) => $this->chat->conversationJson($c, (int) $org->id))->values()]);
    }

    /** POST /trade-chats {seller_organization_id, listing_id?}: "Message seller". */
    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'seller_organization_id' => 'required|integer',
            'listing_id' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'code' => 'VALIDATION', 'message' => 'Validation error', 'errors' => $v->errors()], 422);
        }
        $user = $request->user();
        $buyer = $user->currentOrganization();
        if (!$buyer || !OrganizationMembership::where('organization_id', $buyer->id)->where('user_id', $user->id)->where('is_active', true)->exists()) {
            return response()->json(['success' => false, 'code' => 'ORGANIZATION_REQUIRED', 'message' => __('trade.errors.business_required')], 422);
        }
        $seller = Organization::find($request->integer('seller_organization_id'));
        if (!$seller) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND', 'message' => __('trade.errors.not_allowed')], 404);
        }
        $lot = $request->filled('listing_id') ? CoffeeListing::find($request->integer('listing_id')) : null;

        try {
            $c = $this->chat->withSeller($buyer, $seller, $lot, $user);
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->chat->conversationJson($c, (int) $buyer->id)], $c->wasRecentlyCreated ? 201 : 200);
    }

    /** GET /trade-chats/{id}?after=ID: messages (oldest first), marks the other side's as read. */
    public function show(Request $request, TradeConversation $conversation): JsonResponse
    {
        $user = $request->user();
        if ($user->cannot('view', $conversation)) {
            return $this->forbidden();
        }
        $myOrg = (int) TradeConversationPolicy::myOrganizationId($user, $conversation);
        $messages = TradeMessage::where('trade_conversation_id', $conversation->id)
            ->when($request->filled('after'), fn ($q) => $q->where('id', '>', $request->integer('after')))
            ->orderByDesc('id')->limit(200)->get()->reverse()->values();
        $this->chat->markRead($conversation, $myOrg);

        return response()->json(['success' => true, 'data' => [
            'conversation' => $this->chat->conversationJson($conversation, $myOrg),
            'messages' => $messages->map(fn ($m) => $this->chat->messageJson($m, $myOrg))->values(),
        ]]);
    }

    /** POST /trade-chats/{id}/messages {body} */
    public function send(Request $request, TradeConversation $conversation): JsonResponse
    {
        $user = $request->user();
        if ($user->cannot('send', $conversation)) {
            return $this->forbidden();
        }
        $myOrg = Organization::find(TradeConversationPolicy::myOrganizationId($user, $conversation));
        try {
            $m = $this->chat->send($conversation, $myOrg, $user, (string) $request->input('body'));
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->chat->messageJson($m, (int) $myOrg->id)], 201);
    }
}
