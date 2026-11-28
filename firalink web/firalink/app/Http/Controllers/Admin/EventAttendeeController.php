<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventAttendeeController extends Controller
{
    /**
     * Display the attendees & attendance management dashboard for an event.
     */
    public function index(Request $request, Event $event): View
    {
        $query = $event->bookings()->with(['user', 'checkedInBy'])->latest('id');

        // Search by name, email, phone, reference
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('verification_token', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by category: student vs general
        if ($category = $request->string('category')->trim()->value()) {
            if (in_array($category, [EventBooking::CATEGORY_STUDENT, EventBooking::CATEGORY_GENERAL], true)) {
                $query->where('category', $category);
            }
        }

        // Filter by student section (A1 to A10)
        if ($section = $request->string('section')->trim()->value()) {
            if (in_array($section, EventBooking::STUDENT_SECTIONS, true)) {
                $query->where('category', EventBooking::CATEGORY_STUDENT)
                      ->where('student_section', $section);
            }
        }

        // Filter by attendance / check-in status
        if ($checkInStatus = $request->string('check_in')->trim()->value()) {
            if ($checkInStatus === 'checked_in') {
                $query->whereNotNull('checked_in_at');
            } elseif ($checkInStatus === 'not_checked_in') {
                $query->whereNull('checked_in_at');
            }
        }

        // Filter by booking status
        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(25)->withQueryString();

        // Calculate real-time attendance stats
        $totalBookings = $event->bookings()->count();
        $confirmedBookings = $event->bookings()->where('status', EventBooking::STATUS_CONFIRMED)->count();
        $checkedInCount = $event->bookings()->whereNotNull('checked_in_at')->count();
        $notCheckedInCount = $confirmedBookings - $checkedInCount;
        if ($notCheckedInCount < 0) {
            $notCheckedInCount = 0;
        }

        $studentsCount = $event->bookings()->where('category', EventBooking::CATEGORY_STUDENT)->count();
        $generalCount = $event->bookings()->where('category', EventBooking::CATEGORY_GENERAL)->count();

        $attendanceRate = $confirmedBookings > 0 ? round(($checkedInCount / $confirmedBookings) * 100, 1) : 0;

        // Section breakdown counts
        $sectionCounts = [];
        foreach (EventBooking::STUDENT_SECTIONS as $sec) {
            $sectionCounts[$sec] = $event->bookings()
                ->where('category', EventBooking::CATEGORY_STUDENT)
                ->where('student_section', $sec)
                ->count();
        }

        $stats = [
            'total' => $totalBookings,
            'confirmed' => $confirmedBookings,
            'checked_in' => $checkedInCount,
            'not_checked_in' => $notCheckedInCount,
            'attendance_rate' => $attendanceRate,
            'students' => $studentsCount,
            'general' => $generalCount,
        ];

        return view('admin.events.attendees', compact('event', 'bookings', 'stats', 'sectionCounts'));
    }

    /**
     * Export attendees list as CSV.
     */
    public function export(Request $request, Event $event): StreamedResponse
    {
        $query = $event->bookings()->with(['user', 'checkedInBy'])->latest('id');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->trim()->value()) {
            if (in_array($category, [EventBooking::CATEGORY_STUDENT, EventBooking::CATEGORY_GENERAL], true)) {
                $query->where('category', $category);
            }
        }

        if ($section = $request->string('section')->trim()->value()) {
            if (in_array($section, EventBooking::STUDENT_SECTIONS, true)) {
                $query->where('student_section', $section);
            }
        }

        if ($checkInStatus = $request->string('check_in')->trim()->value()) {
            if ($checkInStatus === 'checked_in') {
                $query->whereNotNull('checked_in_at');
            } elseif ($checkInStatus === 'not_checked_in') {
                $query->whereNull('checked_in_at');
            }
        }

        $filename = sprintf('attendees-%s-%s.csv', Str::slug($event->title), date('Y-m-d-His'));

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Ticket Reference',
                'Attendee Full Name',
                'Email',
                'Phone',
                'Company',
                'Category',
                'Student Section',
                'Seats Reserved',
                'Booking Status',
                'Payment Status',
                'Total Amount',
                'Registration Date',
                'Gate Check-In Status',
                'Check-In Time',
                'Verified By Staff',
            ]);

            $query->chunk(100, function ($bookings) use ($file) {
                foreach ($bookings as $b) {
                    fputcsv($file, [
                        $b->reference,
                        $b->full_name ?: ($b->user?->name ?? 'N/A'),
                        $b->email ?: ($b->user?->email ?? 'N/A'),
                        $b->phone ?: ($b->user?->phone ?? 'N/A'),
                        $b->company ?? '',
                        $b->isStudent() ? 'Fira Link Student' : 'General Attendee',
                        $b->student_section ?? 'N/A',
                        $b->seats,
                        strtoupper($b->status),
                        strtoupper($b->payment_status),
                        $b->total_amount . ' ' . $b->currency,
                        optional($b->booked_at)->format('Y-m-d H:i:s'),
                        $b->isCheckedIn() ? 'CHECKED IN' : 'NOT CHECKED IN',
                        $b->checked_in_at ? $b->checked_in_at->format('Y-m-d H:i:s') : '',
                        $b->checkedInBy?->name ?? '',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manual Check-In toggle from Admin Table.
     */
    public function manualCheckIn(Request $request, EventBooking $booking)
    {
        if ($booking->isCheckedIn()) {
            // Revert check-in if requested
            if ($request->boolean('revert')) {
                $booking->update([
                    'checked_in_at' => null,
                    'checked_in_by' => null,
                ]);
                ToastMagic::success('Check-in reverted for ' . $booking->full_name);
                return back();
            }

            ToastMagic::warning('Ticket ' . $booking->reference . ' was already checked in.');
            return back();
        }

        $booking->update([
            'checked_in_at' => now(),
            'checked_in_by' => Auth::id(),
            'status' => EventBooking::STATUS_CONFIRMED,
        ]);

        ToastMagic::success('Checked in ' . $booking->full_name . ' (' . $booking->reference . ')');
        return back();
    }

    /**
     * Render the dedicated Gate Scanner Interface for staff.
     */
    public function scanner(Request $request, ?Event $event = null): View
    {
        if (!$event) {
            $event = Event::where('is_published', true)
                ->whereDate('start_date', '>=', now()->subDays(2))
                ->orderBy('start_date', 'asc')
                ->first() ?? Event::latest()->firstOrFail();
        }

        $eventsList = Event::where('is_published', true)->orderByDesc('start_date')->take(10)->get();

        $stats = [
            'total' => $event->bookings()->where('status', EventBooking::STATUS_CONFIRMED)->count(),
            'checked_in' => $event->bookings()->whereNotNull('checked_in_at')->count(),
            'students' => $event->bookings()->where('category', EventBooking::CATEGORY_STUDENT)->count(),
            'general' => $event->bookings()->where('category', EventBooking::CATEGORY_GENERAL)->count(),
        ];
        $stats['remaining'] = max(0, $stats['total'] - $stats['checked_in']);
        $stats['rate'] = $stats['total'] > 0 ? round(($stats['checked_in'] / $stats['total']) * 100, 1) : 0;

        return view('admin.events.scanner', compact('event', 'eventsList', 'stats'));
    }

    /**
     * API: Lookup ticket by scan query (URL, verification token, or reference).
     */
    public function apiLookup(Request $request, Event $event): JsonResponse
    {
        $rawQuery = trim((string) $request->input('code', ''));
        if (empty($rawQuery)) {
            return response()->json(['success' => false, 'message' => 'Please provide a valid QR code or ticket ID.'], 400);
        }

        // If scanned text is a URL, extract reference or token
        $tokenOrRef = $rawQuery;
        if (str_contains($rawQuery, '/events/bookings/verify/')) {
            $parts = explode('/events/bookings/verify/', $rawQuery);
            $tokenOrRef = trim(end($parts), '/?# ');
        } elseif (preg_match('/[A-Za-z0-9\-_]{8,64}/', $rawQuery, $matches)) {
            $tokenOrRef = $matches[0];
        }

        $booking = $event->bookings()
            ->with(['user', 'checkedInBy'])
            ->where(function ($q) use ($tokenOrRef) {
                $q->where('reference', $tokenOrRef)
                  ->orWhere('verification_token', $tokenOrRef);
            })
            ->first();

        if (!$booking) {
            // Check globally across all events
            $globalBooking = EventBooking::with(['event', 'user', 'checkedInBy'])
                ->where(function ($q) use ($tokenOrRef) {
                    $q->where('reference', $tokenOrRef)
                      ->orWhere('verification_token', $tokenOrRef);
                })
                ->first();

            if ($globalBooking) {
                return response()->json([
                    'success' => false,
                    'status' => 'wrong_event',
                    'message' => 'Ticket belongs to a different event: "' . ($globalBooking->event->title ?? 'Other Event') . '"',
                    'booking' => $this->formatBookingPayload($globalBooking),
                ], 404);
            }

            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'Invalid ticket: No matching booking found for code "' . $tokenOrRef . '"',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'booking' => $this->formatBookingPayload($booking),
        ]);
    }

    /**
     * API: Confirm check-in via Gate Scanner.
     */
    public function apiCheckIn(Request $request, Event $event, EventBooking $booking): JsonResponse
    {
        if ((int) $booking->event_id !== (int) $event->id) {
            return response()->json([
                'success' => false,
                'status' => 'wrong_event',
                'message' => 'This ticket does not belong to the active event.',
            ], 422);
        }

        if ($booking->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'status' => 'already_checked_in',
                'message' => 'DOUBLE ENTRY PREVENTED: Ticket was already checked in on ' . $booking->checked_in_at->format('M d, Y h:i A') . ' by ' . ($booking->checkedInBy?->name ?? 'Gate Staff'),
                'checked_in_at' => $booking->checked_in_at->format('M d, Y h:i A'),
                'checked_in_by' => $booking->checkedInBy?->name ?? 'Gate Staff',
                'booking' => $this->formatBookingPayload($booking),
            ], 422);
        }

        if ($booking->status === EventBooking::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'status' => 'cancelled',
                'message' => 'ENTRY DENIED: This ticket has been cancelled.',
                'booking' => $this->formatBookingPayload($booking),
            ], 422);
        }

        $booking->update([
            'checked_in_at' => now(),
            'checked_in_by' => Auth::id(),
            'status' => EventBooking::STATUS_CONFIRMED,
        ]);

        $booking->load('checkedInBy');

        return response()->json([
            'success' => true,
            'status' => 'checked_in',
            'message' => 'ENTRY CONFIRMED: Attendee checked in successfully!',
            'checked_in_at' => $booking->checked_in_at->format('M d, Y h:i A'),
            'checked_in_by' => $booking->checkedInBy?->name ?? Auth::user()->name,
            'booking' => $this->formatBookingPayload($booking),
        ]);
    }

    private function formatBookingPayload(EventBooking $booking): array
    {
        $statusState = 'valid';
        if ($booking->isCheckedIn()) {
            $statusState = 'already_used';
        } elseif ($booking->status === EventBooking::STATUS_CANCELLED) {
            $statusState = 'cancelled';
        } elseif ($booking->payment_status !== EventBooking::PAYMENT_PAID && $booking->total_amount > 0) {
            $statusState = 'pending_payment';
        }

        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'full_name' => $booking->full_name ?: ($booking->user?->name ?? 'Guest Attendee'),
            'email' => $booking->email ?: ($booking->user?->email ?? ''),
            'phone' => $booking->phone ?: ($booking->user?->phone ?? ''),
            'company' => $booking->company ?? '',
            'category' => $booking->category_label,
            'is_student' => $booking->isStudent(),
            'student_section' => $booking->student_section,
            'seats' => $booking->seats,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'is_checked_in' => $booking->isCheckedIn(),
            'checked_in_at' => $booking->checked_in_at ? $booking->checked_in_at->format('M d, Y h:i A') : null,
            'checked_in_by' => $booking->checkedInBy?->name,
            'status_state' => $statusState,
            'profile_photo_url' => $booking->user?->profile_photo_url,
            'initials' => $booking->user?->initials ?? mb_strtoupper(mb_substr($booking->first_name, 0, 1) . mb_substr($booking->last_name, 0, 1)),
            'event_title' => $booking->event?->title,
        ];
    }
}
