<?php

namespace App\Policies;

use App\Models\TradeConversation;
use App\Models\User;
use App\Services\Trade\DealRoomService;

/** Business chat: active members of either business only. Admins read flagged messages in the review queue, not here. */
class TradeConversationPolicy
{
    /** The user's business in this conversation (their active one first), or null. */
    public static function myOrganizationId(User $user, TradeConversation $c): ?int
    {
        $current = $user->currentOrganization()?->id;
        if ($current && $c->involves((int) $current) && DealRoomService::isMember($user, (int) $current)) {
            return (int) $current;
        }
        foreach ([(int) $c->buyer_organization_id, (int) $c->seller_organization_id] as $orgId) {
            if (DealRoomService::isMember($user, $orgId)) {
                return $orgId;
            }
        }

        return null;
    }

    public function view(User $user, TradeConversation $c): bool
    {
        return self::myOrganizationId($user, $c) !== null;
    }

    public function send(User $user, TradeConversation $c): bool
    {
        return $this->view($user, $c);
    }
}
