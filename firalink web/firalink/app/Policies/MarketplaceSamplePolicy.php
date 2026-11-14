<?php

namespace App\Policies;

use App\Models\MarketplaceSample;
use App\Models\User;

/**
 * Samples are between two businesses. Members of either business can view a sample; the buyer
 * business's owner or manager pays, confirms delivery and records the cupping result; the seller
 * business's owner or manager seals and ships. Admins do not act for either side.
 */
class MarketplaceSamplePolicy
{
    private function isBuyerSide(User $user, MarketplaceSample $sample, bool $manage): bool
    {
        if ($sample->buyer_organization_id) {
            return $manage
                ? MarketplaceRfqPolicy::manages($user, $sample->buyer_organization_id)
                : MarketplaceRfqPolicy::role($user, $sample->buyer_organization_id) !== null;
        }

        // Samples requested before they belonged to a business.
        return (int) $sample->buyer_id === (int) $user->id;
    }

    public function view(User $user, MarketplaceSample $sample): bool
    {
        return $this->isBuyerSide($user, $sample, false) || MarketplaceRfqPolicy::role($user, $sample->organization_id) !== null;
    }

    /** Seal and hand to the courier: the seller business's owner or manager. */
    public function dispatch(User $user, MarketplaceSample $sample): bool
    {
        return MarketplaceRfqPolicy::manages($user, $sample->organization_id);
    }

    /** Pay, cancel, confirm delivery and record the cupping result: the buyer business. */
    public function buyerAct(User $user, MarketplaceSample $sample): bool
    {
        return $this->isBuyerSide($user, $sample, true);
    }

    public function confirmDelivery(User $user, MarketplaceSample $sample): bool
    {
        return $this->buyerAct($user, $sample);
    }

    public function evaluate(User $user, MarketplaceSample $sample): bool
    {
        return $this->buyerAct($user, $sample);
    }
}
