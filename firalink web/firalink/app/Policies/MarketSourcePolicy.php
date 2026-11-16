<?php

namespace App\Policies;

use App\Models\MarketSource;
use App\Models\User;

class MarketSourcePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function manage(User $user, MarketSource $source): bool
    {
        return $user->can('review-verifications');
    }
}
