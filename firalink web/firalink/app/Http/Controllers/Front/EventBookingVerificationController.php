<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Event Booking Verification Controller
 *
 * This controller handles the event booking verification and entrance gate check-in functionality.
 *
 * @package App\Http\Controllers\Front
 */
class EventBookingVerificationController extends Controller
{
    public function __invoke(Request $request, string $reference)
    {
        $booking = EventBooking::query()
            ->with(['event', 'user', 'checkedInBy'])
            ->where(function ($q) use ($reference) {
                $q->where('reference', $reference)
                  ->orWhere('verification_token', $reference);
            })
            ->firstOrFail();

        $viewer = $request->user();
        $isStaff = $viewer && (
            $viewer->isAdmin() ||
            (method_exists($viewer, 'hasRole') && ($viewer->hasRole('admin') || $viewer->hasRole('staff') || $viewer->hasRole('instructor'))) ||
            in_array($viewer->role ?? '', ['admin', 'instructor', 'staff'], true)
        );

        // Handle Gate Check-In POST request
        if ($request->isMethod('POST')) {
            abort_unless($isStaff, 403, 'Only authorized event gate staff can check in attendees.');

            if ($booking->isCheckedIn()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'already_checked_in',
                        'message' => 'Ticket was already checked in on ' . $booking->checked_in_at->format('M d, Y h:i A') . ' by ' . ($booking->checkedInBy?->name ?? 'Gate Staff'),
                        'checked_in_at' => $booking->checked_in_at->format('M d, Y h:i A'),
                        'checked_in_by' => $booking->checkedInBy?->name ?? 'Gate Staff',
                    ], 422);
                }
                return back()->with('error', 'Ticket was already checked in at ' . $booking->checked_in_at->format('h:i A') . ' by ' . ($booking->checkedInBy?->name ?? 'Gate Staff'));
            }

            if ($booking->status === EventBooking::STATUS_CANCELLED) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'cancelled',
                        'message' => 'This ticket has been cancelled and is not valid for entry.',
                    ], 422);
                }
                return back()->with('error', 'This ticket has been cancelled.');
            }

            $booking->update([
                'checked_in_at' => now(),
                'checked_in_by' => $viewer->id,
                'status' => EventBooking::STATUS_CONFIRMED,
            ]);

            $booking->load('checkedInBy');

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'status' => 'checked_in',
                    'message' => 'Entry confirmed! Attendee checked in successfully.',
                    'checked_in_at' => $booking->checked_in_at->format('M d, Y h:i A'),
                    'checked_in_by' => $booking->checkedInBy?->name ?? $viewer->name,
                ]);
            }

            return back()->with('success', 'Entry confirmed! Attendee has been checked in successfully.');
        }

        $event = $booking->event;

        $verificationState = 'valid';
        if ($booking->isCheckedIn()) {
            $verificationState = 'already_used';
        } elseif ($booking->status === EventBooking::STATUS_CANCELLED) {
            $verificationState = 'cancelled';
        } elseif ($booking->payment_status !== EventBooking::PAYMENT_PAID && $booking->total_amount > 0) {
            $verificationState = 'pending_payment';
        }

        $details = [
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'verification_token' => $booking->verification_token,
            'status' => ucfirst($booking->status ?? EventBooking::STATUS_PENDING),
            'verification_state' => $verificationState,
            'attendee' => $booking->full_name ?: ($booking->user?->name ?? 'Guest Attendee'),
            'category' => $booking->category_label,
            'is_student' => $booking->isStudent(),
            'student_section' => $booking->student_section,
            'company' => $booking->company,
            'profile_photo_url' => $booking->user?->profile_photo_url,
            'initials' => $booking->user?->initials ?? mb_strtoupper(mb_substr($booking->first_name, 0, 1) . mb_substr($booking->last_name, 0, 1)),
            'seats' => $booking->seats,
            'booked_at' => optional($booking->booked_at)->format('M d, Y H:i'),
            'checked_in_at' => $booking->checked_in_at ? $booking->checked_in_at->format('M d, Y h:i A') : null,
            'checked_in_by' => $booking->checkedInBy?->name,
            'event' => $event ? [
                'id' => $event->id,
                'title' => $event->title,
                'start_date' => optional($event->start_date)->format('l, M d, Y'),
                'start_time' => optional($event->start_time)->format('h:i A'),
                'end_date' => optional($event->end_date)->format('l, M d, Y'),
                'end_time' => optional($event->end_time)->format('h:i A'),
                'location' => $event->location ?? $event->location_description,
                'slug' => $event->slug,
            ] : null,
        ];

        return view('events.booking-verification', [
            'booking' => $booking,
            'details' => $details,
            'isStaff' => $isStaff,
        ]);
    }
}
