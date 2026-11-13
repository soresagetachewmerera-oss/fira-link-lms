<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TourManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tour::query()->withCount([
            'bookings as confirmed_bookings_count' => fn($q) => $q->where('status', TourBooking::STATUS_CONFIRMED),
            'bookings',
        ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('published')) {
            $query->where('is_published', $request->boolean('published'));
        }

        $tours = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_tours' => Tour::count(),
            'published_tours' => Tour::where('is_published', true)->count(),
            'total_bookings' => TourBooking::count(),
            'confirmed_bookings' => TourBooking::where('status', TourBooking::STATUS_CONFIRMED)->count(),
            'total_revenue' => TourBooking::where('payment_status', TourBooking::PAYMENT_PAID)->sum('total_amount'),
        ];

        return view('admin.tours.index', compact('tours', 'stats'));
    }

    public function create(): View
    {
        return view('admin.tours.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:1000',
            'description' => 'required|string',
            'destination' => 'required|string|max:255',
            'meeting_point' => 'nullable|string|max:255',
            'duration_days' => 'required|integer|min:1',
            'duration_hours' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'departure_time' => 'nullable|string',
            'total_seats' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'category' => 'nullable|string|max:100',
            'difficulty_level' => 'required|string|in:Easy,Moderate,Challenging',
            'cancellation_policy' => 'nullable|string',
            'featured_image' => 'nullable|image|max:5120',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Tour::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        $validated['slug'] = $slug;

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('tours/covers', 'public');
        }

        // Process Itinerary JSON
        if ($request->filled('itinerary_titles')) {
            $itinerary = [];
            $titles = $request->input('itinerary_titles', []);
            $descriptions = $request->input('itinerary_descriptions', []);
            foreach ($titles as $idx => $t) {
                if (!empty($t)) {
                    $itinerary[] = [
                        'day' => $idx + 1,
                        'title' => $t,
                        'description' => $descriptions[$idx] ?? '',
                    ];
                }
            }
            $validated['itinerary'] = $itinerary;
        }

        // Process Inclusions & Exclusions
        if ($request->filled('included_services_text')) {
            $validated['included_services'] = array_filter(array_map('trim', explode("\n", $request->input('included_services_text'))));
        }
        if ($request->filled('exclusions_text')) {
            $validated['exclusions'] = array_filter(array_map('trim', explode("\n", $request->input('exclusions_text'))));
        }
        if ($request->filled('requirements_text')) {
            $validated['requirements'] = array_filter(array_map('trim', explode("\n", $request->input('requirements_text'))));
        }

        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        $tour = Tour::create($validated);

        return redirect()->route('admin.tours.index')
            ->with('success', "Tour \"{$tour->title}\" created successfully.");
    }

    public function show(Tour $tour): View
    {
        $tour->load(['bookings.user', 'bookings.payment']);
        
        $bookingStats = [
            'total' => $tour->bookings()->count(),
            'confirmed' => $tour->bookings()->where('status', TourBooking::STATUS_CONFIRMED)->count(),
            'pending' => $tour->bookings()->where('status', TourBooking::STATUS_PENDING)->count(),
            'cancelled' => $tour->bookings()->where('status', TourBooking::STATUS_CANCELLED)->count(),
            'remaining_seats' => $tour->remainingSeats(),
            'revenue' => $tour->bookings()->where('payment_status', TourBooking::PAYMENT_PAID)->sum('total_amount'),
        ];

        return view('admin.tours.show', compact('tour', 'bookingStats'));
    }

    public function edit(Tour $tour): View
    {
        return view('admin.tours.edit', compact('tour'));
    }

    public function update(Request $request, Tour $tour): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:1000',
            'description' => 'required|string',
            'destination' => 'required|string|max:255',
            'meeting_point' => 'nullable|string|max:255',
            'duration_days' => 'required|integer|min:1',
            'duration_hours' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'departure_time' => 'nullable|string',
            'total_seats' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'category' => 'nullable|string|max:100',
            'difficulty_level' => 'required|string|in:Easy,Moderate,Challenging',
            'cancellation_policy' => 'nullable|string',
            'featured_image' => 'nullable|image|max:5120',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('tours/covers', 'public');
        }

        // Process Itinerary JSON
        if ($request->filled('itinerary_titles')) {
            $itinerary = [];
            $titles = $request->input('itinerary_titles', []);
            $descriptions = $request->input('itinerary_descriptions', []);
            foreach ($titles as $idx => $t) {
                if (!empty($t)) {
                    $itinerary[] = [
                        'day' => $idx + 1,
                        'title' => $t,
                        'description' => $descriptions[$idx] ?? '',
                    ];
                }
            }
            $validated['itinerary'] = $itinerary;
        }

        if ($request->filled('included_services_text')) {
            $validated['included_services'] = array_filter(array_map('trim', explode("\n", $request->input('included_services_text'))));
        }
        if ($request->filled('exclusions_text')) {
            $validated['exclusions'] = array_filter(array_map('trim', explode("\n", $request->input('exclusions_text'))));
        }
        if ($request->filled('requirements_text')) {
            $validated['requirements'] = array_filter(array_map('trim', explode("\n", $request->input('requirements_text'))));
        }

        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        $tour->update($validated);

        return redirect()->route('admin.tours.show', $tour)
            ->with('success', "Tour \"{$tour->title}\" updated successfully.");
    }

    public function destroy(Tour $tour): RedirectResponse
    {
        $hasActiveBookings = $tour->bookings()
            ->whereIn('status', [TourBooking::STATUS_CONFIRMED, TourBooking::STATUS_PENDING])
            ->exists();

        if ($hasActiveBookings) {
            return back()->with('error', 'Cannot delete a tour that currently has active or confirmed bookings. Unpublish or cancel bookings first.');
        }

        $tour->delete();
        return redirect()->route('admin.tours.index')
            ->with('success', 'Tour deleted successfully.');
    }

    public function togglePublish(Tour $tour): RedirectResponse
    {
        $tour->is_published = !$tour->is_published;
        $tour->save();

        $state = $tour->is_published ? 'published' : 'hidden';
        return back()->with('success', "Tour \"{$tour->title}\" is now {$state}.");
    }

    // ==========================================
    // Bookings Management
    // ==========================================

    public function bookings(Request $request): View
    {
        $query = TourBooking::query()->with(['tour', 'user', 'payment']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tour_id')) {
            $query->where('tour_id', $request->input('tour_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        $bookings = $query->latest('booked_at')->paginate(20)->withQueryString();

        $tours = Tour::select('id', 'title')->orderBy('title')->get();

        $stats = [
            'total' => TourBooking::count(),
            'confirmed' => TourBooking::where('status', TourBooking::STATUS_CONFIRMED)->count(),
            'pending' => TourBooking::where('status', TourBooking::STATUS_PENDING)->count(),
            'cancelled' => TourBooking::where('status', TourBooking::STATUS_CANCELLED)->count(),
            'revenue' => TourBooking::where('payment_status', TourBooking::PAYMENT_PAID)->sum('total_amount'),
        ];

        return view('admin.tours.bookings.index', compact('bookings', 'tours', 'stats'));
    }

    public function showBooking(TourBooking $booking): View
    {
        $booking->load(['tour', 'user', 'payment']);
        return view('admin.tours.bookings.show', compact('booking'));
    }

    public function updateBookingStatus(Request $request, TourBooking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,completed,cancelled',
            'payment_status' => 'required|string|in:pending,paid,refunded,failed',
            'notes' => 'nullable|string|max:1000',
        ]);

        $booking->update([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'notes' => $validated['notes'],
        ]);

        // If payment status is marked paid and payment record exists, update payment status too
        if ($booking->payment) {
            if ($validated['payment_status'] === TourBooking::PAYMENT_PAID) {
                $booking->payment->update(['status' => Payment::STATUS_COMPLETED]);
            } elseif ($validated['payment_status'] === TourBooking::PAYMENT_REFUNDED) {
                $booking->payment->update(['status' => Payment::STATUS_REFUNDED]);
            }
        }

        return back()->with('success', "Booking {$booking->reference} updated successfully.");
    }
}
