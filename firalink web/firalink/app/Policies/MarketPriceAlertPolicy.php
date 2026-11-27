<?php

namespace App\Policies;

use App\Models\MarketPriceAlert;
use App\Models\User;

class MarketPriceAlertPolicy
{
    /** Any signed-in person may keep alerts (the per-person limit is checked in MarketAlertService). */
    public function create(User $user): bool
    {
        return true;
    }

    /** Only the owner sees or deletes an alert; staff included, nobody else's alerts are visible. */
    public function delete(User $user, MarketPriceAlert $alert): bool
    {
        return (int) $alert->user_id === (int) $user->id;
    }
}
