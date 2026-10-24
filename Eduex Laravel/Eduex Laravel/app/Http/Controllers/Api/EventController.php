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
        $user = $request->user('sanctum') ?? $request->user();

        $data = $request->validated();
        $seats = (int) ($data['seats'] ?? 1);

        if ($event->total_seats && $event->remainingSeats() < $seats) {
            return response()->json([
                'success' => false,
                'message' => 'Only ' . $event->remainingSeats() . ' seat(s) remaining for this event.'
            ], 422);
        }

        $unitPrice = (float)($event->price ?? 0);
        $totalAmount = $unitPrice * $seats;
        $paymentMethod = $data['payment_method'] ?? ($totalAmount > 0 ? 'chapa' : 'free');

        // Strictly prevent booking paid event as free
        if ($totalAmount > 0 && $paymentMethod === 'free') {
            return response()->json([
                'success' => false,
                'message' => 'This event requires a paid ticket of ' . number_format($totalAmount, 2) . ' ' . ($event->currency ?? 'ETB') . '. Free reservations are not permitted for paid events.',
            ], 422);
        }

        if ($totalAmount > 0 && $paymentMethod !== 'chapa') {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid payment method (e.g. Chapa) to book this event.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $booking = $event->bookings()->create([
                'user_id' => $user ? $user->id : null,
                'reference' => $this->generateReference(),
                'status' => ($totalAmount <= 0) ? EventBooking::STATUS_CONFIRMED : EventBooking::STATUS_PENDING,
                'payment_status' => ($totalAmount <= 0) ? EventBooking::PAYMENT_PAID : EventBooking::PAYMENT_PENDING,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'seats' => $seats,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'currency' => $event->currency ?? 'ETB',
                'contact_method' => $data['contact_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booked_at' => now(),
            ]);

            // Handle Free Event
            if ($totalAmount <= 0) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Free event reservation confirmed successfully!',
                    'booking' => $booking,
                ]);
            }

            // Create pending payment for paid event
            $txRef = 'CHAPA_EVENT_API_' . $booking->id . '_' . time();
            $payment = \App\Models\Payment::create([
                'user_id' => $user ? $user->id : null,
                'event_booking_id' => $booking->id,
                'payment_method' => \App\Models\Payment::PAYMENT_METHOD_CHAPA,
                'amount' => $totalAmount,
                'status' => \App\Models\Payment::STATUS_PENDING,
                'chapa_tx_ref' => $txRef,
            ]);

            $booking->update(['payment_id' => $payment->id]);

            $chapaSession = $this->createChapaSession($totalAmount, $booking, $event, $txRef);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Event reservation created. Complete payment via Chapa to confirm.',
                'payment_required' => true,
                'checkout_url' => $chapaSession['checkout_url'],
                'tx_ref' => $txRef,
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while processing your booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a Chapa checkout session for API event booking.
     */
    private function createChapaSession(float $amount, EventBooking $booking, Event $event, string $txRef): array
    {
        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $credentials = $gatewayService->credentialsFor('chapa');
        $secretKey = $credentials['secret_key'] ?? env('CHAPA_SECRET_KEY');

        if (empty($secretKey)) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $rawDesc = 'Event ' . ($event->title ?? 'Admission');
        $customDesc = substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', $rawDesc), 0, 50);
        if (empty($customDesc)) {
            $customDesc = 'Event Pass';
        }

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $event->currency ?? 'ETB',
            'email' => $booking->email,
            'first_name' => $booking->first_name ?: 'Guest',
            'last_name' => $booking->last_name ?: 'User',
            'phone_number' => $booking->phone ?? '',
            'tx_ref' => $txRef,
            'callback_url' => url('/events/payment/chapa/callback'),
            'return_url' => url('/events/payment/chapa/callback?tx_ref=' . $txRef),
            'customization' => [
                'title' => substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', config('app.name', 'Fira Link')), 0, 16),
                'description' => $customDesc,
            ],
        ];

        $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('Chapa connection error: ' . $curlError);
        }

        $responseData = json_decode($response, true);
        if (!$responseData || ($responseData['status'] ?? '') !== 'success') {
            $msg = $responseData['message'] ?? 'Unable to initialize Chapa payment.';
            if (is_array($msg)) {
                $flattened = [];
                array_walk_recursive($msg, function ($val) use (&$flattened) {
                    $flattened[] = $val;
                });
                $msg = implode(' ', $flattened);
            }
            throw new \Exception('Chapa session error: ' . $msg);
        }

        $checkoutUrl = $responseData['data']['checkout_url'] ?? $responseData['checkout_url'] ?? null;
        if (!$checkoutUrl) {
            throw new \Exception('Chapa did not return a valid checkout URL.');
        }

        return [
            'checkout_url' => $checkoutUrl,
            'tx_ref' => $txRef,
        ];
    }

    /**
     * Verify a Chapa event payment for mobile / API client.
     */
    public function chapaVerify(Request $request)
    {
        $request->validate([
            'tx_ref' => 'required|string',
            'booking_id' => 'required|integer',
        ]);

        $booking = EventBooking::with(['event', 'user'])->find($request->booking_id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Event booking not found.',
            ], 404);
        }

        $payment = \App\Models\Payment::where('chapa_tx_ref', $request->tx_ref)
            ->where('event_booking_id', $booking->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Event booking payment record not found.',
            ], 400);
        }

        if ($payment->status === \App\Models\Payment::STATUS_COMPLETED && $booking->status === EventBooking::STATUS_CONFIRMED) {
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified.',
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
            ]);
        }

        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $credentials = $gatewayService->credentialsFor('chapa');
        $secretKey = $credentials['secret_key'] ?? env('CHAPA_SECRET_KEY');

        if (empty($secretKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Chapa credentials are not configured.',
            ], 500);
        }

        try {
            $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $request->tx_ref);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $secretKey,
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception('Chapa verify cURL error: ' . $curlError);
            }

            $verifyData = json_decode($response, true) ?? [];
            $verifiedStatus = strtolower(
                $verifyData['data']['status'] ?? $verifyData['status'] ?? ''
            );
            $reference = $verifyData['data']['reference'] ?? $verifyData['reference'] ?? $request->tx_ref;

            if ($verifiedStatus === 'success') {
                DB::beginTransaction();
                try {
                    $payment->update([
                        'status' => \App\Models\Payment::STATUS_COMPLETED,
                        'chapa_reference' => $reference,
                        'transaction_id' => $reference,
                    ]);

                    $booking->update([
                        'status' => EventBooking::STATUS_CONFIRMED,
                        'payment_status' => EventBooking::PAYMENT_PAID,
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment successful! Your event ticket is confirmed.',
                        'booking_id' => $booking->id,
                        'reference' => $booking->reference,
                        'status' => $booking->status,
                        'payment_status' => $booking->payment_status,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error confirming booking: ' . $e->getMessage(),
                    ], 500);
                }
            }

            $payment->update(['status' => \App\Models\Payment::STATUS_FAILED]);

            return response()->json([
                'success' => false,
                'message' => 'Chapa payment status: ' . ($verifiedStatus ?: 'unsuccessful'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chapa verification error: ' . $e->getMessage(),
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
