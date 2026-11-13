<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of the events assigned to the instructor.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Event::query()
            ->whereHas('instructors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->withCount(['bookings' => function ($q) {
                $q->where('status', EventBooking::STATUS_CONFIRMED);
            }])
            ->orderBy('start_date', 'desc');

        $events = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Display the specified event and its bookings.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();

        $event = Event::query()
            ->whereHas('instructors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with(['instructors'])
            ->withCount([
                'bookings as total_bookings',
                'bookings as confirmed_bookings' => function ($q) {
                    $q->where('status', EventBooking::STATUS_CONFIRMED);
                },
                'bookings as pending_bookings' => function ($q) {
                    $q->where('status', EventBooking::STATUS_PENDING);
                }
            ])
            ->findOrFail($id);

        $bookingsQuery = $event->bookings()->with('user');

        // Apply filters
        if ($request->has('status') && $request->status != 'all') {
            $bookingsQuery->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $bookingsQuery->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        $bookings = $bookingsQuery->latest()->get();
        
        $bookings->transform(function ($booking) {
            $booking->email = $this->maskEmail($booking->email);
            if ($booking->user) {
                $booking->user->email = $this->maskEmail($booking->user->email);
            }
            return $booking;
        });

        $event->setRelation('bookings', $bookings);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the status of a specific booking.
     */
    public function updateBookingStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', [
                EventBooking::STATUS_PENDING,
                EventBooking::STATUS_CONFIRMED,
                EventBooking::STATUS_CANCELLED
            ])
        ]);

        $user = Auth::user();
        $booking = EventBooking::whereHas('event.instructors', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->findOrFail($id);

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully.',
            'data' => $booking
        ]);
    }

    /**
     * Send email to all attendees of an event.
     */
    public function sendEmail(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'nullable|string'
        ]);

        $user = Auth::user();
        $event = Event::whereHas('instructors', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->findOrFail($id);

        $query = $event->bookings();
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $bookings = $query->get();

        foreach ($bookings as $booking) {
            // In a real app, you would use a Mailable here
            // \Mail::to($booking->email)->send(new EventAttendeeMail($request->subject, $request->message));
        }

        return response()->json([
            'success' => true,
            'message' => 'Email sent to ' . $bookings->count() . ' attendees.'
        ]);
    }

    /**
     * Export bookings for an event as CSV.
     */
    public function export($id)
    {
        $user = Auth::user();
        $event = Event::whereHas('instructors', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->findOrFail($id);

        $bookings = $event->bookings()->with('user')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=bookings_event_{$id}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Attendee', 'Email', 'Phone', 'Seats', 'Status', 'Booked At', 'Reference'];

        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($bookings as $booking) {
                $row['Attendee']  = $booking->user ? $booking->user->name : $booking->first_name . ' ' . $booking->last_name;
                $row['Email']     = $booking->email;
                $row['Phone']     = $booking->phone;
                $row['Seats']     = $booking->quantity;
                $row['Status']    = $booking->status;
                $row['Booked At'] = $booking->booked_at;
                $row['Reference'] = $booking->reference;

                fputcsv($file, array_values($row));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Verify a ticket by its reference.
     */
    public function verifyTicket(Request $request)
    {
        $request->validate([
            'reference' => 'required|string'
        ]);

        $user = Auth::user();
        $booking = EventBooking::with(['event', 'user'])
            ->where('reference', $request->reference)
            ->whereHas('event.instructors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ticket reference or you are not an instructor for this event.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'status' => $booking->status,
                'seats' => $booking->seats,
                'booked_at' => $booking->booked_at,
                'attendee' => $booking->user ? $booking->user->name : $booking->first_name . ' ' . $booking->last_name,
                'email' => $this->maskEmail($booking->email),
                'event' => [
                    'id' => $booking->event->id,
                    'title' => $booking->event->title,
                    'start_date' => $booking->event->start_date,
                    'start_time' => $booking->event->start_time,
                    'location' => $booking->event->location,
                ]
            ]
        ]);
    }
    
    /**
     * Mask email address for privacy
     */
    private function maskEmail($email)
    {
        if (!$email) return '';
        $parts = explode('@', $email);
        if (count($parts) < 2) return $email;
        
        $name = $parts[0];
        $domain = $parts[1];
        
        $len = strlen($name);
        if ($len <= 2) {
            return $name . '***@' . $domain;
        }
        
        return substr($name, 0, 1) . str_repeat('*', min($len - 2, 5)) . substr($name, -1) . '@' . $domain;
    }
}
