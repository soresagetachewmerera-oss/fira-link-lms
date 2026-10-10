<?php

namespace App\Listeners;

use App\Events\SubscriptionPurchased;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSubscriptionConfirmationEmail
{
    public function handle(SubscriptionPurchased $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->user;
        $plan = $subscription->plan;

        Log::info('Subscription activated for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'plan' => $plan?->name,
            'ends_at' => $subscription->ends_at?->toDateTimeString(),
        ]);

        // Email dispatch can be added here
    }
}
