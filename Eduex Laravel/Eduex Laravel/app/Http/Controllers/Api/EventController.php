<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;

use App\Http\Requests\Event\StoreEventBookingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query()->published();

        $filter = $request->query('filter', 'upcoming');

        if ($filter === 'upcoming') {
            $query->where('start_date', '>=', now()->toDateString());
        } elseif ($filter === 'past') {
            $query->where('start_date', '<', now()->toDateString());
        }

        $events = $query->with(['speakers', 'highlights'])
            ->withCount([
                'bookings as confirmed_bookings_count' => fn($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ])
            ->orderBy('start_date', $filter === 'past' ? 'desc' : 'asc')
            ->paginate($request->query('per_page', 10));

        return response()->json($events);
    }

    public function show($id)
    {
        $event = Event::with(['highlights', 'speakers', 'instructors'])
            ->withCount([
                'bookings as confirmed_bookings_count' => fn($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ])
            ->findOrFail($id);

        return response()->json($event);
    }

    public function book(StoreEventBookingRequest $request, $id)
    {
        $event = Event::published()->findOrFail($id);
        $user = $request->user();

        $data = $request->validated();
        $seats = (int) ($data['seats'] ?? 1);

        if ($event->total_seats && $event->remainingSeats() < $seats) {
            return response()->json([
                'success' => false,
                'message' => 'Only ' . $event->remainingSeats() . ' seat(s) remaining for this event.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $booking = $event->bookings()->create([
                'user_id' => $user->id,
                'reference' => $this->generateReference(),
                'status' => EventBooking::STATUS_PENDING,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'seats' => $seats,
                'contact_method' => $data['contact_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booked_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your booking has been received!',
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while processing your booking.'
            ], 500);
        }
    }

    private function generateReference(): string
    {
        do {
            $reference = 'EVT-' . Str::upper(Str::random(8));
        } while (EventBooking::where('reference', $reference)->exists());

        return $reference;
    }

    public function myBookings(Request $request)
    {
        $user = $request->user();

        $bookings = $user->eventBookings()
            ->with('event')
            ->latest('booked_at')
            ->paginate($request->query('per_page', 10));

        $transformed = $bookings->getCollection()->transform(function (EventBooking $booking) {
            return [
                'id' => $booking->id,
                'event_title' => $booking->event?->title ?? 'Event removed',
                'event_slug' => $booking->event?->slug,
                'has_event' => $booking->event !== null,
                'booked_at' => optional($booking->booked_at)->toIso8601String(),
                'booked_at_formatted' => optional($booking->booked_at)->format('M d, Y H:i'),
                'start_date' => optional($booking->event?->start_date)->toIso8601String(),
                'start_time' => $booking->event?->start_time,
                'start_date_formatted' => optional($booking->event?->start_date)->format('M d, Y'),
                'start_time_formatted' => optional($booking->event?->start_time)->format('h:i A'),
                'venue' => $booking->event?->location ?? $booking->event?->location_description,
                'seats' => $booking->seats,
                'status' => Str::lower($booking->status ?? EventBooking::STATUS_PENDING),
                'reference' => $booking->reference,
                'total_price' => ($booking->event?->price ?? 0) * $booking->seats,
            ];
        });

        $bookings->setCollection($transformed);

        return response()->json($bookings);
    }

    public function showBooking(Request $request, $id)
    {
        $booking = EventBooking::with(['event', 'user'])->findOrFail($id);

        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $event = $booking->event;
        $ticket = [
            'booking_id' => $booking->reference, // Using reference as booking ID for display
            'event_name' => $event?->title ?? 'Event',
            'event_date' => optional($event?->start_date)->format('F d, Y'),
            'event_time' => optional($event?->start_time)->format('h:i A'),
            'venue' => $event?->location ?? $event?->location_description ?? 'TBA',
            'ticket_type' => $event->price > 0 ? 'Standard' : 'Free Entry', // detailed types if implemented
            'quantity' => $booking->seats,
            'total_price' => ($event->price ?? 0.0) * $booking->seats,
            'status' => Str::lower($booking->status ?? EventBooking::STATUS_PENDING),
            'seat_numbers' => '', // specific seats not yet implemented
            'passenger_name' => $booking->full_name ?: ($booking->user?->name ?? 'Guest'),
            'passenger_id' => 'ID-' . str_pad((string) $booking->user_id, 8, '0', STR_PAD_LEFT),
        ];

        // Generate QR Code
        $verificationUrl = route('events.bookings.verify', $booking->reference);
        $qrPayload = array_filter([
            'reference' => $booking->reference,
            'event' => Arr::only([
                'title' => $event?->title,
                'start_date' => $event?->start_date,
                'start_time' => $event?->start_time,
                'location' => $event?->location
            ], ['title', 'start_date', 'start_time', 'location']),
            'attendee' => $ticket['passenger_name'],
            'seats' => $booking->seats,
            'verification_url' => $verificationUrl,
        ]);

        $qrCodeDataUri = $this->generateQrCodeDataUri(json_encode($qrPayload, JSON_UNESCAPED_SLASHES));

        return response()->json([
            'ticket' => $ticket,
            'qr_code' => $qrCodeDataUri,
        ]);
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
