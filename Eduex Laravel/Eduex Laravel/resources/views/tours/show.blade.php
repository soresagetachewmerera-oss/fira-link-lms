@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $tour->title,
        'base_url' => route('tours.index'),
    ])
@endsection

@section('content')
<section class="tour-details-section section-padding py-5">
    <div class="container">
        <!-- Top Flash / Alerts -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Left Main Column -->
            <div class="col-lg-8">
                <!-- Cover Image & Gallery -->
                <div class="card border-0 rounded-4 overflow-hidden shadow-sm mb-4">
                    <div class="position-relative" style="height: 380px;">
                        <img src="{{ $tour->featured_image_url }}" alt="{{ $tour->title }}" class="w-100 h-100" style="object-fit: cover;">
                        <div class="position-absolute top-0 start-0 m-3 d-flex gap-2">
                            @if ($tour->category)
                                <span class="badge bg-dark bg-opacity-75 text-white px-3 py-2 rounded-pill text-xs fw-semibold">
                                    {{ $tour->category }}
                                </span>
                            @endif
                            <span class="badge bg-primary px-3 py-2 rounded-pill text-xs fw-semibold">
                                {{ $tour->difficulty_level }}
                            </span>
                        </div>
                    </div>

                    @if(!empty($tour->gallery) && count($tour->gallery) > 1)
                        <div class="p-3 bg-light d-flex gap-2 overflow-auto border-top">
                            @foreach($tour->gallery as $img)
                                <img src="{{ str_starts_with($img, 'http') ? $img : asset($img) }}" alt="Gallery" class="rounded-3 border" style="width: 90px; height: 60px; object-fit: cover;">
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Key Quick Info Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-3 col-6">
                        <div class="p-3 rounded-4 bg-white border text-center shadow-xs">
                            <i class="fa-regular fa-clock fa-lg mb-1" style="color: #6D1A95;"></i>
                            <div class="text-xs text-muted">Duration</div>
                            <div class="fw-bold text-sm">{{ $tour->duration_days }} {{ Str::plural('Day', $tour->duration_days) }}</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-3 rounded-4 bg-white border text-center shadow-xs">
                            <i class="fa-solid fa-location-dot fa-lg mb-1" style="color: #6D1A95;"></i>
                            <div class="text-xs text-muted">Destination</div>
                            <div class="fw-bold text-sm text-truncate" title="{{ $tour->destination }}">{{ $tour->destination }}</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-3 rounded-4 bg-white border text-center shadow-xs">
                            <i class="fa-solid fa-users fa-lg mb-1" style="color: #6D1A95;"></i>
                            <div class="text-xs text-muted">Available Seats</div>
                            <div class="fw-bold text-sm text-success">{{ $remainingSeats }} of {{ $tour->total_seats }}</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-3 rounded-4 bg-white border text-center shadow-xs">
                            <i class="fa-solid fa-mountain fa-lg mb-1" style="color: #6D1A95;"></i>
                            <div class="text-xs text-muted">Difficulty</div>
                            <div class="fw-bold text-sm">{{ $tour->difficulty_level }}</div>
                        </div>
                    </div>
                </div>

                <!-- Overview -->
                <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
                    <h2 class="fw-bold mb-3" style="font-size: 1.25rem;">Expedition Overview</h2>
                    <div class="text-muted" style="line-height: 1.7; font-size: 0.95rem; white-space: pre-line;">
                        {{ $tour->description }}
                    </div>

                    @if ($tour->meeting_point)
                        <div class="mt-4 p-3 rounded-3 bg-light border-start border-4 border-purple" style="border-left-color: #6D1A95 !important;">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-map-pin me-2" style="color: #6D1A95;"></i>
                                <div>
                                    <span class="fw-bold text-sm">Meeting Point:</span>
                                    <span class="text-sm text-muted ms-1">{{ $tour->meeting_point }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Itinerary Accordion -->
                @if (!empty($tour->itinerary) && count($tour->itinerary) > 0)
                    <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
                        <h2 class="fw-bold mb-3" style="font-size: 1.25rem;">Day-by-Day Itinerary</h2>
                        <div class="accordion" id="itineraryAccordion">
                            @foreach ($tour->itinerary as $index => $step)
                                <div class="accordion-item border rounded-3 mb-2.5 overflow-hidden">
                                    <h3 class="accordion-header" id="heading{{ $index }}">
                                        <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }} fw-bold text-sm py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                                            <span class="badge me-2.5 px-2 py-1 text-xs" style="background-color: #6D1A95; color: #fff;">
                                                Day {{ $step['day'] ?? ($index + 1) }}
                                            </span>
                                            {{ $step['title'] ?? 'Daily Program' }}
                                        </button>
                                    </h3>
                                    <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#itineraryAccordion">
                                        <div class="accordion-body text-muted text-sm pt-2 pb-3" style="line-height: 1.6;">
                                            {{ $step['description'] ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Inclusions & Exclusions -->
                <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
                    <h2 class="fw-bold mb-3" style="font-size: 1.25rem;">Included Services &amp; Exclusions</h2>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h3 class="fw-semibold text-sm mb-2.5 text-success d-flex align-items-center">
                                <i class="fa-solid fa-circle-check me-2"></i> What's Included
                            </h3>
                            <ul class="list-unstyled mb-0">
                                @if(!empty($tour->included_services))
                                    @foreach($tour->included_services as $inc)
                                        <li class="text-sm text-muted mb-2 d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1" style="font-size: 0.75rem;"></i>
                                            <span>{{ $inc }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="text-sm text-muted">All scheduled field activities and professional guides.</li>
                                @endif
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h3 class="fw-semibold text-sm mb-2.5 text-danger d-flex align-items-center">
                                <i class="fa-solid fa-circle-xmark me-2"></i> What's Excluded
                            </h3>
                            <ul class="list-unstyled mb-0">
                                @if(!empty($tour->exclusions))
                                    @foreach($tour->exclusions as $exc)
                                        <li class="text-sm text-muted mb-2 d-flex align-items-start">
                                            <i class="fa-solid fa-xmark text-danger me-2 mt-1" style="font-size: 0.75rem;"></i>
                                            <span>{{ $exc }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="text-sm text-muted">Personal expenses and travel insurance.</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Requirements & Policies -->
                <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
                    <h2 class="fw-bold mb-3" style="font-size: 1.25rem;">Requirements &amp; Cancellation Policy</h2>
                    @if(!empty($tour->requirements))
                        <div class="mb-3">
                            <h3 class="fw-semibold text-sm mb-2">Participant Requirements:</h3>
                            <ul class="text-sm text-muted ps-3 mb-0">
                                @foreach($tour->requirements as $req)
                                    <li class="mb-1">{{ $req }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($tour->cancellation_policy)
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="fw-bold text-xs text-uppercase text-muted d-block mb-1">Cancellation Terms</span>
                            <p class="text-sm text-muted mb-0">{{ $tour->cancellation_policy }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Sticky Booking Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <div class="card border-0 rounded-4 shadow-sm p-4 bg-white border">
                        <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Price per participant</div>
                        <div class="d-flex align-items-baseline gap-2 mb-3">
                            <span class="fw-bold" style="font-size: 1.6rem; color: #6D1A95;">
                                @if($tour->price > 0)
                                    {{ $tour->currency }} {{ number_format($tour->price, 2) }}
                                @else
                                    <span class="text-success">Free Entry</span>
                                @endif
                            </span>
                            <span class="text-xs text-muted">/ person</span>
                        </div>

                        <!-- Availability Pill -->
                        <div class="p-3 rounded-3 mb-4 {{ $remainingSeats > 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-sm">
                                    <i class="fa-solid fa-ticket me-1.5"></i>
                                    {{ $remainingSeats > 0 ? 'Seats Available' : 'Sold Out' }}
                                </span>
                                <span class="badge {{ $remainingSeats > 0 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $remainingSeats }} seats left
                                </span>
                            </div>
                        </div>

                        <!-- Key Schedule Dates -->
                        @if(!empty($tour->available_schedules))
                            <div class="mb-4">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted mb-2">Available Departure Schedules</label>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($tour->available_schedules as $sched)
                                        <div class="p-2.5 rounded-3 bg-light border d-flex justify-content-between align-items-center text-xs">
                                            <div>
                                                <i class="fa-regular fa-calendar me-1.5 text-muted"></i>
                                                <strong>{{ \Carbon\Carbon::parse($sched['date'])->format('M d, Y') }}</strong>
                                            </div>
                                            <span class="text-muted">{{ $sched['time'] ?? '08:00 AM' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($tour->start_date)
                            <div class="mb-4 p-2.5 rounded-3 bg-light border text-xs">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Departure Date:</span>
                                    <strong>{{ optional($tour->start_date)->format('M d, Y') }}</strong>
                                </div>
                                @if($tour->departure_time)
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Departure Time:</span>
                                        <strong>{{ $tour->departure_time }}</strong>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- CTA Button -->
                        @if($remainingSeats > 0)
                            <a href="{{ route('tours.book', $tour->slug) }}" class="btn text-white w-100 py-3 rounded-3 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2" style="background-color: #6D1A95;">
                                <span>Proceed to Book</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @else
                            <button disabled class="btn btn-secondary w-100 py-3 rounded-3 fw-bold opacity-50">
                                Registration Closed
                            </button>
                        @endif

                        <div class="text-center mt-3">
                            <span class="text-xs text-muted">
                                <i class="fa-solid fa-shield-halved me-1 text-success"></i> Secure booking with verified seat reservation
                            </span>
                        </div>
                    </div>

                    <!-- Contact & Assistance -->
                    <div class="card border-0 rounded-4 shadow-sm p-3 mt-3 bg-light border text-center">
                        <span class="text-xs text-muted d-block mb-1">Need corporate or customized group bookings?</span>
                        <a href="{{ route('contact') }}" class="text-xs fw-semibold" style="color: #6D1A95;">
                            Contact Fira Link Expeditions Team &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
