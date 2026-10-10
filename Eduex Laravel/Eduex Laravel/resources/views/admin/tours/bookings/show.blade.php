@extends('layouts.admin')

@section('title', 'Booking Reference: ' . $booking->reference)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tours.index') }}">Tours</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tours.bookings.index') }}">Bookings</a></div>
        <div class="breadcrumb-item active">{{ $booking->reference }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <!-- Left: Passenger & Tour Summary -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Reservation Details</h4>
                    <span class="badge {{ $booking->status === 'confirmed' ? 'badge-success' : ($booking->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                        {{ strtoupper($booking->status) }}
                    </span>
                </div>

                <div class="card-body">
                    <div class="mb-4 p-3 bg-light rounded border">
                        <span class="text-muted text-uppercase small font-weight-600 d-block">Tour Expedition</span>
                        <h5 class="mb-1 text-dark">{{ $booking->tour->title ?? 'Tour' }}</h5>
                        <div class="text-muted small">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> Destination: {{ $booking->tour->destination ?? 'N/A' }} &bull;
                            <i class="fas fa-calendar me-1"></i> Scheduled Date: <strong>{{ optional($booking->schedule_date)->format('F d, Y') ?? 'TBA' }}</strong>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Booking Reference</span>
                            <span class="font-weight-600 font-monospace fs-6">{{ $booking->reference }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Primary Passenger</span>
                            <span class="font-weight-600">{{ $booking->full_name }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Email</span>
                            <span>{{ $booking->email }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Phone</span>
                            <span>{{ $booking->phone ?? 'Not provided' }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Registered Seats</span>
                            <span class="font-weight-600">{{ $booking->participants }} Passengers</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Departure Time</span>
                            <span>{{ $booking->departure_time ?? '08:00 AM' }}</span>
                        </div>
                    </div>

                    @if($booking->special_requests)
                        <div class="mb-3 p-3 bg-light rounded">
                            <span class="text-muted small d-block font-weight-600">Special Requests / Notes:</span>
                            <p class="mb-0 text-dark small">{{ $booking->special_requests }}</p>
                        </div>
                    @endif

                    <hr>

                    <!-- Payment Information -->
                    <h6 class="font-weight-600 mb-3">Payment Transaction Info</h6>
                    <div class="row">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Total Amount</span>
                            <h5 class="text-purple mb-0" style="color: #6D1A95;">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</h5>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Payment Status</span>
                            <span class="badge {{ $booking->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                {{ strtoupper($booking->payment_status) }}
                            </span>
                        </div>
                    </div>

                    @if($booking->payment)
                        <div class="mt-3 p-3 bg-light rounded border text-sm">
                            <div><strong>Payment Method:</strong> {{ strtoupper($booking->payment->payment_method) }}</div>
                            @if($booking->payment->chapa_tx_ref)
                                <div><strong>Chapa Tx Ref:</strong> <code class="text-dark">{{ $booking->payment->chapa_tx_ref }}</code></div>
                            @endif
                            @if($booking->payment->chapa_reference)
                                <div><strong>Chapa Reference:</strong> <code class="text-dark">{{ $booking->payment->chapa_reference }}</code></div>
                            @endif
                            @if($booking->payment->transaction_id)
                                <div><strong>Transaction ID:</strong> <code class="text-dark">{{ $booking->payment->transaction_id }}</code></div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Action & Status Update Form -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h4>Manage Status &amp; Settlement</h4>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible show fade">
                            <div class="alert-body">
                                <button class="close" data-dismiss="alert"><span>&times;</span></button>
                                {{ session('success') }}
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.tours.bookings.update-status', $booking) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Booking Operational Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="pending" {{ $booking->status === 'pending' ? 'selected' : '' }}>Pending (Awaiting Confirmation)</option>
                                <option value="confirmed" {{ $booking->status === 'confirmed' ? 'selected' : '' }}>Confirmed (Seat Reserved)</option>
                                <option value="completed" {{ $booking->status === 'completed' ? 'selected' : '' }}>Completed (Tour Finished)</option>
                                <option value="cancelled" {{ $booking->status === 'cancelled' ? 'selected' : '' }}>Cancelled (Capacity Released)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Payment Settlement Status <span class="text-danger">*</span></label>
                            <select name="payment_status" class="form-control" required>
                                <option value="pending" {{ $booking->payment_status === 'pending' ? 'selected' : '' }}>Pending Payment</option>
                                <option value="paid" {{ $booking->payment_status === 'paid' ? 'selected' : '' }}>Paid / Verified</option>
                                <option value="refunded" {{ $booking->payment_status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                <option value="failed" {{ $booking->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Admin Internal Notes</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Internal memo regarding this reservation...">{{ old('notes', $booking->notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="background-color: #6D1A95; border-color: #6D1A95;">
                            <i class="fas fa-save me-1"></i> Update Booking Record
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.tours.bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                            &larr; Back to List
                        </a>
                        <a href="{{ route('tours.confirmation', $booking->reference) }}" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-ticket-alt me-1"></i> View Ticket Pass
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
