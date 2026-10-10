<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Event Booking Verification Controller
 *
 * This controller handles the event booking verification functionality.
 *
 * @package App\Http\Controllers\Front
 */
class EventBookingVerificationController extends Controller
{
    public function __invoke(Request $request, string $reference): View
    {
        $booking = EventBooking::query()
            ->with(['event', 'user'])
            ->where('reference', $reference)
            ->firstOrFail();

        $event = $booking->event;

        $details = [
            'reference' => $booking->reference,
            'status' => ucfirst($booking->status ?? EventBooking::STATUS_PENDING),
            'attendee' => $booking->full_name ?: ($booking->user?->name ?? 'Guest'),
            'email' => $booking->email ?: $booking->user?->email,
            'phone' => $booking->phone,
            'seats' => $booking->seats,
            'booked_at' => optional($booking->booked_at)->format('M d, Y H:i'),
            'event' => $event ? [
                'title' => $event->title,
                'start_date' => optional($event->start_date)->format('M d, Y'),
                'start_time' => optional($event->start_time)->format('h:i A'),
                'end_date' => optional($event->end_date)->format('M d, Y'),
                'end_time' => optional($event->end_time)->format('h:i A'),
                'location' => $event->location ?? $event->location_description,
            ] : null,
        ];

        return view('events.booking-verification', [
            'details' => $details,
        ]);
    }
}
