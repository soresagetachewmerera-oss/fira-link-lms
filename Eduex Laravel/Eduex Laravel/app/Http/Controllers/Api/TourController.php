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
            'payment_method' => 'nullable|string|in:chapa,offline,free,card',
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tour booking registered successfully!',
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
