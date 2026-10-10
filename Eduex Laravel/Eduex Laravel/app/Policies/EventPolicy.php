<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Event Policy
 *
 * This policy determines the actions that can be performed on an event.
 *
 * @package App\Policies
 */
class EventPolicy
{
    public function create(User $user): bool
    {
        return $user->isApprovedInstructor();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isApprovedInstructor()
            && $user->instructedEvents()->whereKey($event->id)->exists();
    }

    public function viewBookings(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
