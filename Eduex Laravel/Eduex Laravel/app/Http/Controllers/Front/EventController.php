<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventBookingRequest;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Event Controller
 *
 * This controller handles the event functionality.
 *
 * @package App\Http\Controllers\Front
 */
class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::with(['speakers', 'highlights'])
            ->published()
            ->withCount([
                'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ])
            ->orderBy('start_date')
            ->paginate(9);

        $meta = frontend_meta('events', null, []);

        return view('events.index', [
            'pageTitle' => $meta['title'] ?? 'Events',
            'metaDescription' => $meta['description'] ?? null,
            'events' => $events,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $event = $this->findEventBySlug($request, $slug);
        $event->load(['highlights', 'speakers', 'instructors'])
            ->loadCount([
                'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ]);

        $countdownTarget = $this->countdownTarget($event);

        return view('events.show', [
            'pageTitle' => $event->title,
            'metaDescription' => Str::limit(strip_tags((string) ($event->summary ?? $event->description ?? '')), 160),
            'event' => $event,
            'countdownTarget' => $countdownTarget,
        ]);
    }

    public function book(Request $request, string $slug): View
    {
        $event = $this->findEventBySlug($request, $slug);
        $event->load(['speakers'])
            ->loadCount([
                'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ]);

        $gatewayService = app(PaymentGatewayService::class);
        $chapaEnabled = $gatewayService->isEnabled('chapa');

        return view('events.book', [
            'pageTitle' => 'Book: ' . $event->title,
            'metaDescription' => Str::limit(strip_tags((string) ($event->summary ?? $event->description ?? '')), 160),
            'event' => $event,
            'chapaEnabled' => $chapaEnabled,
        ]);
    }

    public function storeBooking(StoreEventBookingRequest $request, string $slug)
    {
        $event = $this->findEventBySlug($request, $slug);

        if (! $event->is_published) {
            abort(403, 'This event is not accepting bookings yet.');
        }

        $data = $request->validated();
        $seats = (int) ($data['seats'] ?? 1);

        if ($event->total_seats && $event->remainingSeats() < $seats) {
            return back()
                ->withInput()
                ->withErrors(['seats' => 'Only ' . $event->remainingSeats() . ' seat(s) remaining for this event.']);
        }

        $unitPrice = (float)($event->price ?? 0);
        $totalAmount = $unitPrice * $seats;
        $paymentMethod = $data['payment_method'] ?? ($totalAmount > 0 ? 'chapa' : 'free');

        // Strictly enforce payment: paid events CANNOT be booked as free
        if ($totalAmount > 0 && $paymentMethod === 'free') {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => 'This event requires a paid ticket (' . number_format($totalAmount, 2) . ' ' . $event->currency . '). You cannot book a paid event as free.']);
        }

        if ($totalAmount > 0 && $paymentMethod !== 'chapa') {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => 'Please select a valid payment method (Chapa) to secure your seats.']);
        }

        if ($totalAmount <= 0) {
            $paymentMethod = 'free';
        }

        $reference = $this->generateReference();
        $user = $request->user();

        DB::beginTransaction();
        try {
            $booking = $event->bookings()->create([
                'user_id' => $user->id,
                'reference' => $reference,
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

            // Free Event Flow
            if ($totalAmount <= 0) {
                DB::commit();
                return redirect()
                    ->route('events.confirmation', ['reference' => $booking->reference])
                    ->with('success', 'Your reservation has been confirmed successfully!');
            }

            // Paid Event Flow with Chapa Gateway
            if ($paymentMethod === 'chapa') {
                $txRef = 'CHAPA_EVENT_' . $booking->id . '_' . time();

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'event_booking_id' => $booking->id,
                    'payment_method' => Payment::PAYMENT_METHOD_CHAPA,
                    'amount' => $totalAmount,
                    'status' => Payment::STATUS_PENDING,
                    'chapa_tx_ref' => $txRef,
                ]);

                $booking->update(['payment_id' => $payment->id]);

                $chapaSession = $this->createChapaSession($totalAmount, $booking, $event, $txRef);
                DB::commit();

                if (!empty($chapaSession['checkout_url'])) {
                    return redirect()->away($chapaSession['checkout_url']);
                }

                throw new \Exception('Chapa did not return a valid checkout URL.');
            }

            throw new \Exception('Invalid payment method for paid event.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Event booking error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Booking error: ' . $e->getMessage());
        }
    }

    public function chapaCallback(Request $request)
    {
        $txRef = $request->query('tx_ref') ?? $request->input('tx_ref');

        if (!$txRef) {
            return redirect()->route('events.index')->with('error', 'Transaction reference missing.');
        }

        $payment = Payment::where('chapa_tx_ref', $txRef)->first();

        if (!$payment || !$payment->event_booking_id) {
            return redirect()->route('events.index')->with('error', 'Event booking payment record not found.');
        }

        $booking = EventBooking::with('event')->find($payment->event_booking_id);
        if (!$booking) {
            return redirect()->route('events.index')->with('error', 'Booking record not found.');
        }

        $gatewayService = app(PaymentGatewayService::class);
        $credentials = $gatewayService->credentialsFor('chapa');
        $secretKey = $credentials['secret_key'] ?? env('CHAPA_SECRET_KEY');

        try {
            $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $txRef);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $secretKey,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($response, true);

            $status = $data['data']['status'] ?? null;
            $reference = $data['data']['reference'] ?? $txRef;

            if (($data['status'] ?? '') === 'success' && strtolower((string)$status) === 'success') {
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'chapa_reference' => $reference,
                    'transaction_id' => $reference,
                ]);

                $booking->update([
                    'status' => EventBooking::STATUS_CONFIRMED,
                    'payment_status' => EventBooking::PAYMENT_PAID,
                ]);

                return redirect()->route('events.confirmation', ['reference' => $booking->reference])
                    ->with('success', 'Payment verified! Your event admission pass is confirmed.');
            }

            $payment->update(['status' => Payment::STATUS_FAILED]);
            $booking->update(['payment_status' => EventBooking::PAYMENT_FAILED]);

            return redirect()->route('events.show', $booking->event->slug)
                ->with('error', 'Payment was not successful (Status: ' . ($status ?? 'failed') . ').');
        } catch (\Throwable $e) {
            Log::error('Event Chapa verification exception: ' . $e->getMessage());
            return redirect()->route('events.confirmation', ['reference' => $booking->reference])
                ->with('warning', 'Booking saved. Payment verification is pending.');
        }
    }

    public function confirmation(string $reference): View
    {
        $booking = EventBooking::with(['event', 'payment', 'user'])
            ->where('reference', $reference)
            ->firstOrFail();

        $qrPayload = json_encode([
            'reference' => $booking->reference,
            'event' => $booking->event->title,
            'attendee' => $booking->full_name,
            'seats' => $booking->seats,
            'date' => optional($booking->event->start_date)->format('Y-m-d'),
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
        ], JSON_UNESCAPED_SLASHES);

        $qrCodeDataUri = $this->generateQrSvg($qrPayload);

        return view('events.confirmation', [
            'pageTitle' => 'Event Ticket Confirmation - ' . $booking->reference,
            'booking' => $booking,
            'qrCode' => $qrCodeDataUri,
        ]);
    }

    private function createChapaSession(float $amount, EventBooking $booking, Event $event, string $txRef): array
    {
        $gatewayService = app(PaymentGatewayService::class);
        $credentials = $gatewayService->credentialsFor('chapa');
        $secretKey = $credentials['secret_key'] ?? env('CHAPA_SECRET_KEY');

        if (empty($secretKey)) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $rawEventDesc = 'Event: ' . ($event->title ?? 'Booking');
        $customEventDesc = substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', $rawEventDesc), 0, 50);
        if (empty($customEventDesc)) {
            $customEventDesc = 'Event Ticket';
        }

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $event->currency ?? 'ETB',
            'email' => $booking->email,
            'first_name' => $booking->first_name,
            'last_name' => $booking->last_name,
            'tx_ref' => $txRef,
            'callback_url' => route('events.chapa.callback'),
            'return_url' => route('events.chapa.callback', ['tx_ref' => $txRef]),
            'customization' => [
                'title' => 'Fira Link Event Admission',
                'description' => $customEventDesc,
            ],
        ];

        $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('Chapa connection failed: ' . $err);
        }

        $res = json_decode($response, true);
        if (($res['status'] ?? '') !== 'success' || empty($res['data']['checkout_url'])) {
            throw new \RuntimeException($res['message'] ?? 'Failed to initialize payment gateway.');
        }

        return $res['data'];
    }

    private function generateQrSvg(string $payload): string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(240),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($payload);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function findEventBySlug(Request $request, string $slug): Event
    {
        $event = Event::with(['instructors'])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($event->is_published) {
            return $event;
        }

        $user = $request->user();
        if ($user && $user->instructedEvents()->whereKey($event->id)->exists()) {
            return $event;
        }

        abort(404);
    }

    private function generateReference(): string
    {
        do {
            $reference = 'EVT-' . Str::upper(Str::random(8));
        } while (EventBooking::where('reference', $reference)->exists());

        return $reference;
    }

    private function countdownTarget(Event $event): ?string
    {
        if (! $event->start_date) {
            return null;
        }

        try {
            $start = Carbon::parse($event->start_date);

            if ($event->start_time) {
                $timeString = $event->start_time instanceof Carbon
                    ? $event->start_time->format('H:i:s')
                    : (string) $event->start_time;

                $start->setTimeFromTimeString($timeString);
            }

            return $start->greaterThan(now()) ? $start->toIso8601String() : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}

