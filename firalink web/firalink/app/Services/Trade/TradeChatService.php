<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\BusinessBlock;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\Organization;
use App\Models\TradeConversation;
use App\Models\TradeMessage;
use App\Models\User;
use App\Notifications\TradeUpdate;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\Verification\VerificationException;
use App\Support\BusinessSummary;
use Illuminate\Support\Facades\DB;

/**
 * Business chat (slice 7). Both sides are businesses and see each other by business name only.
 * A message with contact details or an off-platform invitation is still delivered, but flagged:
 * the recipient sees a warning and admins see it in the flagged list.
 */
class TradeChatService
{
    public const MAX_BODY = 2000;

    /** The deal room's thread, created on first open. */
    public function forDealRoom(DealRoom $deal, User $by): TradeConversation
    {
        $buyerOrgId = $deal->buyer_organization_id ?: $deal->buyer?->currentOrganization()?->id;
        if (!$buyerOrgId) {
            throw new VerificationException('ORGANIZATION_REQUIRED', __('trade.errors.business_required'), 422);
        }

        return TradeConversation::where('deal_room_id', $deal->id)->first() ?? tap(new TradeConversation(), function ($c) use ($deal, $buyerOrgId, $by) {
            $c->forceFill([
                'buyer_organization_id' => $buyerOrgId,
                'seller_organization_id' => $deal->organization_id,
                'deal_room_id' => $deal->id,
                'listing_id' => $deal->listing_id,
                'started_by' => $by->id,
            ])->save();
        });
    }

    /** "Message seller" from a lot or exporter page: one thread per buyer and seller business. */
    public function withSeller(Organization $buyer, Organization $seller, ?CoffeeListing $lot, User $by): TradeConversation
    {
        if ((int) $buyer->id === (int) $seller->id) {
            throw new VerificationException('OWN_BUSINESS', __('trade.errors.own_business_chat'), 422);
        }
        if (!MarketplaceRfqPolicy::isVerifiedSeller($seller)) {
            throw new VerificationException('SELLER_NOT_VERIFIED', __('trade.blockers.SELLER_NOT_VERIFIED'), 422);
        }
        if ($buyer->status === 'suspended') {
            throw new VerificationException('BUSINESS_SUSPENDED', __('verification.public.suspended'), 403);
        }
        $this->assertNotBlocked($buyer->id, $seller->id);
        if ($lot && (int) $lot->organization_id !== (int) $seller->id) {
            $lot = null;
        }

        return DB::transaction(function () use ($buyer, $seller, $lot, $by) {
            $existing = TradeConversation::where('buyer_organization_id', $buyer->id)->where('seller_organization_id', $seller->id)
                ->whereNull('deal_room_id')->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }
            $c = new TradeConversation();
            $c->forceFill([
                'buyer_organization_id' => $buyer->id,
                'seller_organization_id' => $seller->id,
                'listing_id' => $lot?->id,
                'started_by' => $by->id,
            ])->save();

            return $c;
        });
    }

    public function assertNotBlocked(?int $a, ?int $b): void
    {
        if (BusinessBlock::between($a, $b)) {
            throw new VerificationException('BLOCKED', __('trade.errors.blocked'), 403);
        }
    }

    public function send(TradeConversation $c, Organization $from, User $by, string $body): TradeMessage
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > self::MAX_BODY) {
            throw new VerificationException('MESSAGE_INVALID', __('trade.errors.message_invalid'), 422, ['errors' => ['body' => ['invalid']]]);
        }
        if ($from->status === 'suspended') {
            throw new VerificationException('BUSINESS_SUSPENDED', __('verification.public.suspended'), 403);
        }
        $toId = $c->otherOrganizationId((int) $from->id);
        $this->assertNotBlocked($from->id, $toId);

        $kinds = ContactDetector::find($body);
        $m = new TradeMessage();
        $m->forceFill([
            'trade_conversation_id' => $c->id,
            'organization_id' => $from->id,
            'sender_id' => $by->id,
            'body' => $body,
            'flagged' => $kinds !== [],
            'flag_kinds' => $kinds ?: null,
        ])->save();
        $c->forceFill(['last_message_at' => now()])->save();

        if ($kinds) {
            AuditLog::create([
                'user_id' => $by->id,
                'organization_id' => $from->id,
                'action' => 'trade_message_flagged',
                'auditable_type' => TradeMessage::class,
                'auditable_id' => $m->id,
                'after_state' => ['kinds' => $kinds],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent() ?? 'system',
            ]);
        }

        Organization::find($toId)?->owner()?->notify(new TradeUpdate('message_received', ['seller' => $from->name, 'buyer' => $from->name]));

        return $m;
    }

    public function markRead(TradeConversation $c, int $readerOrgId): void
    {
        TradeMessage::where('trade_conversation_id', $c->id)->where('organization_id', '!=', $readerOrgId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    // ── JSON ────────────────────────────────────────────────────────────────

    public function conversationJson(TradeConversation $c, int $myOrgId): array
    {
        $c->loadMissing(['buyerOrganization', 'sellerOrganization', 'listing', 'dealRoom']);
        $other = $myOrgId === (int) $c->buyer_organization_id ? $c->sellerOrganization : $c->buyerOrganization;
        $last = $c->messages()->latest('id')->first();

        return [
            'id' => $c->id,
            'my_side' => $myOrgId === (int) $c->buyer_organization_id ? 'buyer' : 'seller',
            'other' => BusinessSummary::of($other),
            'deal_room' => $c->dealRoom ? ['id' => $c->dealRoom->id, 'deal_reference' => $c->dealRoom->deal_reference, 'title' => $c->dealRoom->title] : null,
            'lot' => $c->listing ? ['id' => $c->listing->id, 'title' => $c->listing->title, 'lot_code' => $c->listing->lot_code] : null,
            'blocked' => BusinessBlock::between($myOrgId, $other?->id),
            'blocked_by_me' => $other ? BusinessBlock::where('organization_id', $myOrgId)->where('blocked_organization_id', $other->id)->exists() : false,
            'last_message' => $last ? mb_substr($last->body, 0, 120) : null,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'unread' => $c->messages()->where('organization_id', '!=', $myOrgId)->whereNull('read_at')->count(),
        ];
    }

    public function messageJson(TradeMessage $m, int $myOrgId): array
    {
        $mine = (int) $m->organization_id === $myOrgId;

        return [
            'id' => $m->id,
            'mine' => $mine,
            'body' => $m->body,
            // The warning is for the recipient; the sender only learns it was flagged.
            'flagged' => (bool) $m->flagged,
            'flag_kinds' => $m->flag_kinds ?? [],
            'show_warning' => !$mine && $m->flagged,
            'read' => (bool) $m->read_at,
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }
}
