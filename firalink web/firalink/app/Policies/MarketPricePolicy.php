<?php

namespace App\Policies;

use App\Models\MarketPrice;
use App\Models\User;

class MarketPricePolicy
{
    /** Market reviewers may type in a published price for a source without a feed; it always waits for review. */
    public function create(User $user): bool
    {
        return $user->can('review-verifications');
    }

    /** Market reviewers approve or reject held prices; a typed-in price never by the person who typed it. */
    public function review(User $user, MarketPrice $price): bool
    {
        if ((int) ($price->raw_ref['entered_by'] ?? 0) === (int) $user->id) {
            return false;
        }

        return $user->can('review-verifications');
    }
}
