@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Booking Confirmation',
        'base_url' => route('tours.index'),
    ])
@endsection

@section('content')
<section class="tour-confirmation-section py-5">
    <div class="container">
        <!-- Success Alert -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 text-center" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Boarding Pass / Ticket Card -->
                <div class="card border-0 rounded-4 shadow-sm overflow-hidden bg-white border" id="printableTicket">
                    <!-- Top Purple Header -->
                    <div class="p-4 text-white d-flex justify-content-between align-items-center" style="background-color: #6D1A95;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-route fa-2x"></i>
                            <div>
                                <span class="text-xs text-white-50 text-uppercase fw-semibold d-block">Official Boarding Pass</span>
                                <h3 class="fw-bold mb-0 text-white" style="font-size: 1.25rem;">{{ $booking->tour->title }}</h3>
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
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Booking Reference</div>
                                        <div class="fw-bold fs-5 text-dark font-monospace">{{ $booking->reference }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Primary Passenger</div>
                                        <div class="fw-bold text-dark">{{ $booking->full_name }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Departure Date</div>
                                        <div class="fw-bold text-dark">{{ optional($booking->schedule_date)->format('F d, Y') ?? 'TBA' }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Departure Time</div>
                                        <div class="fw-bold text-dark">{{ $booking->departure_time ?? optional($booking->tour->departure_time)->format('h:i A') ?? '08:00 AM' }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Destination</div>
                                        <div class="fw-bold text-dark">{{ $booking->tour->destination }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Total Participants</div>
                                        <div class="fw-bold text-dark">{{ $booking->participants }} {{ Str::plural('Passenger', $booking->participants) }}</div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Meeting Point</div>
                                        <div class="text-sm text-dark">{{ $booking->tour->meeting_point ?? 'Fira Link Headquarters, Addis Ababa' }}</div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Payment Status</div>
                                        <div>
                                            <span class="badge {{ $booking->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} text-xs">
                                                {{ strtoupper($booking->payment_status) }}
                                            </span>
                                            <span class="ms-2 fw-bold text-sm text-purple" style="color: #6D1A95;">
                                                {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="text-xs text-muted text-uppercase fw-semibold">Booked On</div>
                                        <div class="text-sm text-muted">{{ optional($booking->booked_at)->format('M d, Y H:i') }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right QR Code -->
                            <div class="col-md-4 text-center border-start ps-md-4">
                                @if(!empty($qrCode))
                                    <img src="{{ $qrCode }}" alt="QR Ticket" class="img-fluid rounded-3 mb-2" style="max-width: 170px;">
                                    <div class="text-xs text-muted">Scan to verify booking credentials</div>
                                @endif
                            </div>
                        </div>

                        @if ($booking->payment_status === 'pending')
                            <div class="mt-4 p-3 rounded-3 bg-light border text-sm text-muted">
                                <i class="fa-solid fa-clock me-1 text-warning"></i>
                                <strong>Payment Pending:</strong> If paying via bank transfer, please transfer <strong>{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong> to Fira Link corporate account and quote reference <strong>{{ $booking->reference }}</strong>.
                            </div>
                        @endif
                    </div>

                    <!-- Ticket Footer Actions -->
                    <div class="p-4 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <a href="{{ route('tours.index') }}" class="btn btn-outline-secondary px-4 rounded-3 text-xs fw-semibold">
                            &larr; Explore More Tours
                        </a>

                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-outline-dark px-3 rounded-3 text-xs fw-semibold">
                                <i class="fa-solid fa-print me-1"></i> Print Ticket
                            </button>
                            @if(Auth::check())
                                <a href="{{ route('student.tours') }}" class="btn text-white px-3 rounded-3 text-xs fw-semibold" style="background-color: #6D1A95;">
                                    View My Bookings &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
