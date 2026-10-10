<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Mail\EventBookingBulkMail;
use App\Models\Event;
use App\Models\EventBooking;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Event Booking Email Controller
 *
 * This controller handles the event booking email functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class EventBookingEmailController extends Controller
{
    public function send(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('viewBookings', $event);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(array_merge(['all'], array_keys($this->statusOptions()))),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $recipientsQuery = $event->bookings()->with('user');

        if ($data['status'] !== 'all') {
            $recipientsQuery->where('status', $data['status']);
        }

        $recipients = $recipientsQuery->get();

        if ($recipients->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['status' => 'No bookings match the selected status.']);
        }

        $sent = 0;

        foreach ($recipients as $booking) {
            $email = $booking->user?->email ?? $booking->email;

            if (! $email) {
                continue;
            }

            Mail::to($email)->send(new EventBookingBulkMail(
                $event,
                $data['subject'],
                $data['body'],
                $booking
            ));

            $sent++;
        }

        if ($sent === 0) {
            return back()
                ->withInput()
                ->withErrors(['status' => 'Emails could not be sent because no attendees have a valid email address.']);
        }

        ToastMagic::success("Email Sent Successfully");

        return back();
    }

    /**
     * @return array<string,string>
     */
    private function statusOptions(): array
    {
        return [
            EventBooking::STATUS_PENDING => 'Pending',
            EventBooking::STATUS_CONFIRMED => 'Confirmed',
            EventBooking::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}

