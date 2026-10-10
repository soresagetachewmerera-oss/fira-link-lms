<?php

namespace App\Events;

use App\Models\UserSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPurchased
{
    use Dispatchable, SerializesModels;

    public function __construct(public UserSubscription $subscription)
    {
    }
}
