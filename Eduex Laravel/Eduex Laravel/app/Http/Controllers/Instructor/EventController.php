<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Event Controller
 *
 * This controller handles the event functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class EventController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $events = $user->instructedEvents()
            ->withCount([
                'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', EventBooking::STATUS_CONFIRMED),
                'bookings',
            ])
            ->latest()
            ->paginate(10)
            ->through(function (Event $event) {
                $image = $event->featured_image
                    ? asset('storage/' . $event->featured_image)
                    : asset('assets/front/img/inner/event/01.jpg');

                $summarySource = $event->summary ?? $event->description ?? '';

                return [
                    'id' => $event->id,
                    'slug' => $event->slug,
                    'title' => $event->title,
                    'summary' => Str::limit(strip_tags($summarySource), 120),
                    'image_url' => $image,
                    'start_date_badge' => optional($event->start_date)->format('M d, Y') ?? 'Schedule TBD',
                    'start_time_label' => $event->start_time ? Carbon::parse($event->start_time)->format('h:i A') : 'Start TBD',
                    'seats_label' => $event->total_seats ?? 'Unlimited',
                    'bookings_total' => $event->bookings_count,
                    'bookings_confirmed' => $event->confirmed_bookings_count ?? 0,
                    'remaining_label' => $event->total_seats ? max($event->remainingSeats() ?? 0, 0) : '∞',
                    'status_badge' => $event->is_published ? 'Published' : 'Draft',
                    'status_chip_label' => $event->is_published ? 'Live' : 'Draft',
                    'status_chip_class' => $event->is_published ? 'text-success' : 'text-warning',
                    'builder_url' => route('instructor.events.basic.edit', $event),
                    'bookings_url' => route('instructor.events.bookings', $event),
                ];
            });

        return view('instructor.events.index', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    public function bookings(Request $request, Event $event): View
    {
        $this->authorize('viewBookings', $event);
        $user = $request->user();

        $filters = $this->extractBookingFilters($request);
        $bookingsQuery = $this->buildBookingQuery($event, $filters);

        $bookings = (clone $bookingsQuery)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (EventBooking $booking) => $this->transformBookingForTable($event, $booking));

        $statusOptions = $this->bookingStatusOptions();
        $emailRecipientCounts = $this->bookingEmailRecipientCounts($event);
        $emailStatusOptions = $this->bookingEmailStatusOptions($statusOptions, $emailRecipientCounts);
        $defaultEmailSubject = 'Update for ' . $event->title;
        $defaultEmailBody = "Hi there,\n\nWe wanted to share a quick update about {$event->title}.";

        $exportUrl = route('instructor.events.bookings.export', array_merge([
            'event' => $event->id,
        ], array_filter([
            'status' => $filters['status'],
            'search' => $filters['search'],
        ], fn ($value) => filled($value))));

        return view('instructor.events.bookings', [
            'event' => $event,
            'bookings' => $bookings,
            'user' => $user,
            'statusOptions' => $statusOptions,
            'filterStatusOptions' => ['' => 'All statuses'] + $statusOptions,
            'statusFilter' => $filters['status'] ?? '',
            'searchTerm' => $filters['search'],
            'exportUrl' => $exportUrl,
            'emailRecipientCounts' => $emailRecipientCounts,
            'emailStatusOptions' => $emailStatusOptions,
            'defaultEmailSubject' => $defaultEmailSubject,
            'defaultEmailBody' => $defaultEmailBody,
        ]);
    }

    public function updateBookingStatus(Request $request, Event $event, EventBooking $booking): RedirectResponse
    {
        $this->authorize('viewBookings', $event);

        abort_unless($booking->event_id === $event->id, 404);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    EventBooking::STATUS_PENDING,
                    EventBooking::STATUS_CONFIRMED,
                    EventBooking::STATUS_CANCELLED,
                ]),
            ],
        ]);

        $booking->status = $data['status'];
        $booking->save();

        return back()->with('success', 'Booking status updated.');
    }

    public function exportBookings(Request $request, Event $event)
    {
        $this->authorize('viewBookings', $event);

        $filters = $this->extractBookingFilters($request);
        $bookingsQuery = $this->buildBookingQuery($event, $filters);

        $filename = 'event-bookings-' . $event->slug . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($bookingsQuery) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Attendee', 'Email', 'Phone', 'Seats', 'Status', 'Booked At', 'Reference']);

            foreach ((clone $bookingsQuery)->cursor() as $booking) {
                /** @var EventBooking $booking */
                fputcsv($handle, [
                    $booking->full_name,
                    $booking->user?->email ?? $booking->email,
                    $booking->phone,
                    $booking->seats,
                    ucfirst($booking->status),
                    optional($booking->booked_at)->format('M d, Y H:i'),
                    $booking->reference,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function extractBookingFilters(Request $request): array
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('search'));

        if (! in_array($status, array_keys($this->bookingStatusOptions()), true)) {
            $status = null;
        }

        return [
            'status' => $status ?: null,
            'search' => $search !== '' ? $search : null,
        ];
    }

    private function buildBookingQuery(Event $event, array $filters): Builder
    {
        $query = EventBooking::query()
            ->with('user')
            ->where('event_id', $event->id)
            ->orderByDesc('booked_at');

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function transformBookingForTable(Event $event, EventBooking $booking): array
    {
        $status = strtolower($booking->status);

        return [
            'id' => $booking->id,
            'full_name' => $booking->full_name,
            'email' => $booking->user?->email ?? $booking->email,
            'phone' => $booking->phone,
            'seats' => $booking->seats,
            'status' => $booking->status,
            'status_label' => ucfirst($status),
            'status_badge_class' => match ($status) {
                EventBooking::STATUS_CONFIRMED => 'bg-success',
                EventBooking::STATUS_CANCELLED => 'bg-danger',
                default => 'bg-warning text-dark',
            },
            'booked_at' => optional($booking->booked_at)->format('M d, Y H:i'),
            'reference' => $booking->reference,
            'update_url' => route('instructor.events.bookings.update', [$event, $booking]),
        ];
    }

    private function bookingStatusOptions(): array
    {
        return [
            EventBooking::STATUS_PENDING => 'Pending',
            EventBooking::STATUS_CONFIRMED => 'Confirmed',
            EventBooking::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get the booking email recipient counts
     * @return array<string,int>
     */
    private function bookingEmailRecipientCounts(Event $event): array
    {
        $statusCounts = $event->bookings()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = $statusCounts->sum();

        $counts = [
            'all' => (int) $total,
        ];

        foreach (array_keys($this->bookingStatusOptions()) as $status) {
            $counts[$status] = (int) ($statusCounts[$status] ?? 0);
        }

        return $counts;
    }

    /**
     * Get the booking email status options
     * @param array<string,string> $statusOptions
     * @param array<string,int> $recipientCounts
     * @return array<string,string>
     */
    private function bookingEmailStatusOptions(array $statusOptions, array $recipientCounts): array
    {
        $options = [
            'all' => 'All attendees',
        ];

        foreach ($statusOptions as $value => $label) {
            $options[$value] = $label;
        }

        return collect($options)
            ->mapWithKeys(function (string $label, string $key) use ($recipientCounts) {
                $count = number_format((int) ($recipientCounts[$key] ?? 0));
                return [$key => "{$label} ({$count})"];
            })
            ->toArray();
    }
}
