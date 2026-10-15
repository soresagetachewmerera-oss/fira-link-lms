@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Event Ticket Confirmation',
        'base_url' => route('events.index'),
    ])
@endsection

@section('content')
<section class="event-confirmation-section py-5">
    <div class="container">
        <!-- Success Alert -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 text-center" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4 rounded-3 text-center" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Admission Pass / Ticket Card -->
                <div class="card border-0 rounded-4 shadow-sm overflow-hidden bg-white border" id="printableTicket">
                    <!-- Top Purple Header -->
                    <div class="p-4 text-white d-flex justify-content-between align-items-center" style="background-color: #6D1A95;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-ticket fa-2x"></i>
                            <div>
                                <span class="text-xs text-white-50 text-uppercase fw-semibold d-block">Official Event Admission Pass</span>
                                <h3 class="fw-bold mb-0 text-white" style="font-size: 1.25rem;">{{ $booking->event->title }}</h3>
                            </div>
                        </div>

                        <div class="text-end">
                            <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill text-xs fw-bold">
                                {{ strtoupper($booking->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Ticket Details Body -->
                    <div class="card-body p-4 p-md-5">
                        <div class="row g-4 align-items-center">
                            <!-- Left Info -->
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Ticket Reference</div>
                                        <div class="fw-bold fs-5 text-dark font-monospace">{{ $booking->reference }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Attendee Name</div>
                                        <div class="fw-bold text-dark">{{ $booking->full_name }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Event Date</div>
                                        <div class="fw-bold text-dark">{{ optional($booking->event->start_date)->format('F d, Y') ?? 'TBA' }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Time</div>
                                        <div class="fw-bold text-dark">
                                            {{ $booking->event->start_time ? optional($booking->event->start_time)->format('h:i A') : 'TBA' }}
                                            @if($booking->event->end_time)
                                                - {{ optional($booking->event->end_time)->format('h:i A') }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Venue / Location</div>
                                        <div class="fw-bold text-dark">{{ $booking->event->location ?? 'To Be Announced' }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Reserved Seats</div>
                                        <div class="fw-bold text-dark">{{ $booking->seats }} {{ Str::plural('Seat', $booking->seats) }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Payment Status</div>
                                        <div>
                                            <span class="badge {{ $booking->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} text-xs">
                                                {{ strtoupper($booking->payment_status) }}
                                            </span>
                                            <span class="ms-2 fw-bold text-sm" style="color: #6D1A95;">
                                                @if($booking->total_amount > 0)
                                                    {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
                                                @else
                                                    Free Admission
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Registered On</div>
                                        <div class="text-sm text-muted">{{ optional($booking->booked_at)->format('M d, Y H:i') }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right QR Code -->
                            <div class="col-md-4 text-center border-start ps-md-4">
                                @if(!empty($qrCode))
                                    <img src="{{ $qrCode }}" alt="QR Ticket" class="img-fluid rounded-3 mb-2" style="max-width: 170px;">
                                    <div class="text-xs text-muted">Scan to verify event admission credentials</div>
                                @endif
                            </div>
                        </div>

                        @if ($booking->payment_status === 'pending' && $booking->total_amount > 0)
                            <div class="mt-4 p-3 rounded-3 bg-light border text-sm text-muted">
                                <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>
                                <strong>Payment Pending:</strong> This ticket reservation is currently pending payment. To complete payment, please contact Fira Link or complete transaction quoting reference <strong>{{ $booking->reference }}</strong>.
                            </div>
                        @endif
                    </div>

                    <!-- Ticket Footer Actions -->
                    <div class="p-4 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-3 text-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back to Events
                        </a>
                        <button onclick="window.print()" class="btn btn-primary rounded-3 text-sm d-flex align-items-center gap-2" style="background-color: #6D1A95; border-color: #6D1A95;">
                            <i class="fa-solid fa-print"></i> Print Ticket Pass
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
