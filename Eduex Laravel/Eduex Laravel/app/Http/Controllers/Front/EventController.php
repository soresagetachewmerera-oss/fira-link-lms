<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventBookingRequest;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

        return view('events.book', [
            'pageTitle' => 'Book: ' . $event->title,
            'metaDescription' => Str::limit(strip_tags((string) ($event->summary ?? $event->description ?? '')), 160),
            'event' => $event,
        ]);
    }

    public function storeBooking(StoreEventBookingRequest $request, string $slug): RedirectResponse
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

        $booking = $event->bookings()->create([
            'user_id' => $request->user()->id,
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

        return redirect()
            ->route('student.events.bookings')
            ->with('success', 'Your booking has been received! Reference: ' . $booking->reference);
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

