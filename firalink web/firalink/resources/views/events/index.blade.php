@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Events & Masterclasses',
        'page_subtitle' => 'Connect with coffee leaders, export authorities, and global buyers at premier industry summits.',
        'bg_image' => asset('assets/front/img/banners/fira_events_live_gathering.jpg'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<section class="fira-event-section">
    <div class="container">
        <!-- Filter & Search Panel -->
        <div class="fira-event-filter-card">
            <form action="{{ route('events.index') }}" method="GET" class="row g-3 align-items-end">
                <!-- Search Input -->
                <div class="col-lg-5 col-md-6 col-12">
                    <label class="fira-filter-label" for="event-search-input">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Search Events &amp; Venues</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="event-search-input" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by conference, venue, keyword...">
                    </div>
                </div>

                <!-- Event Type (Free / Paid) -->
                <div class="col-lg-3 col-md-6 col-12">
                    <label class="fira-filter-label" for="event-pass-select">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Event Pass Type</span>
                    </label>
                    <select id="event-pass-select" name="type" class="form-select">
                        <option value="">All Event Passes</option>
                        <option value="free" {{ request('type') == 'free' ? 'selected' : '' }}>Free Entry &amp; Public Summits</option>
                        <option value="paid" {{ request('type') == 'paid' ? 'selected' : '' }}>VIP &amp; Paid Masterclasses</option>
                    </select>
                </div>

                <!-- Sort Dropdown -->
                <div class="col-lg-3 col-md-6 col-12">
                    <label class="fira-filter-label" for="event-sort-select">
                        <i class="fa-solid fa-arrow-down-short-wide"></i>
                        <span>Sort Schedule</span>
                    </label>
                    <select id="event-sort-select" name="sort" class="form-select">
                        <option value="upcoming" {{ request('sort') == 'upcoming' ? 'selected' : '' }}>Upcoming Dates First</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Ticket Price: Low to High</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Ticket Price: High to Low</option>
                    </select>
                </div>

                <!-- Submit & Reset Action Buttons -->
                <div class="col-lg-1 col-md-6 col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="fira-filter-btn flex-grow-1" title="Apply Filters">
                            <i class="fa-solid fa-filter"></i>
                            <span class="d-lg-none">Apply</span>
                        </button>
                        @if (request()->hasAny(['search', 'type', 'sort']))
                            <a href="{{ route('events.index') }}" class="fira-reset-btn" title="Reset Filters">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <!-- Quick Filter Pill Tabs (1-Click Filtering) -->
            <div class="fira-tour-quick-cats">
                <a href="{{ route('events.index') }}" class="fira-cat-pill {{ empty(request('type')) && empty(request('search')) ? 'active' : '' }}">
                    <span>All Events</span>
                    <span class="pill-count">({{ \App\Models\Event::published()->count() }})</span>
                </a>
                <a href="{{ route('events.index', ['type' => 'free']) }}" class="fira-cat-pill {{ request('type') == 'free' ? 'active' : '' }}">
                    <i class="fa-solid fa-gift text-success me-1"></i>
                    <span>Free Passes</span>
                </a>
                <a href="{{ route('events.index', ['type' => 'paid']) }}" class="fira-cat-pill {{ request('type') == 'paid' ? 'active' : '' }}">
                    <i class="fa-solid fa-star text-warning me-1"></i>
                    <span>VIP Masterclasses</span>
                </a>
                <a href="{{ route('events.index', ['search' => 'Coffee']) }}" class="fira-cat-pill {{ request('search') == 'Coffee' ? 'active' : '' }}">
                    <i class="fa-solid fa-mug-hot text-purple me-1"></i>
                    <span>Specialty Coffee</span>
                </a>
                <a href="{{ route('events.index', ['search' => 'Export']) }}" class="fira-cat-pill {{ request('search') == 'Export' ? 'active' : '' }}">
                    <i class="fa-solid fa-globe text-primary me-1"></i>
                    <span>Export &amp; Trade</span>
                </a>
            </div>

            <!-- Active Filter Chips -->
            @if (request()->hasAny(['search', 'type']))
                <div class="fira-active-filters">
                    <span class="text-xs fw-bold text-muted text-uppercase me-1">Active Filters:</span>
                    @if (request('search'))
                        <span class="fira-chip">
                            <span>Keyword: "{{ request('search') }}"</span>
                            <a href="{{ route('events.index', request()->except('search')) }}" title="Remove">&times;</a>
                        </span>
                    @endif
                    @if (request('type'))
                        <span class="fira-chip">
                            <span>Type: {{ ucfirst(request('type')) }}</span>
                            <a href="{{ route('events.index', request()->except('type')) }}" title="Remove">&times;</a>
                        </span>
                    @endif
                    <a href="{{ route('events.index') }}" class="fira-clear-all-link">Clear all</a>
                </div>
            @endif
        </div>

        <!-- Results Counter & Status Bar -->
        <div class="fira-tour-results-bar">
            <div class="fira-results-count">
                <span>Scheduled Events</span>
                <span class="badge">{{ $events->total() }} {{ Str::plural('Event', $events->total()) }} Available</span>
            </div>
            @if(request('search'))
                <span class="text-xs text-muted d-none d-md-inline">
                    Matching: <strong>"{{ request('search') }}"</strong>
                </span>
            @endif
        </div>

        <!-- Event Cards Grid / Empty State -->
        @if ($events->count() === 0)
            <div class="text-center py-5 rounded-4 p-5 my-4 bg-white border" style="border-style: dashed !important; border-color: rgba(109,26,149,0.3) !important;">
                <i class="fa-solid fa-calendar-xmark fa-3x mb-3" style="color: #6D1A95;"></i>
                <h3 class="fw-bold mb-2" style="font-size: 1.35rem; color: #0F172A;">No Events Found</h3>
                <p class="text-muted mb-4" style="font-size: 0.95rem; max-width: 520px; margin: 0 auto;">
                    We couldn't find any events matching your criteria. Try adjusting your search keywords or clear your filters to explore all upcoming programs.
                </p>
                <a href="{{ route('events.index') }}" class="btn fira-filter-btn px-4" style="height: auto; padding: 12px 24px !important;">
                    <i class="fa-solid fa-rotate-right me-2"></i> Reset Filters
                </a>
            </div>
        @else
            <div class="row g-4">
                @foreach ($events as $event)
                    @php
                        $remaining = $event->remainingSeats();
                        $isFree = $event->isFree();
                        $startDate = optional($event->start_date);
                        $formattedDate = $startDate ? $startDate->format('M d, Y') : 'Date TBA';
                        $month = $startDate ? $startDate->format('M') : 'TBA';
                        $day = $startDate ? $startDate->format('d') : '--';
                        $startTime = $event->start_time ? optional($event->start_time)->format('h:i A') : null;
                    @endphp
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <div class="fira-event-card">
                            <!-- Image Container & Date Overlay Tile -->
                            <div class="fira-event-media">
                                <img src="{{ $event->featured_image_url ?? asset('assets/front/img/event/event-1.jpg') }}" alt="{{ $event->title }}" loading="lazy">
                                
                                <!-- Top Bar Badges -->
                                <div class="fira-media-top">
                                    <!-- Calendar Date Tile -->
                                    <div class="fira-date-tile">
                                        <span class="date-month">{{ $month }}</span>
                                        <span class="date-day">{{ $day }}</span>
                                    </div>

                                    <!-- Price / Pass Pill -->
                                    @if ($isFree)
                                        <span class="fira-event-badge-free">
                                            <i class="fa-solid fa-gift me-1"></i> Free Pass
                                        </span>
                                    @else
                                        <span class="fira-event-badge-paid">
                                            <i class="fa-solid fa-ticket me-1"></i> VIP Masterclass
                                        </span>
                                    @endif
                                </div>

                                <!-- Bottom Bar: Time & Capacity -->
                                <div class="fira-media-bottom">
                                    @if ($startTime)
                                        <div class="fira-duration-pill">
                                            <i class="fa-regular fa-clock"></i>
                                            <span>{{ $startTime }}</span>
                                        </div>
                                    @else
                                        <div class="fira-duration-pill">
                                            <i class="fa-regular fa-calendar"></i>
                                            <span>Full Day</span>
                                        </div>
                                    @endif

                                    @if ($remaining !== null)
                                        @if ($remaining > 0)
                                            <div class="fira-seats-pill fira-seats-available">
                                                <i class="fa-solid fa-users"></i>
                                                <span>{{ $remaining }} seats left</span>
                                            </div>
                                        @else
                                            <div class="fira-seats-pill fira-seats-full">
                                                <i class="fa-solid fa-lock"></i>
                                                <span>Sold Out</span>
                                            </div>
                                        @endif
                                    @else
                                        <div class="fira-seats-pill fira-seats-available">
                                            <i class="fa-solid fa-circle-check"></i>
                                            <span>Open Registration</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="fira-event-body">
                                <!-- Location / Venue -->
                                <div class="fira-event-venue">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span class="text-truncate" title="{{ $event->location }}">{{ $event->location ?? 'Addis Ababa, Ethiopia' }}</span>
                                </div>

                                <!-- Title -->
                                <h3 class="fira-event-title">
                                    <a href="{{ route('events.show', $event->slug) }}" title="{{ $event->title }}">
                                        {{ $event->title }}
                                    </a>
                                </h3>

                                <!-- Summary -->
                                <p class="fira-event-summary">
                                    {{ $event->summary ?? Str::limit(strip_tags($event->description), 95) }}
                                </p>

                                <!-- Event Spec Chips -->
                                <div class="fira-event-spec-chips">
                                    <span class="fira-spec-chip">
                                        <i class="fa-regular fa-calendar-days"></i>
                                        <span>{{ $formattedDate }}</span>
                                    </span>
                                    @if ($event->speakers_count ?? $event->speakers->count())
                                        <span class="fira-spec-chip">
                                            <i class="fa-solid fa-microphone"></i>
                                            <span>{{ $event->speakers_count ?? $event->speakers->count() }} Keynotes</span>
                                        </span>
                                    @endif
                                    @if ($event->total_seats)
                                        <span class="fira-spec-chip">
                                            <i class="fa-solid fa-chair"></i>
                                            <span>{{ $event->total_seats }} Seats</span>
                                        </span>
                                    @endif
                                </div>

                                <!-- Card Footer: Price & Buttons -->
                                <div class="fira-event-footer">
                                    <div class="fira-price-row">
                                        <div class="fira-price-col">
                                            <div class="price-label">Registration</div>
                                            @if (!$isFree && $event->price > 0)
                                                <div class="price-value">
                                                    {{ $event->currency ?? 'ETB' }} {{ number_format($event->price, 2) }}
                                                </div>
                                            @else
                                                <div class="price-free">
                                                    <i class="fa-solid fa-check-circle me-1"></i> Complimentary
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="text-xs text-muted">
                                                Verified Ticket
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="fira-card-actions">
                                        <a href="{{ route('events.show', $event->slug) }}" class="btn-details">
                                            <span>Details</span>
                                            <i class="fa-solid fa-arrow-right" style="font-size: 0.75rem;"></i>
                                        </a>

                                        @if ($remaining === null || $remaining > 0)
                                            <a href="{{ route('events.book', $event->slug) }}" class="btn-book">
                                                <i class="fa-solid fa-ticket" style="font-size: 0.75rem;"></i>
                                                <span>{{ $isFree ? 'Register Free' : 'Book Ticket' }}</span>
                                            </a>
                                        @else
                                            <button disabled class="btn-soldout">
                                                <i class="fa-solid fa-ban" style="font-size: 0.75rem;"></i>
                                                <span>Sold Out</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="fira-pagination-wrapper mt-5 d-flex justify-content-center">
                {{ $events->links('pagination::bootstrap-5') }}
            </div>
        @endif

        <!-- Why Attend Fira Link Summits & Events (Value Props) -->
        <div class="fira-tour-features-section">
            <div class="fira-features-header">
                <h3>Why Attend Fira Link Summits &amp; Masterclasses</h3>
                <p>
                    Bringing together researchers, certified Q-graders, coffee exporters, and government leaders for direct knowledge exchange.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-flask-vial"></i>
                        </div>
                        <h4>Live Sensory &amp; Cupping Labs</h4>
                        <p>
                            Participate in live calibration cuppings, green grading sessions, and roast profiling workshops.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h4>Accredited CPD Certificates</h4>
                        <p>
                            Gain recognized continuing professional development credentials upon completing technical sessions.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-handshake-angle"></i>
                        </div>
                        <h4>Executive Trade Networking</h4>
                        <p>
                            Engage directly with international importers, cooperative leaders, investors, and agribusiness founders.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-shield-check"></i>
                        </div>
                        <h4>Instant QR Digital Pass</h4>
                        <p>
                            Seamless check-in with your unique verifiable QR digital boarding pass and automated confirmation.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Host an Event / Corporate Partnership CTA Banner -->
        <div class="fira-tour-cta-banner">
            <div class="row align-items-center g-4">
                <div class="col-lg-8 col-12">
                    <div class="fira-cta-content">
                        <h3>Host an Event or Partner With Fira Link Platform</h3>
                        <p>
                            Looking to host a sector symposium, corporate masterclass, or cupping competition? Utilize our state-of-the-art training auditoriums, coffee sensory laboratories, and hybrid streaming infrastructure.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="fira-cta-actions">
                        <a href="{{ route('contact') }}" class="fira-cta-btn-primary">
                            <i class="fa-solid fa-handshake"></i>
                            <span>Partner With Us</span>
                        </a>
                        <a href="{{ route('tours.index') }}" class="fira-cta-btn-outline">
                            <i class="fa-solid fa-compass"></i>
                            <span>Explore Field Tours</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
