<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Revenue Share Service
 *
 * Handles revenue share calculation and balance updates for Courses, Bundles, and Subscriptions.
 * Logs per-instructor payout breakdown into payments.notes as parseable JSON for audit trail.
 *
 * @package App\Services
 */
class RevenueShareService
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    public function settings(): array
    {
        return $this->settingsRepository->get('revenue.distribution', [
            'mode' => 'percentage',
            'value' => 0,
        ]);
    }

    public function calculate(float $amount): array
    {
        $settings = $this->settings();
        $mode = $settings['mode'] ?? 'percentage';
        $value = (float) ($settings['value'] ?? 0);

        $commission = 0.0;

        if ($amount <= 0) {
            return [
                'platform_commission' => 0.0,
                'instructor_earning' => 0.0,
            ];
        }

        if ($mode === 'fixed') {
            $commission = min($value, $amount);
        } else {
            if ($value < 0) $value = 0;
            if ($value > 100) $value = 100;
            $commission = round($amount * ($value / 100), 2);
        }

        $commission = round($commission, 2);
        $instructor = round($amount - $commission, 2);
        if ($instructor < 0) $instructor = 0.0;

        return [
            'platform_commission' => $commission,
            'instructor_earning' => $instructor,
        ];
    }

    public function process(Payment $payment, bool $force = false): Payment
    {
        if (!$force && $payment->commission_processed) {
            return $payment;
        }

        $amount = (float) $payment->amount;
        $distribution = $this->calculate($amount);
        $platformCommission = $distribution['platform_commission'];
        $instructorEarning = $distribution['instructor_earning'];

        return DB::transaction(function () use ($payment, $platformCommission, $instructorEarning, $amount) {
            $payment->platform_commission = $platformCommission;
            $payment->commission_processed = true;

            // 1. Handle Bundle Direct Purchase Revenue Share
            if ($payment->bundle_id) {
                $payment->loadMissing('bundle.courses');
                $bundle = $payment->bundle;

                if ($bundle) {
                    $vendor = $bundle->vendor;

                    if ($vendor && $vendor->hasRole('admin')) {
                        $totalCourses = $bundle->courses->count();
                        if ($totalCourses > 0) {
                            $instructorCounts = [];
                            foreach ($bundle->courses as $c) {
                                $instructorCounts[$c->instructor_id] = ($instructorCounts[$c->instructor_id] ?? 0) + 1;
                            }
                            foreach ($instructorCounts as $instructorId => $count) {
                                $share = round(($count / $totalCourses) * $instructorEarning, 2);
                                if ($share > 0) {
                                    \App\Models\User::where('id', $instructorId)->increment('balance', $share);
                                }
                            }
                        }
                    } elseif ($vendor && $vendor->hasRole('instructor')) {
                        $vendor->increment('balance', $instructorEarning);
                    }

                    $payment->instructor_earning = $instructorEarning;
                }

            // 3. Handle Standard Single Course Direct Purchase Revenue Share
            } else {
                $payment->loadMissing('enrollment.course.instructor');
                $instructor = optional($payment->enrollment)->course?->instructor;
                $payment->instructor_earning = $instructorEarning;

                if ($instructor && $instructorEarning > 0) {
                    $instructor->increment('balance', $instructorEarning);
                }
            }

            $payment->save();
            return $payment->fresh();
        });
    }

    /**
     * Parse the subscription revenue breakdown from a payment's notes field.
     * Returns null if no breakdown exists (regular course payment).
     */
    public static function parseBreakdown(Payment $payment): ?array
    {
        if (!$payment->notes) return null;
        if (!preg_match('/<!--\s*REVENUE_BREAKDOWN:(.*?)\s*-->/', $payment->notes, $matches)) return null;
        $decoded = json_decode($matches[1], true);
        return is_array($decoded) ? $decoded : null;
    }
}
