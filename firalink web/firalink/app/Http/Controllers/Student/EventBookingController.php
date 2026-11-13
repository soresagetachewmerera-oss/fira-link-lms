<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Event Booking Controller
 *
 * This controller handles the event booking functionality.
 *
 * @package App\Http\Controllers\Student
 */
class EventBookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $bookings = $user->eventBookings()
            ->with('event')
            ->latest('booked_at')
            ->paginate(10);

        $transformed = $bookings->getCollection()->transform(function (EventBooking $booking) {
            $status = Str::lower($booking->status ?? EventBooking::STATUS_PENDING);

            return [
                'id' => $booking->id,
                'event_title' => $booking->event?->title ?? 'Event removed',
                'event_slug' => $booking->event?->slug,
                'has_event' => $booking->event !== null,
                'booked_at' => $booking->booked_at,
                'booked_at_formatted' => optional($booking->booked_at)->format('M d, Y H:i'),
                'start_date' => $booking->event?->start_date,
                'start_time' => $booking->event?->start_time,
                'start_date_formatted' => optional($booking->event?->start_date)->format('M d, Y'),
                'start_time_formatted' => optional($booking->event?->start_time)->format('h:i A'),
                'seats' => $booking->seats,
                'status' => $status,
                'status_label' => ucfirst($status),
                'status_badge_class' => $this->statusBadgeClass($status),
                'reference' => $booking->reference,
                'ticket_url' => route('student.events.bookings.ticket', $booking->id),
            ];
        });

        $bookings->setCollection($transformed);

        return view('student.events.bookings', [
            'bookings' => $bookings,
            'user' => $user,
        ]);
    }

    public function ticket(Request $request, EventBooking $booking): Response
    {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $booking->loadMissing('event', 'user');

        $event = $booking->event;
        $ticket = [
            'reference' => $booking->reference,
            'attendee' => $booking->full_name ?: ($booking->user?->name ?? 'Attendee'),
            'email' => $booking->email ?: ($booking->user?->email ?? null),
            'phone' => $booking->phone,
            'seats' => $booking->seats,
            'status' => ucfirst(Str::lower($booking->status ?? EventBooking::STATUS_PENDING)),
            'booked_at' => optional($booking->booked_at)->format('M d, Y H:i'),
            'issued_at' => now()->format('M d, Y H:i'),
            'event' => [
                'title' => $event?->title ?? 'Event',
                'start_date' => optional($event?->start_date)->format('M d, Y'),
                'start_time' => optional($event?->start_time)->format('h:i A'),
                'end_date' => optional($event?->end_date)->format('M d, Y'),
                'end_time' => optional($event?->end_time)->format('h:i A'),
                'location' => $event?->location ?? $event?->location_description,
                'contact_email' => $event?->contact_email,
                'contact_phone' => $event?->contact_phone,
                'slug' => $event?->slug,
            ],
        ];

        $verificationUrl = route('events.bookings.verify', $booking->reference);

        $qrPayload = array_filter([
            'reference' => $ticket['reference'],
            'event' => Arr::only($ticket['event'], ['title', 'start_date', 'start_time', 'location']),
            'attendee' => $ticket['attendee'],
            'seats' => $ticket['seats'],
            'verification_url' => $verificationUrl,
        ]);

        $qrCodeDataUri = $this->generateQrCodeDataUri(json_encode($qrPayload, JSON_UNESCAPED_SLASHES));

        $pdf = Pdf::loadView('student.events.booking-ticket', [
            'ticket' => $ticket,
            'qrCodeDataUri' => $qrCodeDataUri,
            'verificationUrl' => $verificationUrl,
        ])->setPaper('a4');

        $fileName = sprintf('event-booking-%s.pdf', Str::slug($booking->reference));

        return $pdf->stream($fileName);
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            EventBooking::STATUS_CONFIRMED => 'badge bg-success',
            EventBooking::STATUS_CANCELLED => 'badge bg-danger',
            default => 'badge bg-warning text-dark',
        };
    }

    private function generateQrCodeDataUri(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(260),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        $svg = $writer->writeString($payload);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
