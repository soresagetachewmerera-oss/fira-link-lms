<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;

use App\Http\Requests\Event\StoreEventBookingRequest;
use App\Models\Payment;
use App\Services\Booking\BookingPaymentService;
use App\Services\Verification\VerificationException;
use App\Support\EventTourPresenter;
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

        // Slice 8: Trade fair, Training, Cupping, Networking; and Online.
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($request->boolean('online')) {
            $query->where('is_online', true);
        }

        $events = $query->with(['speakers', 'highlights', 'organizerOrganization'])
            ->withCount([
                'bookings as confirmed_bookings_count' => fn($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ])
            ->orderBy('start_date', $filter === 'past' ? 'desc' : 'asc')
            ->paginate($request->query('per_page', 10));

        $user = $request->user('sanctum');
        $events->getCollection()->transform(fn (Event $e) => $e->setAttribute('flow', EventTourPresenter::eventFields($e, $user)));

        return response()->json($events);
    }

    public function show(Request $request, $id)
    {
        $event = Event::published()->with(['highlights', 'speakers', 'instructors', 'organizerOrganization'])
            ->withCount([
                'bookings as confirmed_bookings_count' => fn($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ])
            ->findOrFail($id);
        $event->setAttribute('flow', EventTourPresenter::eventFields($event, $request->user('sanctum')));

        return response()->json($event);
    }

    public function book(StoreEventBookingRequest $request, $id, BookingPaymentService $payments)
    {
        $event = Event::published()->findOrFail($id);
        $user = $request->user();

        $data = $request->validated();
        // The register form in the app asks for a phone (an email is optional; phone-only accounts exist).
        $request->validate(['phone' => 'required|string|max:50']);
        $seats = (int) ($data['seats'] ?? 1);
        if ($event->end_date && $event->end_date->lt(now()->startOfDay())) {
            return response()->json(['success' => false, 'code' => 'EVENT_ENDED', 'message' => __('bookings.errors.event_ended')], 422);
        }
        if ($event->total_seats && $event->remainingSeats() < $seats) {
            return response()->json([
                'success' => false,
                'code' => 'SEATS_LEFT',
                'message' => __('bookings.errors.seats_left', ['n' => $event->remainingSeats()]),
            ], 422);
        }

        // The price comes from the ticket type; a member ticket needs an Academy enrollment.
        $type = $data['ticket_type'] ?? 'general';
        $ticket = collect(EventTourPresenter::tickets($event, $user))->firstWhere('type', $type);
        if (!$ticket || !$ticket['eligible']) {
            return response()->json(['success' => false, 'code' => 'TICKET_NOT_ALLOWED', 'message' => __('bookings.errors.member_only')], 422);
        }
        $unitPrice = (float) $ticket['price'];
        $fee = $unitPrice > 0 ? (float) $event->service_fee : 0.0;
        $totalAmount = round(($unitPrice + $fee) * $seats, 2);
        $method = $totalAmount > 0 ? ($data['payment_method'] ?? 'chapa') : 'free';
        if ($totalAmount > 0 && !in_array($method, ['chapa', 'bank_transfer'], true)) {
            return response()->json(['success' => false, 'code' => 'PAYMENT_METHOD_INVALID', 'message' => __('bookings.errors.payment_method')], 422);
        }

        DB::beginTransaction();
        try {
            $booking = new EventBooking();
            $booking->forceFill([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'reference' => $this->generateReference(),
                'status' => $totalAmount <= 0 ? EventBooking::STATUS_CONFIRMED : EventBooking::STATUS_PENDING,
                'payment_status' => $totalAmount <= 0 ? EventBooking::PAYMENT_PAID : EventBooking::PAYMENT_PENDING,
                'payment_method' => $method,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'company' => $data['company'] ?? null,
                'seats' => $seats,
                'ticket_type' => $type,
                'unit_price' => $unitPrice,
                'service_fee' => $fee,
                'total_amount' => $totalAmount,
                'currency' => $event->currency ?? 'ETB',
                'contact_method' => $data['contact_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booked_at' => now(),
            ])->save();

            if ($totalAmount <= 0) {
                DB::commit();

                return response()->json(['success' => true, 'message' => __('bookings.free_confirmed'), 'booking' => $this->bookingJson($booking)]);
            }

            $started = $payments->start($booking, BookingPaymentService::EVENT_TICKET, $totalAmount, $method, $request->file('slip'), $data['bank_reference'] ?? null, $user, 'Event ' . $event->title);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $method === 'bank_transfer' ? __('bookings.slip_in_review') : __('bookings.chapa_next'),
                'payment_required' => $method === 'chapa',
                'checkout_url' => $started['checkout_url'],
                'tx_ref' => $started['tx_ref'],
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'booking' => $this->bookingJson($booking->fresh()),
            ]);
        } catch (VerificationException $e) {
            DB::rollBack();

            return $e->toResponse();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => __('bookings.errors.failed')], 500);
        }
    }

    private function bookingJson(EventBooking $b): array
    {
        return [
            'id' => $b->id,
            'reference' => $b->reference,
            'status' => $b->status,
            'payment_status' => $b->payment_status,
            'seats' => $b->seats,
            'total_amount' => (float) $b->total_amount,
            'currency' => $b->currency,
        ] + EventTourPresenter::eventBookingFields($b);
    }

    /**
     * Verify a Chapa event payment (signed-in owner only). Chapa is asked server to server and the
     * amount must match; the client never marks a ticket paid.
     */
    public function chapaVerify(Request $request, BookingPaymentService $payments)
    {
        $request->validate(['tx_ref' => 'required|string', 'booking_id' => 'required|integer']);
        $booking = EventBooking::find($request->integer('booking_id'));
        if (!$booking || (int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => __('bookings.errors.not_found')], 404);
        }
        $payment = Payment::where('chapa_tx_ref', $request->input('tx_ref'))->where('event_booking_id', $booking->id)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => __('bookings.errors.not_found')], 404);
        }
        try {
            $ok = $payments->verifyChapa($payment);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => __('bookings.errors.verify_failed')], 502);
        }
        $booking->refresh();

        return response()->json([
            'success' => $ok,
            'message' => $ok ? __('bookings.ticket_confirmed') : __('bookings.errors.not_paid'),
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
        ], $ok ? 200 : 400);
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
                'total_price' => (float) $booking->total_amount,
                'payment_status' => $booking->payment_status,
            ] + EventTourPresenter::eventBookingFields($booking);
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
            'ticket_type' => $booking->ticket_type ?? 'general',
            'quantity' => $booking->seats,
            'total_price' => (float) $booking->total_amount,
            'payment_status' => $booking->payment_status,
            'payment_method' => $booking->payment_method,
            'in_review' => $booking->payment_status === 'in_review',
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

        $qrCodeDataUri = $booking->status === EventBooking::STATUS_CONFIRMED
            ? $this->generateQrCodeDataUri(json_encode($qrPayload, JSON_UNESCAPED_SLASHES))
            : null;

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
