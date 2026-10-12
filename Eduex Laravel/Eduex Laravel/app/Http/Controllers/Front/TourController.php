<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TourController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tour::query()->published();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->input('difficulty'));
        }

        if ($request->filled('destination')) {
            $query->where('destination', 'like', '%' . $request->input('destination') . '%');
        }

        $sort = $request->input('sort', 'upcoming');
        if ($sort === 'price_low') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_high') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'duration') {
            $query->orderBy('duration_days', 'asc');
        } else {
            $query->orderByRaw('CASE WHEN start_date IS NOT NULL THEN start_date ELSE created_at END asc');
        }

        $tours = $query->paginate(9)->withQueryString();

        $categories = Tour::published()
            ->whereNotNull('category')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        $destinations = Tour::published()
            ->select('destination')
            ->distinct()
            ->pluck('destination');

        return view('tours.index', [
            'pageTitle' => 'Exclusive Tours & Field Expeditions | Fira Link',
            'tours' => $tours,
            'categories' => $categories,
            'destinations' => $destinations,
        ]);
    }

    public function show(string $slug): View
    {
        $tour = Tour::published()->where('slug', $slug)->firstOrFail();
        
        $relatedTours = Tour::published()
            ->where('id', '!=', $tour->id)
            ->where(function ($q) use ($tour) {
                $q->where('category', $tour->category)
                  ->orWhere('destination', $tour->destination);
            })
            ->take(3)
            ->get();

        if ($relatedTours->isEmpty()) {
            $relatedTours = Tour::published()
                ->where('id', '!=', $tour->id)
                ->take(3)
                ->get();
        }

        return view('tours.show', [
            'pageTitle' => $tour->title . ' | Fira Link Tours',
            'tour' => $tour,
            'relatedTours' => $relatedTours,
            'remainingSeats' => $tour->remainingSeats(),
        ]);
    }

    public function book(Request $request, string $slug): View
    {
        $tour = Tour::published()->where('slug', $slug)->firstOrFail();
        $remainingSeats = $tour->remainingSeats();

        if ($remainingSeats <= 0) {
            return redirect()->route('tours.show', $tour->slug)
                ->with('error', 'Sorry, this tour is currently fully booked.');
        }

        $gatewayService = app(PaymentGatewayService::class);
        $chapaEnabled = $gatewayService->isEnabled('chapa');
        $offlineEnabled = $gatewayService->isEnabled('offline');

        return view('tours.book', [
            'pageTitle' => 'Book Tour: ' . $tour->title,
            'tour' => $tour,
            'remainingSeats' => $remainingSeats,
            'chapaEnabled' => $chapaEnabled,
            'offlineEnabled' => $offlineEnabled,
            'user' => Auth::user(),
        ]);
    }

    public function processBooking(Request $request, string $slug)
    {
        $tour = Tour::published()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:30',
            'participants' => 'required|integer|min:1|max:20',
            'schedule_date' => 'nullable|date',
            'departure_time' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|in:chapa,free',
        ]);

        $participants = (int)$validated['participants'];
        $remainingSeats = $tour->remainingSeats();

        if ($remainingSeats < $participants) {
            return back()->withInput()->with('error', "Only {$remainingSeats} seat(s) are remaining for this tour.");
        }

        $unitPrice = (float)$tour->price;
        $totalAmount = $unitPrice * $participants;
        $reference = TourBooking::generateReference();
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $booking = TourBooking::create([
                'tour_id' => $tour->id,
                'user_id' => $user?->id,
                'reference' => $reference,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'participants' => $participants,
                'schedule_date' => $validated['schedule_date'] ?? $tour->start_date,
                'departure_time' => $validated['departure_time'] ?? $tour->departure_time,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'currency' => 'ETB',
                'status' => ($totalAmount <= 0) ? TourBooking::STATUS_CONFIRMED : TourBooking::STATUS_PENDING,
                'payment_status' => ($totalAmount <= 0) ? TourBooking::PAYMENT_PAID : TourBooking::PAYMENT_PENDING,
                'special_requests' => $validated['special_requests'] ?? null,
                'booked_at' => now(),
            ]);

            // Handle Free Tour
            if ($totalAmount <= 0) {
                DB::commit();
                return redirect()->route('tours.confirmation', ['reference' => $booking->reference])
                    ->with('success', 'Your tour booking has been confirmed successfully!');
            }

            // Handle Chapa Gateway Payment
            if ($validated['payment_method'] === 'chapa') {
                $txRef = 'CHAPA_TOUR_' . $booking->id . '_' . time();

                $payment = Payment::create([
                    'user_id' => $user?->id,
                    'tour_booking_id' => $booking->id,
                    'payment_method' => Payment::PAYMENT_METHOD_CHAPA,
                    'amount' => $totalAmount,
                    'status' => Payment::STATUS_PENDING,
                    'chapa_tx_ref' => $txRef,
                ]);

                $booking->update(['payment_id' => $payment->id]);

                // Create Chapa Session
                $chapaSession = $this->createChapaSession($totalAmount, $booking, $tour, $txRef);
                DB::commit();

                if (!empty($chapaSession['checkout_url'])) {
                    return redirect()->away($chapaSession['checkout_url']);
                }

                throw new \Exception('Chapa did not return a valid checkout URL.');
            }

            DB::commit();
            return redirect()->route('tours.confirmation', ['reference' => $booking->reference]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Tour booking error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Booking processing error: ' . $e->getMessage());
        }
    }

    private function createChapaSession(float $amount, TourBooking $booking, Tour $tour, string $txRef): array
    {
        $gatewayService = app(PaymentGatewayService::class);
        $credentials = $gatewayService->credentialsFor('chapa');
        $secretKey = $credentials['secret_key'] ?? env('CHAPA_SECRET_KEY');

        if (empty($secretKey)) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $tour->currency ?? 'ETB',
            'email' => $booking->email,
            'first_name' => $booking->first_name,
            'last_name' => $booking->last_name,
            'tx_ref' => $txRef,
            'callback_url' => route('tours.chapa.callback'),
            'return_url' => route('tours.chapa.callback', ['tx_ref' => $txRef]),
            'customization' => [
                'title' => 'Fira Link Tour Booking',
                'description' => 'Tour: ' . Str::limit($tour->title, 40),
            ],
        ];

        $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
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
            $msg = $responseData['message'] ?? 'Unable to connect to Chapa checkout.';
            throw new \Exception('Chapa initialization error: ' . $msg);
        }

        return [
            'checkout_url' => $responseData['data']['checkout_url'] ?? null,
            'tx_ref' => $txRef,
        ];
    }

    public function chapaCallback(Request $request): RedirectResponse
    {
        $txRef = $request->query('tx_ref') ?? $request->input('trx_ref') ?? $request->input('tx_ref');

        if (!$txRef) {
            return redirect()->route('tours.index')->with('error', 'Chapa payment callback missing transaction reference.');
        }

        $payment = Payment::where('chapa_tx_ref', $txRef)->first();
        if (!$payment || !$payment->tour_booking_id) {
            return redirect()->route('tours.index')->with('error', 'Tour booking payment record not found.');
        }

        $booking = TourBooking::with('tour')->find($payment->tour_booking_id);
        if (!$booking) {
            return redirect()->route('tours.index')->with('error', 'Booking record not found.');
        }

        // Verify with Chapa API
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
                    'status' => TourBooking::STATUS_CONFIRMED,
                    'payment_status' => TourBooking::PAYMENT_PAID,
                ]);

                return redirect()->route('tours.confirmation', ['reference' => $booking->reference])
                    ->with('success', 'Payment verified and your tour booking is confirmed!');
            }

            $payment->update(['status' => Payment::STATUS_FAILED]);
            $booking->update(['payment_status' => TourBooking::PAYMENT_FAILED]);

            return redirect()->route('tours.show', $booking->tour->slug)
                ->with('error', 'Chapa payment was not successful (Status: ' . ($status ?? 'failed') . ').');
        } catch (\Throwable $e) {
            Log::error('Chapa verification exception: ' . $e->getMessage());
            return redirect()->route('tours.confirmation', ['reference' => $booking->reference])
                ->with('warning', 'Booking saved. Payment verification is pending.');
        }
    }

    public function confirmation(string $reference): View
    {
        $booking = TourBooking::with(['tour', 'payment', 'user'])
            ->where('reference', $reference)
            ->firstOrFail();

        $qrPayload = json_encode([
            'reference' => $booking->reference,
            'tour' => $booking->tour->title,
            'passenger' => $booking->full_name,
            'participants' => $booking->participants,
            'date' => optional($booking->schedule_date)->format('Y-m-d'),
            'status' => $booking->status,
        ], JSON_UNESCAPED_SLASHES);

        $qrCodeDataUri = $this->generateQrSvg($qrPayload);

        return view('tours.confirmation', [
            'pageTitle' => 'Booking Confirmation - ' . $booking->reference,
            'booking' => $booking,
            'qrCode' => $qrCodeDataUri,
        ]);
    }

    public function myBookings(Request $request): View
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $bookings = TourBooking::with(['tour', 'payment'])
            ->where('user_id', $user->id)
            ->latest('booked_at')
            ->paginate(10);

        return view('student.tours.index', [
            'pageTitle' => 'My Tour Bookings | Fira Link',
            'bookings' => $bookings,
        ]);
    }

    public function cancelBooking(Request $request, string $reference): RedirectResponse
    {
        $user = Auth::user();
        $booking = TourBooking::where('reference', $reference)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->status === TourBooking::STATUS_CANCELLED) {
            return back()->with('info', 'This booking has already been cancelled.');
        }

        $booking->update([
            'status' => TourBooking::STATUS_CANCELLED,
            'notes' => 'Cancelled by customer on ' . now()->toDateTimeString(),
        ]);

        return back()->with('success', 'Your booking has been cancelled and seats have been released.');
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
