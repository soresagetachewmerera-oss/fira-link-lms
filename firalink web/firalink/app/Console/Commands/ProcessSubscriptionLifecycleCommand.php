<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Services\EntitlementService;
use Illuminate\Console\Command;

class ProcessSubscriptionLifecycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-lifecycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process SaaS subscription renewals, grace period transitions, and expiry reconciliations';

    /**
     * Execute the console command.
     */
    public function handle(EntitlementService $entitlementService): int
    {
        $this->info('Starting SaaS subscription lifecycle evaluation...');
        $starterPlan = Plan::where('key', Plan::KEY_STARTER)->first();

        // 1. Process Active Subscriptions that have reached period end
        $expiredActive = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now())
            ->get();

        foreach ($expiredActive as $sub) {
            if ($sub->cancel_at_period_end) {
                // Scheduled cancellation
                $sub->update([
                    'status' => Subscription::STATUS_CANCELED,
                    'grace_until' => null,
                ]);

                SubscriptionEvent::create([
                    'subscription_id' => $sub->id,
                    'organization_id' => $sub->organization_id,
                    'event_type' => SubscriptionEvent::EVENT_EXPIRED,
                    'from_plan_id' => $sub->plan_id,
                    'to_plan_id' => $starterPlan?->id,
                    'metadata' => ['reason' => 'User scheduled cancellation at period end'],
                    'created_at' => now(),
                ]);

                if ($starterPlan) {
                    $entitlementService->reconcileDowngrade($sub->organization, $starterPlan);
                }

                $this->warn("Subscription #{$sub->id} canceled at period end for org #{$sub->organization_id}");
            } else {
                // Enter 14-day Grace Period
                $graceDays = 14; // Configurable grace window
                $sub->update([
                    'status' => Subscription::STATUS_GRACE,
                    'grace_until' => now()->addDays($graceDays),
                ]);

                SubscriptionEvent::create([
                    'subscription_id' => $sub->id,
                    'organization_id' => $sub->organization_id,
                    'event_type' => SubscriptionEvent::EVENT_GRACE_STARTED,
                    'from_plan_id' => $sub->plan_id,
                    'metadata' => ['grace_days' => $graceDays, 'grace_until' => now()->addDays($graceDays)->toIso8601String()],
                    'created_at' => now(),
                ]);

                AuditLog::log('subscription_grace_entered', $sub, [], ['grace_days' => $graceDays], $sub->organization_id);

                $this->warn("Subscription #{$sub->id} entered {$graceDays}-day grace period for org #{$sub->organization_id}");
            }
        }

        // 2. Process Subscriptions whose Grace Period has expired
        $lapsedGrace = Subscription::whereIn('status', [Subscription::STATUS_GRACE, Subscription::STATUS_PAST_DUE])
            ->whereNotNull('grace_until')
            ->where('grace_until', '<=', now())
            ->get();

        foreach ($lapsedGrace as $sub) {
            $fromPlanId = $sub->plan_id;
            $sub->update([
                'status' => Subscription::STATUS_EXPIRED,
                'grace_until' => null,
            ]);

            SubscriptionEvent::create([
                'subscription_id' => $sub->id,
                'organization_id' => $sub->organization_id,
                'event_type' => SubscriptionEvent::EVENT_EXPIRED,
                'from_plan_id' => $fromPlanId,
                'to_plan_id' => $starterPlan?->id,
                'metadata' => ['reason' => 'Grace period ended without successful renewal payment'],
                'created_at' => now(),
            ]);

            // Reconcile excess items down to Starter tier
            if ($starterPlan) {
                $entitlementService->reconcileDowngrade($sub->organization, $starterPlan);
            }

            AuditLog::log('subscription_expired', $sub, ['from_plan' => $fromPlanId], ['to_plan' => $starterPlan?->id], $sub->organization_id);

            $this->error("Subscription #{$sub->id} expired. Org #{$sub->organization_id} downgraded to Starter limits.");
        }

        $this->info('SaaS subscription lifecycle evaluation completed.');
        return Command::SUCCESS;
    }
}
