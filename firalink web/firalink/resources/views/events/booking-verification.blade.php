@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Gate Ticket Verification',
        'base_url' => route('events.index'),
    ])
@endsection

@section('content')
<section class="gate-verification-section py-5" style="background: linear-gradient(180deg, #F9F5FF 0%, #FFFFFF 100%); min-height: 80vh;">
    <div class="container">
        <!-- Status Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-4 text-center shadow-sm" role="alert" style="border-left: 5px solid #10B981;">
                <i class="fa-solid fa-circle-check me-2 fs-5"></i> <strong>Success:</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-4 text-center shadow-sm" role="alert" style="border-left: 5px solid #EF4444;">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i> <strong>Warning:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8 col-md-10">

                <!-- Main Verification Card -->
                <div class="card border-0 shadow-lg overflow-hidden rounded-4" style="border: 2px solid #E9D5FF !important;">
                    
                    <!-- Gate Verification State Header -->
                    @if($details['verification_state'] === 'valid')
                        <!-- Green Valid State -->
                        <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #059669 0%, #10B981 100%);">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-success mb-2 shadow-sm" style="width: 58px; height: 58px;">
                                <i class="fa-solid fa-check fs-2"></i>
                            </div>
                            <h2 class="fw-bold mb-1 text-white" style="font-size: 1.5rem;">VALID TICKET PASS</h2>
                            <p class="text-white-50 mb-0 small">Official Fira Link Event Admission Pass &bull; Ready for Gate Check-In</p>
                        </div>
                    @elseif($details['verification_state'] === 'already_used')
                        <!-- Red/Orange Already Checked In State -->
                        <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #DC2626 0%, #EA580C 100%);">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-danger mb-2 shadow-sm" style="width: 58px; height: 58px;">
                                <i class="fa-solid fa-ban fs-2"></i>
                            </div>
                            <h2 class="fw-bold mb-1 text-white" style="font-size: 1.5rem;">ALREADY CHECKED IN</h2>
                            <p class="text-white-50 mb-0 small">
                                This ticket was already verified and admitted into the venue.
                            </p>
                        </div>
                    @elseif($details['verification_state'] === 'cancelled')
                        <!-- Cancelled State -->
                        <div class="p-4 text-white text-center bg-danger">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-danger mb-2 shadow-sm" style="width: 58px; height: 58px;">
                                <i class="fa-solid fa-xmark fs-2"></i>
                            </div>
                            <h2 class="fw-bold mb-1 text-white" style="font-size: 1.5rem;">TICKET CANCELLED</h2>
                            <p class="text-white-50 mb-0 small">This pass has been cancelled and is NOT valid for venue entry.</p>
                        </div>
                    @else
                        <!-- Pending Payment State -->
                        <div class="p-4 text-dark text-center" style="background: #FEF08A;">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-warning mb-2 shadow-sm" style="width: 58px; height: 58px;">
                                <i class="fa-solid fa-clock-rotate-left fs-2 text-warning"></i>
                            </div>
                            <h2 class="fw-bold mb-1" style="font-size: 1.5rem;">PAYMENT PENDING</h2>
                            <p class="text-muted mb-0 small">Payment verification is pending for this admission pass.</p>
                        </div>
                    @endif

                    <div class="card-body p-4 p-md-5 bg-white">
                        
                        <!-- Already Checked In Notice Banner -->
                        @if($details['verification_state'] === 'already_used')
                            <div class="p-3 mb-4 rounded-3 text-center" style="background: #FEF2F2; border: 1.5px solid #FCA5A5; color: #991B1B;">
                                <div class="fw-bold mb-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Pass Already Used
                                </div>
                                <div class="small">
                                    Checked in on: <strong>{{ $details['checked_in_at'] }}</strong>
                                    @if($details['checked_in_by'])
                                        &bull; Verified by staff: <strong>{{ $details['checked_in_by'] }}</strong>
                                    @endif
                                </div>
                                <div class="text-xs mt-1 text-muted">Double entry is strictly prevented to secure event capacity.</div>
                            </div>
                        @endif

                        <!-- Attendee Identity Box -->
                        <div class="p-4 mb-4 rounded-4 d-flex flex-column flex-sm-row align-items-center gap-4 text-center text-sm-start" style="background: #FAF5FF; border: 1.5px solid #E9D5FF;">
                            <!-- Photo / Avatar -->
                            <div class="flex-shrink-0">
                                @if(!empty($details['profile_photo_url']))
                                    <img src="{{ $details['profile_photo_url'] }}" alt="{{ $details['attendee'] }}" class="rounded-circle shadow" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid #6D1A95;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow" style="width: 90px; height: 90px; background: linear-gradient(135deg, #6D1A95 0%, #3B0764 100%); font-size: 30px; border: 3px solid #E9D5FF;">
                                        {{ $details['initials'] }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex-grow-1 overflow-hidden">
                                <span class="badge" style="background: #F3E8FF; color: #6D1A95; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 8px;">
                                    ATTENDEE CREDENTIALS
                                </span>
                                <h3 class="fw-bold text-dark mt-2 mb-1" style="font-size: 1.35rem;">
                                    {{ $details['attendee'] }}
                                </h3>

                                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mt-2">
                                    @if($details['is_student'])
                                        <span class="badge" style="background: #6D1A95; color: white; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 12px;">
                                            <i class="fa-solid fa-graduation-cap me-1"></i> {{ $details['category'] }}
                                        </span>
                                        @if($details['student_section'])
                                            <span class="badge" style="background: #F3E8FF; color: #6D1A95; font-size: 12.5px; font-weight: 800; padding: 5px 12px; border: 1.5px solid #8B5CF6; border-radius: 12px;">
                                                <i class="fa-solid fa-chalkboard-user me-1"></i> Section {{ $details['student_section'] }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge" style="background: #EEF2F6; color: #334155; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 12px;">
                                            <i class="fa-solid fa-user-check me-1"></i> {{ $details['category'] }}
                                        </span>
                                    @endif

                                    @if(!empty($details['company']))
                                        <span class="badge bg-light text-muted border" style="font-size: 11px;">
                                            <i class="fa-solid fa-building me-1"></i> {{ $details['company'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Reference & Seats -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Ticket Reference ID</div>
                                    <div class="fw-bold fs-5 text-dark font-monospace text-truncate" style="color: #6D1A95 !important;">
                                        {{ $details['reference'] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Admitted Seats</div>
                                    <div class="fw-bold fs-5 text-dark">
                                        {{ $details['seats'] }} {{ Str::plural('Person', $details['seats']) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Event Information -->
                        @if ($details['event'])
                            <div class="p-3 mb-4 rounded-3 border bg-light">
                                <h6 class="fw-bold mb-2 text-dark">
                                    <i class="fa-solid fa-calendar-check text-primary me-2"></i>{{ $details['event']['title'] }}
                                </h6>
                                <div class="row g-2 text-muted small">
                                    <div class="col-sm-6">
                                        <i class="fa-regular fa-calendar me-1"></i> {{ $details['event']['start_date'] }}
                                    </div>
                                    <div class="col-sm-6">
                                        <i class="fa-regular fa-clock me-1"></i> {{ $details['event']['start_time'] ?? '09:00 AM' }} EAT
                                    </div>
                                    @if ($details['event']['location'])
                                        <div class="col-12 mt-2">
                                            <i class="fa-solid fa-location-dot text-danger me-1"></i> <strong>{{ $details['event']['location'] }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Gate Staff Action Area -->
                        @if($isStaff)
                            <div class="p-4 rounded-4 border mt-4 text-center" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                                <div class="text-xs text-muted text-uppercase fw-bold mb-2">Gate Staff Entrance Control</div>
                                
                                @if($details['verification_state'] === 'valid')
                                    <form action="{{ route('events.bookings.verify', $details['reference']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-lg px-4 py-3 rounded-pill fw-bold w-100 shadow d-flex align-items-center justify-content-center gap-2">
                                            <i class="fa-solid fa-circle-check fs-4"></i>
                                            <span>Mark as Checked In (Confirm Entry)</span>
                                        </button>
                                    </form>
                                @elseif($details['verification_state'] === 'already_used')
                                    <button class="btn btn-secondary btn-lg px-4 py-3 rounded-pill fw-bold w-100 disabled" disabled>
                                        <i class="fa-solid fa-check-double me-2"></i> Entry Already Granted
                                    </button>
                                @else
                                    <button class="btn btn-danger btn-lg px-4 py-3 rounded-pill fw-bold w-100 disabled" disabled>
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Cannot Check In ({{ ucfirst($details['verification_state']) }})
                                    </button>
                                @endif

                                @if($details['event'] && !empty($details['event']['id']))
                                    <div class="mt-3">
                                        <a href="{{ route('admin.events.scanner', $details['event']['id']) }}" class="small text-decoration-none fw-semibold" style="color: #6D1A95;">
                                            <i class="fa-solid fa-camera me-1"></i> Return to Event Gate Scanner &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center pt-2 text-muted small">
                                <i class="fa-solid fa-shield me-1"></i> Verified by Fira Link Gate Authentication Protocol
                            </div>
                        @endif

                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('events.index') }}" class="btn btn-link text-decoration-none text-muted">
                        <i class="fa-solid fa-arrow-left me-1"></i> Return to Events
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
