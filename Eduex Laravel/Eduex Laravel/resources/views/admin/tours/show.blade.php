@extends('layouts.admin')

@section('title', 'Tour Details: ' . $tour->title)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tours.index') }}">Tours</a></div>
        <div class="breadcrumb-item active">{{ Str::limit($tour->title, 20) }}</div>
    </div>
@endsection

@section('main-content')
    <!-- Metric Cards for this Tour -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-users"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Bookings</h4></div>
                    <div class="card-body">{{ $bookingStats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-check-double"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Confirmed</h4></div>
                    <div class="card-body">{{ $bookingStats['confirmed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-chair"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Available Seats</h4></div>
                    <div class="card-body">{{ $bookingStats['remaining_seats'] }} / {{ $tour->total_seats }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-money-bill-wave"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Revenue (ETB)</h4></div>
                    <div class="card-body">{{ number_format($bookingStats['revenue'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tour Profile Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Expedition Overview</h4>
            <div class="btn-group">
                <a href="{{ route('tours.show', $tour->slug) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i> View on Frontend
                </a>
                <a href="{{ route('admin.tours.edit', $tour) }}" class="btn btn-primary btn-sm" style="background-color: #6D1A95; border-color: #6D1A95;">
                    <i class="fas fa-edit me-1"></i> Edit Tour
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <img src="{{ $tour->featured_image_url }}" alt="{{ $tour->title }}" class="img-fluid rounded shadow-sm mb-3" style="max-height: 220px; width: 100%; object-fit: cover;">
                    <div class="p-3 bg-light rounded border text-sm">
                        <div class="mb-2"><strong>Destination:</strong> {{ $tour->destination }}</div>
                        <div class="mb-2"><strong>Category:</strong> {{ $tour->category ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>Difficulty:</strong> <span class="badge badge-info">{{ $tour->difficulty_level }}</span></div>
                        <div class="mb-2"><strong>Duration:</strong> {{ $tour->duration_days }} Days</div>
                        <div class="mb-2"><strong>Price:</strong> <span class="font-weight-600 text-purple" style="color: #6D1A95;">{{ $tour->currency }} {{ number_format($tour->price, 2) }}</span></div>
                        <div><strong>Status:</strong> <span class="badge {{ $tour->is_published ? 'badge-success' : 'badge-secondary' }}">{{ $tour->is_published ? 'Published' : 'Hidden' }}</span></div>
                    </div>
                </div>

                <div class="col-md-8">
                    <h5 class="mb-2">{{ $tour->title }}</h5>
                    <p class="text-muted mb-4">{{ $tour->summary }}</p>

                    <h6 class="font-weight-600">Full Description:</h6>
                    <div class="text-muted mb-4" style="line-height: 1.6; white-space: pre-line;">{{ $tour->description }}</div>

                    @if(!empty($tour->itinerary))
                        <h6 class="font-weight-600 mb-2">Itinerary Timeline:</h6>
                        <ul class="list-group list-group-flush mb-4">
                            @foreach($tour->itinerary as $step)
                                <li class="list-group-item px-0">
                                    <strong>Day {{ $step['day'] ?? $loop->iteration }}: {{ $step['title'] ?? '' }}</strong>
                                    <div class="text-muted small mt-1">{{ $step['description'] ?? '' }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Attendees & Bookings for this Tour -->
    <div class="card mt-4">
        <div class="card-header">
            <h4>Bookings for this Expedition ({{ $tour->bookings->count() }})</h4>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-md">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Passenger Name</th>
                            <th>Contact</th>
                            <th>Seats</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Booking Status</th>
                            <th>Booked At</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tour->bookings as $b)
                            <tr>
                                <td class="font-weight-600 font-monospace">{{ $b->reference }}</td>
                                <td>{{ $b->full_name }}</td>
                                <td>
                                    <small class="d-block">{{ $b->email }}</small>
                                    <small class="text-muted">{{ $b->phone }}</small>
                                </td>
                                <td>{{ $b->participants }}</td>
                                <td class="font-weight-600 text-purple" style="color: #6D1A95;">
                                    {{ $b->currency }} {{ number_format($b->total_amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $b->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                        {{ strtoupper($b->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $b->status === 'confirmed' ? 'badge-success' : ($b->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                                        {{ strtoupper($b->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($b->booked_at)->format('M d, Y H:i') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tours.bookings.show', $b) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No bookings received for this tour yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
