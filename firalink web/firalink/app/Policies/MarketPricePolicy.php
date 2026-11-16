<?php

namespace App\Policies;

use App\Models\MarketPrice;
use App\Models\User;

class MarketPricePolicy
{
    public function review(User $user, MarketPrice $price): bool
    {
        return $user->can('review-verifications');
    }
}
