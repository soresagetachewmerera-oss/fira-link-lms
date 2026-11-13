<?php

namespace App\Policies;

use App\Models\DealRoom;
use App\Models\OrganizationMembership;
use App\Models\User;

class DealRoomPolicy
{
    /**
     * The buyer, or an active member of the seller's organization.
     */
    public function isParticipant(User $user, DealRoom $dealRoom): bool
    {
        if ((int) $dealRoom->buyer_id === (int) $user->id) {
            return true;
        }

        return OrganizationMembership::where('organization_id', $dealRoom->organization_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function view(User $user, DealRoom $dealRoom): bool
    {
        return $user->isAdmin() || $this->isParticipant($user, $dealRoom);
    }

    /**
     * Sign, upload proof, raise a dispute. Admins can view but never act as a party.
     */
    public function participate(User $user, DealRoom $dealRoom): bool
    {
        return $this->isParticipant($user, $dealRoom);
    }

    /**
     * Mark a milestone achieved after the bank (or inspector) confirms it.
     * Never a party to the deal: platform admin or finance only.
     */
    public function confirmMilestone(User $user, DealRoom $dealRoom): bool
    {
        if ($this->isParticipant($user, $dealRoom)) {
            return false;
        }

        return $user->isAdmin() || $user->hasRole('finance');
    }
}
