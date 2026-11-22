@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Tours & Field Expeditions',
        'page_subtitle' => "Experience Ethiopia's legendary coffee origins, washing stations, and farm highlands firsthand.",
        'bg_image' => asset('assets/front/img/banners/fira_tours_destination.jpg'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<section class="tour-section">
    <div class="container">

        <!-- Filter & Search Panel -->
        <div class="fira-tour-filter-card">
            <form action="{{ route('tours.index') }}" method="GET" class="row g-3 align-items-end">
                <!-- Search Input -->
                <div class="col-lg-4 col-md-6 col-12">
                    <label class="fira-filter-label" for="tour-search-input">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Search Expeditions</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="tour-search-input" name="search" value="{{ request('search') }}" class="form-control" placeholder="Destination, keyword, or title...">
                    </div>
                </div>

                <!-- Category Dropdown -->
                <div class="col-lg-3 col-md-6 col-12">
                    <label class="fira-filter-label" for="tour-category-select">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Category</span>
                    </label>
                    <select id="tour-category-select" name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->category }}" {{ request('category') == $cat->category ? 'selected' : '' }}>
                                {{ $cat->category }} ({{ $cat->count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Difficulty Dropdown -->
                <div class="col-lg-2 col-md-4 col-12">
                    <label class="fira-filter-label" for="tour-difficulty-select">
                        <i class="fa-solid fa-mountain"></i>
                        <span>Difficulty</span>
                    </label>
                    <select id="tour-difficulty-select" name="difficulty" class="form-select">
                        <option value="">All Levels</option>
                        <option value="Easy" {{ request('difficulty') == 'Easy' ? 'selected' : '' }}>Easy</option>
                        <option value="Moderate" {{ request('difficulty') == 'Moderate' ? 'selected' : '' }}>Moderate</option>
                        <option value="Challenging" {{ request('difficulty') == 'Challenging' ? 'selected' : '' }}>Challenging</option>
                    </select>
                </div>

                <!-- Sort Dropdown -->
                <div class="col-lg-2 col-md-4 col-12">
                    <label class="fira-filter-label" for="tour-sort-select">
                        <i class="fa-solid fa-arrow-down-short-wide"></i>
                        <span>Sort By</span>
                    </label>
                    <select id="tour-sort-select" name="sort" class="form-select">
                        <option value="upcoming" {{ request('sort') == 'upcoming' ? 'selected' : '' }}>Upcoming Departures</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="duration" {{ request('sort') == 'duration' ? 'selected' : '' }}>Duration (Days)</option>
                    </select>
                </div>

                <!-- Submit & Reset Action Buttons -->
                <div class="col-lg-1 col-md-4 col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="fira-filter-btn flex-grow-1" title="Apply Filters">
                            <i class="fa-solid fa-filter"></i>
                            <span class="d-lg-none">Apply</span>
                        </button>
                        @if (request()->hasAny(['search', 'category', 'difficulty', 'sort']))
                            <a href="{{ route('tours.index') }}" class="fira-reset-btn" title="Reset Filters">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <!-- Quick Category Pill Tabs (1-Click Filtering) -->
            <div class="fira-tour-quick-cats">
                <a href="{{ route('tours.index') }}" class="fira-cat-pill {{ empty(request('category')) ? 'active' : '' }}">
                    <span>All Expeditions</span>
                    <span class="pill-count">({{ $categories->sum('count') }})</span>
                </a>
                @foreach ($categories as $cat)
                    <a href="{{ route('tours.index', array_merge(request()->query(), ['category' => $cat->category])) }}"
                       class="fira-cat-pill {{ request('category') == $cat->category ? 'active' : '' }}">
                        <span>{{ $cat->category }}</span>
                        <span class="pill-count">({{ $cat->count }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Active Filter Chips (if any filter is applied) -->
            @if (request()->hasAny(['search', 'category', 'difficulty']))
                <div class="fira-active-filters">
                    <span class="text-xs fw-bold text-muted text-uppercase me-1">Active Filters:</span>
                    @if (request('search'))
                        <span class="fira-chip">
                            <span>Keyword: "{{ request('search') }}"</span>
                            <a href="{{ route('tours.index', request()->except('search')) }}" title="Remove">&times;</a>
                        </span>
                    @endif
                    @if (request('category'))
                        <span class="fira-chip">
                            <span>Category: {{ request('category') }}</span>
                            <a href="{{ route('tours.index', request()->except('category')) }}" title="Remove">&times;</a>
                        </span>
                    @endif
                    @if (request('difficulty'))
                        <span class="fira-chip">
                            <span>Difficulty: {{ request('difficulty') }}</span>
                            <a href="{{ route('tours.index', request()->except('difficulty')) }}" title="Remove">&times;</a>
                        </span>
                    @endif
                    <a href="{{ route('tours.index') }}" class="fira-clear-all-link">Clear all</a>
                </div>
            @endif
        </div>

        <!-- Results Counter & Status Bar -->
        <div class="fira-tour-results-bar">
            <div class="fira-results-count">
                <span>Available Expeditions</span>
                <span class="badge">{{ $tours->total() }} {{ Str::plural('Expedition', $tours->total()) }}</span>
            </div>
            @if(request('category'))
                <span class="text-xs text-muted d-none d-md-inline">
                    Showing category: <strong>{{ request('category') }}</strong>
                </span>
            @endif
        </div>

        <!-- Tour Cards Grid / Empty State -->
        @if ($tours->count() === 0)
            <div class="text-center py-5 rounded-4 p-5 my-4 bg-white border" style="border-style: dashed !important; border-color: rgba(109,26,149,0.3) !important;">
                <i class="fa-solid fa-compass-slash fa-3x mb-3" style="color: #6D1A95;"></i>
                <h3 class="fw-bold mb-2" style="font-size: 1.35rem; color: #0F172A;">No Expeditions Found</h3>
                <p class="text-muted mb-4" style="font-size: 0.95rem; max-width: 520px; margin: 0 auto;">
                    We couldn't find any tours matching your current search parameters. Try adjusting your filters or explore all available expeditions.
                </p>
                <a href="{{ route('tours.index') }}" class="btn fira-filter-btn px-4" style="height: auto; padding: 12px 24px !important;">
                    <i class="fa-solid fa-rotate-right me-2"></i> Reset All Filters
                </a>
            </div>
        @else
            <div class="row g-4">
                @foreach ($tours as $tour)
                    @php
                        $remaining = $tour->remainingSeats();
                        $diffClass = match($tour->difficulty_level) {
                            'Easy' => 'fira-diff-easy',
                            'Moderate' => 'fira-diff-moderate',
                            'Challenging' => 'fira-diff-challenging',
                            default => 'bg-secondary text-white'
                        };
                    @endphp
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <div class="fira-tour-card">
                            <!-- Image & Overlaid Badges -->
                            <div class="fira-tour-media">
                                <img src="{{ $tour->featured_image_url }}" alt="{{ $tour->title }}" loading="lazy">
                                
                                <!-- Top Badges -->
                                <div class="fira-media-top">
                                    <div class="fira-badge-group">
                                        @if ($tour->category)
                                            <span class="fira-cat-badge">
                                                {{ $tour->category }}
                                            </span>
                                        @endif
                                        <span class="fira-diff-badge {{ $diffClass }}">
                                            {{ $tour->difficulty_level }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Bottom Badges: Duration & Seats -->
                                <div class="fira-media-bottom">
                                    <div class="fira-duration-pill">
                                        <i class="fa-regular fa-clock"></i>
                                        <span>{{ $tour->duration_days }} {{ Str::plural('Day', $tour->duration_days) }}</span>
                                    </div>

                                    @if ($remaining > 0)
                                        <div class="fira-seats-pill fira-seats-available">
                                            <i class="fa-solid fa-user-check"></i>
                                            <span>{{ $remaining }} left</span>
                                        </div>
                                    @else
                                        <div class="fira-seats-pill fira-seats-full">
                                            <i class="fa-solid fa-lock"></i>
                                            <span>Sold Out</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="fira-tour-body">
                                <!-- Destination -->
                                <div class="fira-tour-dest">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span class="text-truncate" title="{{ $tour->destination }}">{{ $tour->destination }}</span>
                                </div>

                                <!-- Tour Title -->
                                <h3 class="fira-tour-title">
                                    <a href="{{ route('tours.show', $tour->slug) }}" title="{{ $tour->title }}">
                                        {{ $tour->title }}
                                    </a>
                                </h3>

                                <!-- Tour Summary -->
                                <p class="fira-tour-summary">
                                    {{ $tour->summary ?? Str::limit(strip_tags($tour->description), 95) }}
                                </p>

                                <!-- Tour Spec Chips -->
                                <div class="fira-tour-spec-chips">
                                    @if ($tour->departure_time)
                                        <span class="fira-spec-chip">
                                            <i class="fa-regular fa-calendar-check"></i>
                                            <span>{{ $tour->departure_time }}</span>
                                        </span>
                                    @endif
                                    @if ($tour->meeting_point)
                                        <span class="fira-spec-chip text-truncate" style="max-width: 220px;" title="{{ $tour->meeting_point }}">
                                            <i class="fa-solid fa-map-pin"></i>
                                            <span class="text-truncate">{{ $tour->meeting_point }}</span>
                                        </span>
                                    @else
                                        <span class="fira-spec-chip">
                                            <i class="fa-solid fa-van-shuttle"></i>
                                            <span>Transport Included</span>
                                        </span>
                                    @endif
                                </div>

                                <!-- Card Footer: Price & Buttons -->
                                <div class="fira-tour-footer">
                                    <div class="fira-price-row">
                                        <div class="fira-price-col">
                                            <div class="price-label">Price per person</div>
                                            @if ($tour->price > 0)
                                                <div class="price-value">
                                                    {{ $tour->currency }} {{ number_format($tour->price, 2) }}
                                                </div>
                                            @else
                                                <div class="price-free">
                                                    <i class="fa-solid fa-gift me-1"></i> Free Entry
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="text-xs text-muted">
                                                Total Seats: <strong>{{ $tour->total_seats }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="fira-card-actions">
                                        <a href="{{ route('tours.show', $tour->slug) }}" class="btn-details">
                                            <span>Details</span>
                                            <i class="fa-solid fa-arrow-right" style="font-size: 0.75rem;"></i>
                                        </a>

                                        @if ($remaining > 0)
                                            <a href="{{ route('tours.book', $tour->slug) }}" class="btn-book">
                                                <i class="fa-solid fa-ticket" style="font-size: 0.75rem;"></i>
                                                <span>Book Now</span>
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
                {{ $tours->links('pagination::bootstrap-5') }}
            </div>
        @endif

        <!-- Why Learn Through Fira Link Expeditions (Features / Value Props) -->
        <div class="fira-tour-features-section">
            <div class="fira-features-header">
                <h3>Why Learn Through Fira Link Expeditions</h3>
                <p>
                    Connecting theoretical knowledge with live industrial environments, authentic coffee origins, and professional mentorship.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-mug-hot"></i>
                        </div>
                        <h4>Direct Industrial Access</h4>
                        <p>
                            Walk inside active coffee washing stations, modern export warehouses, and manufacturing clusters.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <h4>Distinguished Field Mentors</h4>
                        <p>
                            Learn alongside licensed agronomists, coffee Q-graders, university researchers, and industry specialists.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-shield-virus"></i>
                        </div>
                        <h4>Turnkey Safety &amp; Logistics</h4>
                        <p>
                            Comfortable 4WD transport, curated hotel accommodations, security clearances, and 24/7 coordinator assistance.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="fira-feature-card">
                        <div class="fira-feature-icon">
                            <i class="fa-solid fa-award"></i>
                        </div>
                        <h4>Executive Networking &amp; CPD</h4>
                        <p>
                            Build strategic connections with fellow professionals, corporate leaders, and earn verified field certificates.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Expeditions CTA Banner -->
        <div class="fira-tour-cta-banner">
            <div class="row align-items-center g-4">
                <div class="col-lg-8 col-12">
                    <div class="fira-cta-content">
                        <h3>Planning a Custom Group or Academic Delegation?</h3>
                        <p>
                            We tailor private educational field trips, corporate retreats, and diplomatic sector-study visits across Ethiopia for universities, NGOs, enterprise teams, and international delegations.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="fira-cta-actions">
                        <a href="{{ route('contact') }}" class="fira-cta-btn-primary">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Request Custom Tour</span>
                        </a>
                        <a href="{{ route('courses') }}" class="fira-cta-btn-outline">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>Explore Courses</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
