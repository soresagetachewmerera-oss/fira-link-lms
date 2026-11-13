<?php

namespace App\Policies;

use App\Models\MarketplaceSample;
use App\Models\OrganizationMembership;
use App\Models\User;

class MarketplaceSamplePolicy
{
    private function isBuyer(User $user, MarketplaceSample $sample): bool
    {
        return (int) $sample->buyer_id === (int) $user->id;
    }

    private function isSellerMember(User $user, MarketplaceSample $sample): bool
    {
        return OrganizationMembership::where('organization_id', $sample->organization_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function view(User $user, MarketplaceSample $sample): bool
    {
        return $user->isAdmin() || $this->isBuyer($user, $sample) || $this->isSellerMember($user, $sample);
    }

    /** Only the seller's organization ships its own samples. */
    public function dispatch(User $user, MarketplaceSample $sample): bool
    {
        return $this->isSellerMember($user, $sample);
    }

    /** The buyer confirms delivery (and that the seal is unbroken). */
    public function confirmDelivery(User $user, MarketplaceSample $sample): bool
    {
        return $this->isBuyer($user, $sample) || $user->isAdmin();
    }

    public function evaluate(User $user, MarketplaceSample $sample): bool
    {
        return $this->isBuyer($user, $sample) || $user->isAdmin();
    }
}
