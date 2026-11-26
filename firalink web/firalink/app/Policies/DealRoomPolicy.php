<?php

namespace App\Policies;

use App\Models\DealRoom;
use App\Models\User;
use App\Services\Trade\DealRoomService;

class DealRoomPolicy
{
    /**
     * An active member of the buyer business or of the seller business. Deal rooms opened before
     * slice 7 have no buyer business; there the buyer person stands in.
     */
    public function isParticipant(User $user, DealRoom $dealRoom): bool
    {
        return DealRoomService::sideOf($user, $dealRoom) !== null;
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

    /** Sign the contract: owner or manager of the buyer or seller business, never staff. */
    public function sign(User $user, DealRoom $dealRoom): bool
    {
        return DealRoomService::canSign($user, $dealRoom);
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
