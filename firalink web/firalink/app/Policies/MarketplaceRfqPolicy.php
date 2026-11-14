<?php

namespace App\Policies;

use App\Models\MarketplaceRfq;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\BusinessRole;
use Illuminate\Auth\Access\Response;

/**
 * Requests (RFQs) belong to a buyer or broker business. Only Tier 1 seller businesses see open
 * requests and quote on them; the buyer's own members see their requests and the quotes.
 */
class MarketplaceRfqPolicy
{
    public const BUYER_ROLES = ['buyer', 'broker'];

    public static function role(User $user, ?int $orgId): ?string
    {
        if (!$orgId) {
            return null;
        }

        return OrganizationMembership::where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->value('role');
    }

    public static function manages(User $user, ?int $orgId): bool
    {
        return in_array(self::role($user, $orgId), [OrganizationMembership::ROLE_OWNER, OrganizationMembership::ROLE_MANAGER], true);
    }

    private static function tier1(Organization $org): bool
    {
        return $org->status !== 'suspended' && $org->hasActiveTier(1);
    }

    /** Tier 1 seller business: may see open requests and quote. */
    public static function isVerifiedSeller(?Organization $org): bool
    {
        return $org !== null && self::tier1($org) && in_array($org->roleKey(), BusinessRole::SELLER_ROLES, true);
    }

    public function create(User $user, ?Organization $org = null): Response
    {
        $org ??= $user->currentOrganization();
        if (!$org) {
            return Response::deny(__('trade.errors.business_required'), 'ORGANIZATION_REQUIRED');
        }
        if (!self::manages($user, $org->id)) {
            return Response::deny(__('trade.errors.not_allowed'), 'NOT_ALLOWED');
        }
        if (!in_array($org->roleKey(), self::BUYER_ROLES, true)) {
            return Response::deny(__('trade.errors.buyer_role'), 'BUYER_ROLE_REQUIRED');
        }
        if (!self::tier1($org)) {
            return Response::deny(__('trade.errors.tier_required'), 'VERIFICATION_REQUIRED');
        }

        return Response::allow();
    }

    /** The open request list is for verified seller businesses only. */
    public function viewOpen(User $user, ?Organization $org = null): Response
    {
        $org ??= $user->currentOrganization();

        return self::isVerifiedSeller($org) && self::role($user, $org->id) !== null
            ? Response::allow()
            : Response::deny(__('trade.errors.sellers_only'), 'VERIFIED_SELLER_REQUIRED');
    }

    public function view(User $user, MarketplaceRfq $rfq): bool
    {
        if (self::role($user, $rfq->organization_id) !== null) {
            return true;
        }

        return $this->viewOpen($user)->allowed() && $rfq->isOpen();
    }

    /** Quotes received: the buyer business's members only. */
    public function viewQuotes(User $user, MarketplaceRfq $rfq): bool
    {
        return self::role($user, $rfq->organization_id) !== null;
    }

    public function quote(User $user, MarketplaceRfq $rfq, ?Organization $org = null): Response
    {
        $org ??= $user->currentOrganization();
        if (!$org || !self::manages($user, $org->id)) {
            return Response::deny(__('trade.errors.not_allowed'), 'NOT_ALLOWED');
        }
        if (!self::isVerifiedSeller($org)) {
            return Response::deny(__('trade.errors.sellers_only'), 'VERIFIED_SELLER_REQUIRED');
        }
        if ((int) $org->id === (int) $rfq->organization_id) {
            return Response::deny(__('trade.errors.own_request'), 'OWN_REQUEST');
        }
        if (!$rfq->isOpen()) {
            return Response::deny(__('trade.errors.request_closed'), 'REQUEST_CLOSED');
        }

        return Response::allow();
    }

    /** Accept or decline quotes: the buyer business's owner or manager. */
    public function decide(User $user, MarketplaceRfq $rfq): bool
    {
        return self::manages($user, $rfq->organization_id);
    }
}
