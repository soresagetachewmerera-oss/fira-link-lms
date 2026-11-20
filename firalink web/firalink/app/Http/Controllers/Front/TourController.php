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

    /**
     * Tour booking is in the Fira Link app (slice 8: signed in, host business, future dates, deposit).
     * The website no longer takes bookings: both the form and its POST send the visitor back to the tour.
     */
    public function bookInApp(string $slug): RedirectResponse
    {
        $tour = Tour::published()->where('slug', $slug)->firstOrFail();

        \Devrabiul\ToastMagic\Facades\ToastMagic::info(__('security.bookings.book_in_app'));

        return redirect()->route('tours.show', $tour->slug);
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

        // Slice 8: deposits and balances carry a purpose; one service checks the amount and applies the stage.
        if ($payment->purpose) {
            $ok = rescue(fn () => app(\App\Services\Booking\BookingPaymentService::class)->verifyChapa($payment), false);

            return redirect()->route('tours.confirmation', ['reference' => $booking->reference])
                ->with($ok ? 'success' : 'warning', $ok ? __('bookings.tour_confirmed') : __('bookings.errors.not_paid'));
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
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
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

    public function confirmation(Request $request, string $reference): View
    {
        $booking = TourBooking::with(['tour', 'payment', 'user'])
            ->where('reference', $reference)
            ->firstOrFail();
        // Names, contact details and payment: only the person who booked, or an admin (same 404 otherwise).
        $viewer = $request->user();
        abort_unless($viewer && ($viewer->isAdmin() || ($booking->user_id && (int) $booking->user_id === (int) $viewer->id)), 404);

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
