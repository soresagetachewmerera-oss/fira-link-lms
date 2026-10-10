<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Events\SubscriptionPurchased;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Service
 *
 * Handles purchase, activation, renewal, and validation of user subscriptions.
 *
 * @package App\Services
 */
class SubscriptionService
{
    /**
     * Create or renew user subscription upon completed payment.
     */
    public function activateSubscription(User $user, SubscriptionPlan $plan, ?Payment $payment = null, string $status = UserSubscription::STATUS_ACTIVE): UserSubscription
    {
        return DB::transaction(function () use ($user, $plan, $payment, $status) {
            $startsAt = now();
            $endsAt = $plan->is_lifetime ? null : $startsAt->copy()->addDays($plan->duration_days);

            // Deactivate any existing active subscriptions for this user
            if ($status === UserSubscription::STATUS_ACTIVE) {
                UserSubscription::where('user_id', $user->id)
                    ->where('status', UserSubscription::STATUS_ACTIVE)
                    ->update(['status' => UserSubscription::STATUS_CANCELLED]);
            }

            $userSubscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'payment_id' => $payment?->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status,
                'courses_accessed_count' => 0,
            ]);

            if ($payment) {
                $payment->update([
                    'subscription_plan_id' => $plan->id,
                    'user_subscription_id' => $userSubscription->id,
                ]);

                if ($status === UserSubscription::STATUS_ACTIVE && $payment->status === Payment::STATUS_COMPLETED) {
                    app(RevenueShareService::class)->process($payment);
                }
            }

            if ($status === UserSubscription::STATUS_ACTIVE) {
                event(new SubscriptionPurchased($userSubscription));
            }

            return $userSubscription;
        });
    }

    /**
     * Check if a user currently has access to a course via subscription.
     * Note: Access is valid strictly while subscription status is ACTIVE and NOT EXPIRED.
     */
    public function canAccessCourse(User $user, int $courseId): bool
    {
        return $user->hasAccessToCourseViaSubscription($courseId);
    }

    /**
     * Check if a user currently has access to a bundle via subscription.
     * Note: Access is valid strictly while subscription status is ACTIVE and NOT EXPIRED.
     */
    public function canAccessBundle(User $user, int $bundleId): bool
    {
        return $user->hasAccessToBundleViaSubscription($bundleId);
    }

    /**
     * Manually assign a subscription to a user by Admin.
     */
    public function manualAssign(User $user, SubscriptionPlan $plan, ?int $durationDays = null): UserSubscription
    {
        return DB::transaction(function () use ($user, $plan, $durationDays) {
            $days = $durationDays ?? $plan->duration_days;
            $startsAt = now();
            $endsAt = ($plan->is_lifetime || $days <= 0) ? null : $startsAt->copy()->addDays($days);

            UserSubscription::where('user_id', $user->id)
                ->where('status', UserSubscription::STATUS_ACTIVE)
                ->update(['status' => UserSubscription::STATUS_CANCELLED]);

            $userSubscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'payment_id' => null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => UserSubscription::STATUS_ACTIVE,
                'courses_accessed_count' => 0,
            ]);

            event(new SubscriptionPurchased($userSubscription));

            return $userSubscription;
        });
    }

    /**
     * Approve pending offline subscription payment.
     */
    public function approveOfflineSubscription(Payment $payment): bool
    {
        if ($payment->status === Payment::STATUS_COMPLETED) {
            return true;
        }

        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => Payment::STATUS_COMPLETED]);

            if ($payment->userSubscription) {
                $payment->userSubscription->update([
                    'status' => UserSubscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => $payment->subscriptionPlan->is_lifetime
                        ? null
                        : now()->addDays($payment->subscriptionPlan->duration_days),
                ]);

                event(new SubscriptionPurchased($payment->userSubscription));
            }

            app(RevenueShareService::class)->process($payment);
            return true;
        });
    }
}
