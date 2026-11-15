<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\TourBooking;
use Illuminate\Console\Command;

/**
 * Slice 8: give places back. A Chapa checkout left unpaid for an hour frees its seats; a tour whose
 * balance was not paid by the due date frees its places. Bank slips waiting for finance are kept.
 */
class ReleaseUnpaidBookings extends Command
{
    protected $signature = 'bookings:release-unpaid';

    protected $description = 'Cancel unpaid Chapa bookings after an hour and tours with an unpaid balance after the due date';

    public const CHECKOUT_MINUTES = 60;

    public function handle(): int
    {
        $cutoff = now()->subMinutes(self::CHECKOUT_MINUTES);
        $released = 0;

        foreach ([EventBooking::class, TourBooking::class] as $model) {
            $model::where('status', 'pending')->where('payment_status', 'pending')->where('created_at', '<', $cutoff)
                ->each(function ($b) use (&$released) {
                    $column = $b instanceof EventBooking ? 'event_booking_id' : 'tour_booking_id';
                    if (Payment::where($column, $b->id)->where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)->where('status', Payment::STATUS_PENDING)->exists()) {
                        return; // a slip is with finance
                    }
                    $this->release($b, 'checkout_expired');
                    $released++;
                });
        }

        TourBooking::where('status', TourBooking::STATUS_CONFIRMED)
            ->whereNull('balance_paid_at')->where('balance_amount', '>', 0)
            ->whereNotNull('balance_due_date')->whereDate('balance_due_date', '<', now()->toDateString())
            ->where('payment_status', '!=', 'balance_in_review')
            ->each(function (TourBooking $b) use (&$released) {
                $this->release($b, 'balance_unpaid');
                $released++;
            });

        $this->info("Released {$released} booking(s).");

        return self::SUCCESS;
    }

    private function release($booking, string $reason): void
    {
        $booking->forceFill(['status' => 'cancelled', 'notes' => trim(($booking->notes ?? '') . "\nReleased: {$reason} " . now()->toDateTimeString())])->save();
        AuditLog::create([
            'user_id' => null,
            'action' => 'booking_released',
            'auditable_type' => $booking::class,
            'auditable_id' => $booking->id,
            'after_state' => ['reason' => $reason],
            'ip_address' => null,
            'user_agent' => 'system',
        ]);
    }
}
