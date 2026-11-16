<?php

namespace App\Policies;

use App\Models\MarketSeries;
use App\Models\User;

class MarketSeriesPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, MarketSeries $series): bool
    {
        return true;
    }
}
