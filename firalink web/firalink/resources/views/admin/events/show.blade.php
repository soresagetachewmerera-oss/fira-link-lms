@extends('layouts.admin')

@section('title', $event->title)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></div>
        <div class="breadcrumb-item active">{{ $event->title }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $event->title }}</h4>
                    <span class="badge badge-{{ $event->is_published ? 'success' : 'secondary' }}">
                        {{ $event->is_published ? 'Published' : 'Draft' }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">{{ $event->summary }}</p>

                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted">Schedule</h6>
                        <p class="mb-1">
                            <i class="fas fa-calendar mr-2"></i>
                            {{ $event->start_date?->format('M d, Y') }} - {{ $event->end_date?->format('M d, Y') }}
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock mr-2"></i>
                            {{ $event->start_time?->format('H:i') }} - {{ $event->end_time?->format('H:i') }}
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted">Location</h6>
                        @if($event->location)
                            <p class="mb-1"><i class="fas fa-map-marker-alt mr-2"></i>{{ $event->location }}</p>
                        @endif
                        <p class="mb-0 text-muted">{{ $event->location_description }}</p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted">Description</h6>
                        <div class="leading-relaxed">{!! $event->description !!}</div>
                    </div>

                    @if($event->highlights->isNotEmpty())
                        <div class="mb-3">
                            <h6 class="text-uppercase text-muted">Highlights</h6>
                            <ul class="pl-4">
                                @foreach($event->highlights as $highlight)
                                    <li>{{ $highlight->text }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($event->speakers->isNotEmpty())
                        <div class="mb-3">
                            <h6 class="text-uppercase text-muted">Speakers</h6>
                            <div class="row">
                                @foreach($event->speakers as $speaker)
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-1">{{ $speaker->name }}</h6>
                                            <p class="text-muted mb-2">{{ $speaker->title }}</p>
                                            <p class="mb-0">{{ $speaker->bio }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($event->instructors->isNotEmpty())
                        <div class="mb-0">
                            <h6 class="text-uppercase text-muted">Instructors</h6>
                            <ul class="pl-4">
                                @foreach($event->instructors as $instructor)
                                    <li>{{ $instructor->name }} <span class="text-muted">({{ $instructor->email }})</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Event Summary</h4>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Price:</strong>
                        {{ $event->price ? currency_format($event->price, 2, 'symbol') : 'Free' }}</p>
                    <p class="mb-2"><strong>Total Seats:</strong> {{ $event->total_seats ?? 'Unlimited' }}</p>
                    <p class="mb-2"><strong>Contact Email:</strong> {{ $event->contact_email ?? '—' }}</p>
                    <p class="mb-3"><strong>Contact Phone:</strong> {{ $event->contact_phone ?? '—' }}</p>

                    <form action="{{ route('admin.events.toggle-publish', $event) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-block btn-{{ $event->is_published ? 'warning' : 'success' }}">
                            {{ $event->is_published ? 'Unpublish Event' : 'Publish Event' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Bookings</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Bookings <span class="badge badge-primary">{{ $bookingStats['total'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Confirmed <span class="badge badge-success">{{ $bookingStats['confirmed'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Pending <span class="badge badge-warning">{{ $bookingStats['pending'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Seats Available
                            <span class="badge badge-info">
                                {{ $bookingStats['seats_available'] ?? 'Unlimited' }}
                            </span>
                        </li>
                    </ul>
                    @if($event->bookings->isNotEmpty())
                        <h6>Recent Bookings</h6>
                        <ul class="list-group">
                            @foreach($event->bookings as $booking)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $booking->attendee_name ?? $booking->email }}</span>
                                        <span
                                            class="badge badge-{{ $booking->status === \App\Models\EventBooking::STATUS_CONFIRMED ? 'success' : ($booking->status === \App\Models\EventBooking::STATUS_PENDING ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($booking->status) }}
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        {{ $booking->seats }} seat(s) • {{ $booking->created_at->format('M d, Y H:i') }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No bookings yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection