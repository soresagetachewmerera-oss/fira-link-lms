@extends('layouts.student')

@section('content')
<div class="dashboard-content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold fs-4 mb-1">My Tour Bookings</h2>
            <p class="text-muted text-sm mb-0">Track your upcoming and past field expeditions, view digital tickets, and manage reservations.</p>
        </div>
        <a href="{{ route('tours.index') }}" class="btn text-white px-3 py-2 rounded-3 text-xs fw-semibold" style="background-color: #6D1A95;">
            <i class="fa-solid fa-plus me-1"></i> Browse Tours
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 text-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-4 rounded-3 text-sm" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($bookings->isEmpty())
        <div class="text-center py-5 card-box rounded-4 p-5 bg-white border">
            <i class="fa-solid fa-route fa-3x mb-3" style="color: #6D1A95;"></i>
            <h3 class="fw-bold text-base mb-1">No Tour Bookings Found</h3>
            <p class="text-muted text-sm mb-3">You haven't booked any tours or origin expeditions yet.</p>
            <a href="{{ route('tours.index') }}" class="btn text-white px-4 py-2 rounded-3 text-xs fw-semibold" style="background-color: #6D1A95;">
                Explore Available Tours
            </a>
        </div>
    @else
        <div class="card border-0 rounded-4 shadow-sm overflow-hidden bg-white border">
            <div class="table-responsive">
                <table class="table align-middle mb-0 text-sm">
                    <thead class="table-light text-xs text-uppercase text-muted">
                        <tr>
                            <th class="ps-4">Reference</th>
                            <th>Tour Expedition</th>
                            <th>Departure Date</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            <tr>
                                <td class="ps-4 fw-bold font-monospace text-xs">{{ $booking->reference }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $booking->tour->title ?? 'Tour' }}</div>
                                    <span class="text-xs text-muted"><i class="fa-solid fa-location-dot me-1"></i> {{ $booking->tour->destination ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div>{{ optional($booking->schedule_date)->format('M d, Y') ?? 'TBA' }}</div>
                                    <span class="text-xs text-muted">{{ $booking->departure_time ?? '08:00 AM' }}</span>
                                </td>
                                <td>{{ $booking->participants }}</td>
                                <td class="fw-bold" style="color: #6D1A95;">
                                    {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $booking->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} text-xs">
                                        {{ strtoupper($booking->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : ($booking->status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark') }} text-xs">
                                        {{ strtoupper($booking->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('tours.confirmation', $booking->reference) }}" class="btn btn-sm btn-outline-secondary text-xs rounded-2" title="View Boarding Pass">
                                            <i class="fa-solid fa-ticket"></i> Ticket
                                        </a>

                                        @if($booking->status !== 'cancelled')
                                            <form action="{{ route('student.tours.cancel', $booking->reference) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel booking {{ $booking->reference }}?');" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-xs rounded-2" title="Cancel Booking">
                                                    Cancel
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-center">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
