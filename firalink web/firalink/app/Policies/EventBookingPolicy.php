<?php

namespace App\Policies;

use App\Models\EventBooking;
use App\Models\User;

/** Event tickets (slice 8): only the person who booked sees them. */
class EventBookingPolicy
{
    public function view(User $user, EventBooking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }
}
