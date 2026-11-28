@extends('layouts.admin')

@section('title', 'Attendees & Attendance - ' . $event->title)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.events.show', $event) }}">{{ Str::limit($event->title, 25) }}</a></div>
        <div class="breadcrumb-item active">Attendees &amp; Attendance</div>
    </div>
@endsection

@section('main-content')
<div class="section-body">
    <!-- Top Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="section-title mb-1">Attendee &amp; Gate Attendance Management</h2>
            <p class="section-lead text-muted mb-0">
                Event: <strong>{{ $event->title }}</strong> &bull; 
                <i class="fas fa-map-marker-alt text-danger ml-1"></i> {{ $event->location ?? 'Vamdas Cinema, Addis Ababa' }} &bull;
                <i class="fas fa-calendar text-primary ml-1"></i> {{ optional($event->start_date)->format('M d, Y') ?? 'Dec 20, 2026' }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.events.scanner', $event) }}" class="btn btn-primary btn-lg shadow-sm">
                <i class="fas fa-camera mr-1"></i> Launch Gate Scanner
            </a>
            <a href="{{ route('admin.events.attendees.export', array_merge(['event' => $event->id], request()->query())) }}" class="btn btn-success btn-lg shadow-sm">
                <i class="fas fa-file-excel mr-1"></i> Export Attendees (CSV)
            </a>
        </div>
    </div>

    <!-- Attendance Real-Time Stats Strip -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1 shadow-sm border-0">
                <div class="card-icon bg-primary">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Registered</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['total']) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1 shadow-sm border-0">
                <div class="card-icon bg-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Checked In at Gate</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['checked_in']) }}
                        <small class="text-muted font-weight-normal text-sm">({{ $stats['attendance_rate'] }}%)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1 shadow-sm border-0">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Yet to Arrive</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['not_checked_in']) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1 shadow-sm border-0">
                <div class="card-icon" style="background-color: #6D1A95;">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Students / General</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['students'] }} <small class="text-muted font-weight-normal">/ {{ $stats['general'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Sections Breakdown (A1 to A10) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-dark font-weight-bold">
                <i class="fas fa-layer-group text-primary mr-2"></i>Fira Link Student Cohorts Distribution (A1 through A10)
            </h6>
            <span class="badge badge-primary px-3 py-1 rounded-pill">{{ $stats['students'] }} Students Registered</span>
        </div>
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @foreach(App\Models\EventBooking::STUDENT_SECTIONS as $sec)
                    <a href="{{ route('admin.events.attendees', array_merge(request()->except('section', 'page'), ['section' => $sec, 'category' => 'student', 'event' => $event->id])) }}" 
                       class="btn btn-sm {{ request('section') === $sec ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }} px-3 py-1 rounded-pill mr-2 mb-2">
                        <i class="fas fa-users-rectangle mr-1"></i> Section {{ $sec }}
                        <span class="badge {{ request('section') === $sec ? 'badge-light text-primary' : 'badge-dark text-white' }} ml-1">
                            {{ $sectionCounts[$sec] ?? 0 }}
                        </span>
                    </a>
                @endforeach
                @if(request('section') || request('category'))
                    <a href="{{ route('admin.events.attendees', ['event' => $event->id]) }}" class="btn btn-sm btn-link text-danger mb-2">
                        <i class="fas fa-times-circle mr-1"></i> Clear Filters
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.events.attendees', $event) }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, ticket ID..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <option value="student" @selected(request('category') === 'student')>Fira Link Students</option>
                        <option value="general" @selected(request('category') === 'general')>General Attendees</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <select name="section" class="form-control">
                        <option value="">All Sections (A1-A10)</option>
                        @foreach(App\Models\EventBooking::STUDENT_SECTIONS as $sec)
                            <option value="{{ $sec }}" @selected(request('section') === $sec)>Section {{ $sec }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <select name="check_in" class="form-control">
                        <option value="">All Gate Status</option>
                        <option value="checked_in" @selected(request('check_in') === 'checked_in')>Checked In</option>
                        <option value="not_checked_in" @selected(request('check_in') === 'not_checked_in')>Not Checked In</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'category', 'section', 'check_in', 'status']))
                        <a href="{{ route('admin.events.attendees', $event) }}" class="btn btn-light ml-1" title="Reset Filters">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Attendees Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 text-dark font-weight-bold">
                Registered Attendees ({{ $bookings->total() }})
            </h5>
            <span class="text-muted small">Showing {{ $bookings->firstItem() ?? 0 }} - {{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Attendee Name</th>
                            <th>Ticket ID</th>
                            <th>Category &amp; Section</th>
                            <th>Contact Info</th>
                            <th>Seats</th>
                            <th>Payment</th>
                            <th>Gate Check-In Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td class="text-muted small">{{ $loop->iteration + ($bookings->currentPage() - 1) * $bookings->perPage() }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($booking->user?->hasProfilePhoto())
                                            <img src="{{ $booking->user->profile_photo_url }}" alt="{{ $booking->full_name }}" class="rounded-circle mr-2 border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold mr-2 shadow-sm" style="width: 40px; height: 40px; background: #6D1A95; font-size: 14px;">
                                                {{ $booking->user?->initials ?? mb_strtoupper(mb_substr($booking->first_name, 0, 1) . mb_substr($booking->last_name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-weight-bold text-dark">{{ $booking->full_name ?: ($booking->user?->name ?? 'Guest') }}</div>
                                            @if($booking->company)
                                                <div class="text-muted text-xs">{{ $booking->company }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('events.bookings.verify', $booking->verification_token ?: $booking->reference) }}" target="_blank" class="badge badge-light font-monospace p-2 border" title="Click to view gate verification">
                                        {{ $booking->reference }} <i class="fas fa-external-link-alt ml-1 text-muted"></i>
                                    </a>
                                </td>
                                <td>
                                    @if($booking->isStudent())
                                        <span class="badge text-white" style="background: #6D1A95;">
                                            <i class="fas fa-graduation-cap mr-1"></i> Student
                                        </span>
                                        @if($booking->student_section)
                                            <span class="badge badge-info ml-1 font-weight-bold">
                                                Section {{ $booking->student_section }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-user mr-1"></i> General
                                        </span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div><i class="fas fa-envelope text-muted mr-1"></i> {{ $booking->email ?: 'N/A' }}</div>
                                    @if($booking->phone)
                                        <div><i class="fas fa-phone text-muted mr-1"></i> {{ $booking->phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">{{ $booking->seats }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $booking->payment_status === 'paid' ? 'success' : 'warning' }}">
                                        {{ strtoupper($booking->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($booking->isCheckedIn())
                                        <span class="badge badge-success px-2 py-1">
                                            <i class="fas fa-check-circle mr-1"></i> CHECKED IN
                                        </span>
                                        <div class="text-muted text-xs mt-1">
                                            {{ $booking->checked_in_at->format('M d, H:i') }}
                                            @if($booking->checkedInBy)
                                                by {{ $booking->checkedInBy->name }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge badge-light text-muted border px-2 py-1">
                                            <i class="far fa-clock mr-1"></i> Not Arrived
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="d-inline-flex gap-1">
                                        @if($booking->isCheckedIn())
                                            <form action="{{ route('admin.events.bookings.manual-checkin', $booking) }}" method="POST" onsubmit="return confirm('Revert check-in for {{ addslashes($booking->full_name) }}?');" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="revert" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Revert Check-In">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.events.bookings.manual-checkin', $booking) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success px-3" title="Mark as Checked In">
                                                    <i class="fas fa-check mr-1"></i> Check In
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('events.confirmation', ['reference' => $booking->reference]) }}" target="_blank" class="btn btn-sm btn-light border ml-1" title="View Ticket Pass">
                                            <i class="fas fa-ticket-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-secondary d-block"></i>
                                    <h5>No attendees matched your filter criteria</h5>
                                    <p class="mb-0">Try clearing your search or filter options.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
                <div class="card-footer bg-white border-top py-3 d-flex justify-content-end">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
