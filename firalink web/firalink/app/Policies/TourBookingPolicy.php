<?php

namespace App\Policies;

use App\Models\TourBooking;
use App\Models\User;

/** Tour bookings (slice 8): only the person who booked sees and pays them. */
class TourBookingPolicy
{
    public function view(User $user, TourBooking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    /** The balance can be paid once the deposit is confirmed, before the due date, once at a time. */
    public function payBalance(User $user, TourBooking $booking): bool
    {
        return $this->view($user, $booking)
            && $booking->status === TourBooking::STATUS_CONFIRMED
            && $booking->deposit_paid_at !== null
            && $booking->balance_paid_at === null
            && (float) $booking->balance_amount > 0
            && $booking->payment_status !== 'balance_in_review'
            && ($booking->balance_due_date === null || !$booking->balance_due_date->isPast() || $booking->balance_due_date->isToday());
    }
}
