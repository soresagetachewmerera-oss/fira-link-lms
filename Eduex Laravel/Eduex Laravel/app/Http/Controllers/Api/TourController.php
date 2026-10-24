<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TourController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tour::query()->published();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->query('difficulty'));
        }

        $sort = $request->query('sort', 'upcoming');
        if ($sort === 'price_low') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_high') {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderByRaw('CASE WHEN start_date IS NOT NULL THEN start_date ELSE created_at END asc');
        }

        $tours = $query->paginate($request->query('per_page', 10));

        $tours->getCollection()->transform(function (Tour $tour) {
            return [
                'id' => $tour->id,
                'title' => $tour->title,
                'slug' => $tour->slug,
                'summary' => $tour->summary,
                'description' => $tour->description,
                'destination' => $tour->destination,
                'meeting_point' => $tour->meeting_point,
                'duration_days' => $tour->duration_days,
                'duration_hours' => $tour->duration_hours,
                'start_date' => optional($tour->start_date)->toDateString(),
                'end_date' => optional($tour->end_date)->toDateString(),
                'departure_time' => $tour->departure_time,
                'total_seats' => $tour->total_seats,
                'remaining_seats' => $tour->remainingSeats(),
                'price' => (float)$tour->price,
                'currency' => $tour->currency ?? 'ETB',
                'category' => $tour->category,
                'difficulty_level' => $tour->difficulty_level,
                'featured_image' => $tour->featured_image_url,
                'gallery' => $tour->gallery ?? [],
                'is_featured' => $tour->is_featured,
                'metadata' => $tour->metadata,
                'video_url' => $tour->video_url,
            ];
        });

        return response()->json($tours);
    }

    public function show($id): JsonResponse
    {
        $tour = is_numeric($id) 
            ? Tour::published()->findOrFail($id)
            : Tour::published()->where('slug', $id)->firstOrFail();

        return response()->json([
            'id' => $tour->id,
            'title' => $tour->title,
            'slug' => $tour->slug,
            'summary' => $tour->summary,
            'description' => $tour->description,
            'destination' => $tour->destination,
            'meeting_point' => $tour->meeting_point,
            'duration_days' => $tour->duration_days,
            'duration_hours' => $tour->duration_hours,
            'start_date' => optional($tour->start_date)->toDateString(),
            'end_date' => optional($tour->end_date)->toDateString(),
            'departure_time' => $tour->departure_time,
            'total_seats' => $tour->total_seats,
            'remaining_seats' => $tour->remainingSeats(),
            'price' => (float)$tour->price,
            'currency' => $tour->currency ?? 'ETB',
            'category' => $tour->category,
            'difficulty_level' => $tour->difficulty_level,
            'featured_image' => $tour->featured_image_url,
            'gallery' => $tour->gallery ?? [],
            'itinerary' => $tour->itinerary ?? [],
            'included_services' => $tour->included_services ?? [],
            'exclusions' => $tour->exclusions ?? [],
            'requirements' => $tour->requirements ?? [],
            'cancellation_policy' => $tour->cancellation_policy,
            'available_schedules' => $tour->available_schedules ?? [],
            'is_featured' => $tour->is_featured,
            'metadata' => $tour->metadata,
            'video_url' => $tour->video_url,
        ]);
    }

    public function book(Request $request, $id): JsonResponse
    {
        $tour = is_numeric($id)
            ? Tour::published()->findOrFail($id)
            : Tour::published()->where('slug', $id)->firstOrFail();

        $user = $request->user('sanctum') ?? $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'participants' => 'nullable|integer|min:1|max:20',
            'schedule_date' => 'nullable|date',
            'departure_time' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|in:chapa,free',
        ]);

        $participants = (int)($validated['participants'] ?? 1);
        $remainingSeats = $tour->remainingSeats();

        if ($remainingSeats < $participants) {
            return response()->json([
                'success' => false,
                'message' => "Only {$remainingSeats} seat(s) remaining for this tour.",
            ], 422);
        }

        $unitPrice = (float)$tour->price;
        $totalAmount = $unitPrice * $participants;
        $paymentMethod = $validated['payment_method'] ?? ($totalAmount > 0 ? 'chapa' : 'free');

        // Strictly prevent booking paid tour as free
        if ($totalAmount > 0 && $paymentMethod === 'free') {
            return response()->json([
                'success' => false,
                'message' => 'This tour requires payment of ' . number_format($totalAmount, 2) . ' ' . ($tour->currency ?? 'ETB') . '. Free reservations are not permitted for paid expeditions.',
            ], 422);
        }

        if ($totalAmount > 0 && $paymentMethod !== 'chapa') {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid payment method (e.g. Chapa) to book this paid expedition.',
            ], 422);
        }

        $reference = TourBooking::generateReference();

        DB::beginTransaction();
        try {
            $booking = TourBooking::create([
                'tour_id' => $tour->id,
                'user_id' => $user ? $user->id : null,
                'reference' => $reference,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? ($user ? $user->phone : null),
                'participants' => $participants,
                'schedule_date' => $validated['schedule_date'] ?? $tour->start_date,
                'departure_time' => $validated['departure_time'] ?? $tour->departure_time,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'currency' => $tour->currency ?? 'ETB',
                'status' => ($totalAmount <= 0) ? TourBooking::STATUS_CONFIRMED : TourBooking::STATUS_PENDING,
                'payment_status' => ($totalAmount <= 0) ? TourBooking::PAYMENT_PAID : TourBooking::PAYMENT_PENDING,
                'special_requests' => $validated['special_requests'] ?? null,
                'booked_at' => now(),
            ]);

            // Handle Free Tour
            if ($totalAmount <= 0) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Free tour booking confirmed successfully!',
                    'booking' => [
                        'id' => $booking->id,
                        'reference' => $booking->reference,
                        'tour_title' => $tour->title,
                        'participants' => $booking->participants,
                        'total_amount' => $booking->total_amount,
                        'currency' => $booking->currency,
                        'status' => $booking->status,
                        'payment_status' => $booking->payment_status,
                    ],
                ]);
            }

            // Create pending payment record for paid tour
            $txRef = 'CHAPA_TOUR_API_' . $booking->id . '_' . time();
            $payment = Payment::create([
                'user_id' => $user?->id,
                'tour_booking_id' => $booking->id,
                'payment_method' => Payment::PAYMENT_METHOD_CHAPA,
                'amount' => $totalAmount,
                'status' => Payment::STATUS_PENDING,
                'chapa_tx_ref' => $txRef,
            ]);

            $booking->update(['payment_id' => $payment->id]);

            $chapaSession = $this->createChapaSession($totalAmount, $booking, $tour, $txRef);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tour reservation created. Complete payment via Chapa to confirm.',
                'payment_required' => true,
                'checkout_url' => $chapaSession['checkout_url'],
                'tx_ref' => $txRef,
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'tour_title' => $tour->title,
                    'participants' => $booking->participants,
                    'total_amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Booking processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a Chapa checkout session for API tour booking.
     */
    private function createChapaSession(float $amount, TourBooking $booking, Tour $tour, string $txRef): array
    {
        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $credentials = $gatewayService->credentialsFor('chapa');
        $secretKey = $credentials['secret_key'] ?? env('CHAPA_SECRET_KEY');

        if (empty($secretKey)) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $rawDesc = 'Tour ' . ($tour->title ?? 'Booking');
        $customDesc = substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', $rawDesc), 0, 50);
        if (empty($customDesc)) {
            $customDesc = 'Tour Booking';
        }

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $tour->currency ?? 'ETB',
            'email' => $booking->email,
            'first_name' => $booking->first_name ?: 'Guest',
            'last_name' => $booking->last_name ?: 'User',
            'phone_number' => $booking->phone ?? '',
            'tx_ref' => $txRef,
            'callback_url' => url('/tours/payment/chapa/callback'),
            'return_url' => url('/tours/payment/chapa/callback?tx_ref=' . $txRef),
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
     * Verify a Chapa tour payment for mobile / API client.
     */
    public function chapaVerify(Request $request)
    {
        $request->validate([
            'tx_ref' => 'required|string',
            'booking_id' => 'required|integer',
        ]);

        $booking = TourBooking::with(['tour', 'user'])->find($request->booking_id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Tour booking not found.',
            ], 404);
        }

        $payment = Payment::where('chapa_tx_ref', $request->tx_ref)
            ->where('tour_booking_id', $booking->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Tour booking payment record not found.',
            ], 400);
        }

        if ($payment->status === Payment::STATUS_COMPLETED && $booking->status === TourBooking::STATUS_CONFIRMED) {
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
                        'status' => Payment::STATUS_COMPLETED,
                        'chapa_reference' => $reference,
                        'transaction_id' => $reference,
                    ]);

                    $booking->update([
                        'status' => TourBooking::STATUS_CONFIRMED,
                        'payment_status' => TourBooking::PAYMENT_PAID,
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment successful! Your tour booking is confirmed.',
                        'booking_id' => $booking->id,
                        'reference' => $booking->reference,
                        'status' => $booking->status,
                        'payment_status' => $booking->payment_status,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error confirming tour booking: ' . $e->getMessage(),
                    ], 500);
                }
            }

            $payment->update(['status' => Payment::STATUS_FAILED]);

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

    public function myBookings(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookings = TourBooking::with('tour')
            ->where('user_id', $user->id)
            ->latest('booked_at')
            ->paginate($request->query('per_page', 10));

        $transformed = $bookings->getCollection()->transform(function (TourBooking $booking) {
            return [
                'id' => $booking->id,
                'tour_id' => $booking->tour_id,
                'tour_title' => $booking->tour?->title ?? 'Tour Removed',
                'tour_slug' => $booking->tour?->slug,
                'destination' => $booking->tour?->destination,
                'featured_image' => $booking->tour?->featured_image_url,
                'booked_at' => optional($booking->booked_at)->toIso8601String(),
                'booked_at_formatted' => optional($booking->booked_at)->format('M d, Y H:i'),
                'schedule_date' => optional($booking->schedule_date)->toIso8601String(),
                'schedule_date_formatted' => optional($booking->schedule_date)->format('M d, Y'),
                'departure_time' => $booking->departure_time,
                'participants' => $booking->participants,
                'status' => Str::lower($booking->status),
                'payment_status' => Str::lower($booking->payment_status),
                'reference' => $booking->reference,
                'total_amount' => (float)$booking->total_amount,
                'currency' => $booking->currency,
            ];
        });

        $bookings->setCollection($transformed);

        return response()->json($bookings);
    }

    public function showBooking(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $booking = TourBooking::with(['tour', 'user', 'payment'])->findOrFail($id);

        if ((int)$booking->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tour = $booking->tour;
        $ticket = [
            'booking_id' => $booking->reference,
            'tour_name' => $tour?->title ?? 'Tour Expedition',
            'destination' => $tour?->destination ?? 'Ethiopia',
            'meeting_point' => $tour?->meeting_point ?? 'TBA',
            'schedule_date' => optional($booking->schedule_date)->format('F d, Y'),
            'departure_time' => $booking->departure_time ?? optional($tour?->start_date)->format('h:i A'),
            'participants' => $booking->participants,
            'total_price' => (float)$booking->total_amount,
            'currency' => $booking->currency,
            'status' => Str::lower($booking->status),
            'payment_status' => Str::lower($booking->payment_status),
            'passenger_name' => $booking->full_name,
            'passenger_email' => $booking->email,
            'passenger_phone' => $booking->phone,
        ];

        $qrPayload = json_encode([
            'reference' => $booking->reference,
            'tour' => $tour?->title,
            'passenger' => $booking->full_name,
            'participants' => $booking->participants,
            'date' => optional($booking->schedule_date)->format('Y-m-d'),
            'status' => $booking->status,
        ], JSON_UNESCAPED_SLASHES);

        $qrCodeDataUri = $this->generateQrSvg($qrPayload);

        return response()->json([
            'ticket' => $ticket,
            'qr_code' => $qrCodeDataUri,
        ]);
    }

    public function cancelBooking(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $booking = TourBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->status === TourBooking::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is already cancelled.',
            ], 422);
        }

        $booking->update([
            'status' => TourBooking::STATUS_CANCELLED,
            'notes' => 'Cancelled via mobile app on ' . now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully. Seats have been released.',
        ]);
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
}
