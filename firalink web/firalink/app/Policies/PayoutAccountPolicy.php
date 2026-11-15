<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PayoutAccount;
use App\Models\User;

/** Payout account: the seller business's owner adds or changes it; reviewers confirm it in the review queue. */
class PayoutAccountPolicy
{
    public static function isOwner(User $user, ?Organization $org): bool
    {
        return $org && MarketplaceRfqPolicy::role($user, $org->id) === OrganizationMembership::ROLE_OWNER;
    }

    /** Owner and managers may see the masked account; only the owner changes it. */
    public static function canSee(User $user, ?Organization $org): bool
    {
        return $org && MarketplaceRfqPolicy::manages($user, $org->id);
    }

    public function review(User $user, PayoutAccount $account): bool
    {
        return $user->can('review-verifications');
    }
}
