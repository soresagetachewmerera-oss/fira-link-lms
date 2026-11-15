<?php

namespace App\Http\Controllers\Api;

use App\Services\Booking\BookingPaymentService;
use App\Services\Verification\VerificationException;
use App\Support\EventTourPresenter;

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

        if ($request->filled('region')) {
            $query->where('destination', 'like', '%' . $request->query('region') . '%');
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
            ] + EventTourPresenter::tourFields($tour);
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
        ] + EventTourPresenter::tourFields($tour));
    }

    /**
     * POST /tours/{id}/book (signed in). The date must be one of the tour's next dates with enough
     * places. A deposit holds the places; the balance is paid in the app before its due date.
     */
    public function book(Request $request, $id, BookingPaymentService $payments): JsonResponse
    {
        $tour = is_numeric($id)
            ? Tour::published()->findOrFail($id)
            : Tour::published()->where('slug', $id)->firstOrFail();
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'required|string|max:30',
            'participants' => 'required|integer|min:1|max:20',
            'schedule_date' => 'required|date',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|in:chapa,bank_transfer,free',
            'slip' => 'nullable|file|max:10240',
            'bank_reference' => 'nullable|string|max:120',
        ]);

        $date = \Carbon\Carbon::parse($validated['schedule_date'])->toDateString();
        if (!in_array($date, EventTourPresenter::tourDates($tour), true)) {
            return response()->json(['success' => false, 'code' => 'DATE_NOT_OFFERED', 'message' => __('bookings.errors.date_not_offered')], 422);
        }
        $guests = (int) $validated['participants'];
        $left = EventTourPresenter::placesLeft($tour, $date);
        if ($left < $guests) {
            return response()->json(['success' => false, 'code' => 'SEATS_LEFT', 'message' => __('bookings.errors.places_left', ['n' => $left])], 422);
        }

        $split = EventTourPresenter::tourSplit($tour, $date, $guests);
        $method = $split['total'] > 0 ? ($validated['payment_method'] ?? 'chapa') : 'free';
        if ($split['total'] > 0 && !in_array($method, ['chapa', 'bank_transfer'], true)) {
            return response()->json(['success' => false, 'code' => 'PAYMENT_METHOD_INVALID', 'message' => __('bookings.errors.payment_method')], 422);
        }

        DB::beginTransaction();
        try {
            $booking = new TourBooking();
            $booking->forceFill([
                'tour_id' => $tour->id,
                'user_id' => $user->id,
                'reference' => TourBooking::generateReference(),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'] ?? $user->email,
                'phone' => $validated['phone'],
                'participants' => $guests,
                'schedule_date' => $date,
                'departure_time' => $tour->departure_time,
                'unit_price' => (float) $tour->price,
                'total_amount' => $split['total'],
                'deposit_amount' => $split['deposit'],
                'balance_amount' => $split['balance'],
                'balance_due_date' => $split['balance_due'],
                'currency' => $tour->currency ?? 'ETB',
                'status' => $split['total'] <= 0 ? TourBooking::STATUS_CONFIRMED : TourBooking::STATUS_PENDING,
                'payment_status' => $split['total'] <= 0 ? TourBooking::PAYMENT_PAID : TourBooking::PAYMENT_PENDING,
                'payment_method' => $method,
                'special_requests' => $validated['special_requests'] ?? null,
                'booked_at' => now(),
            ])->save();

            if ($split['total'] <= 0) {
                $booking->forceFill(['deposit_paid_at' => now(), 'balance_paid_at' => now()])->save();
                DB::commit();

                return response()->json(['success' => true, 'message' => __('bookings.free_confirmed'), 'booking' => $this->bookingJson($booking, $tour)]);
            }

            $started = $payments->start($booking, BookingPaymentService::TOUR_DEPOSIT, $split['deposit'], $method, $request->file('slip'), $validated['bank_reference'] ?? null, $user, 'Tour ' . $tour->title);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $method === 'bank_transfer' ? __('bookings.slip_in_review') : __('bookings.chapa_next'),
                'payment_required' => $method === 'chapa',
                'checkout_url' => $started['checkout_url'],
                'tx_ref' => $started['tx_ref'],
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'booking' => $this->bookingJson($booking->fresh(), $tour),
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

    /** POST /user/tour-bookings/{id}/pay-balance: the balance, in the app, before its due date. */
    public function payBalance(Request $request, $id, BookingPaymentService $payments): JsonResponse
    {
        $booking = TourBooking::with('tour')->findOrFail($id);
        if ($request->user()->cannot('payBalance', $booking)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('bookings.errors.balance_not_due')], 403);
        }
        $request->validate([
            'payment_method' => 'required|string|in:chapa,bank_transfer',
            'slip' => 'nullable|file|max:10240',
            'bank_reference' => 'nullable|string|max:120',
        ]);
        try {
            $started = DB::transaction(fn () => $payments->start(
                $booking, BookingPaymentService::TOUR_BALANCE, (float) $booking->balance_amount, $request->input('payment_method'),
                $request->file('slip'), $request->input('bank_reference'), $request->user(), 'Tour balance ' . ($booking->tour?->title ?? '')
            ));
        } catch (VerificationException $e) {
            return $e->toResponse();
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => __('bookings.errors.failed')], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $request->input('payment_method') === 'bank_transfer' ? __('bookings.slip_in_review') : __('bookings.chapa_next'),
            'payment_required' => $request->input('payment_method') === 'chapa',
            'checkout_url' => $started['checkout_url'],
            'tx_ref' => $started['tx_ref'],
            'booking' => $this->bookingJson($booking->fresh(), $booking->tour),
        ]);
    }

    private function bookingJson(TourBooking $b, ?Tour $tour): array
    {
        return [
            'id' => $b->id,
            'reference' => $b->reference,
            'tour_title' => $tour?->title,
            'participants' => $b->participants,
            'schedule_date' => optional($b->schedule_date)->toDateString(),
            'total_amount' => (float) $b->total_amount,
            'currency' => $b->currency,
            'status' => $b->status,
            'payment_status' => $b->payment_status,
        ] + EventTourPresenter::tourBookingPayment($b);
    }

    /** Verify a Chapa tour payment (deposit or balance), signed-in owner only, server to Chapa. */
    public function chapaVerify(Request $request, BookingPaymentService $payments)
    {
        $request->validate(['tx_ref' => 'required|string', 'booking_id' => 'required|integer']);
        $booking = TourBooking::find($request->integer('booking_id'));
        if (!$booking || (int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => __('bookings.errors.not_found')], 404);
        }
        $payment = Payment::where('chapa_tx_ref', $request->input('tx_ref'))->where('tour_booking_id', $booking->id)->first();
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
            'message' => $ok ? __('bookings.tour_confirmed') : __('bookings.errors.not_paid'),
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
        ], $ok ? 200 : 400);
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
            ] + EventTourPresenter::tourBookingPayment($booking);
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
            'special_requests' => $booking->special_requests,
            'schedule_date_iso' => optional($booking->schedule_date)->toDateString(),
            'host' => $tour ? EventTourPresenter::host($tour) : null,
        ] + EventTourPresenter::tourBookingPayment($booking);

        $qrPayload = json_encode([
            'reference' => $booking->reference,
            'tour' => $tour?->title,
            'passenger' => $booking->full_name,
            'participants' => $booking->participants,
            'date' => optional($booking->schedule_date)->format('Y-m-d'),
            'status' => $booking->status,
        ], JSON_UNESCAPED_SLASHES);

        $qrCodeDataUri = $booking->status === TourBooking::STATUS_CONFIRMED ? $this->generateQrSvg($qrPayload) : null;

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
