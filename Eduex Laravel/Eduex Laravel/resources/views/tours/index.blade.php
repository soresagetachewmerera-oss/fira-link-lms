@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Tours & Field Expeditions',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<section class="tour-section section-padding fix py-5">
    <div class="container">
        <!-- Filter & Search Bar -->
        <div class="tour-filter-bar mb-4 p-4 rounded-4 shadow-sm bg-white border">
            <form action="{{ route('tours.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label text-xs fw-semibold text-uppercase text-muted mb-1">Search Expeditions</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Destination, keyword, or title...">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-xs fw-semibold text-uppercase text-muted mb-1">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->category }}" {{ request('category') == $cat->category ? 'selected' : '' }}>
                                {{ $cat->category }} ({{ $cat->count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label text-xs fw-semibold text-uppercase text-muted mb-1">Difficulty</label>
                    <select name="difficulty" class="form-select">
                        <option value="">All Levels</option>
                        <option value="Easy" {{ request('difficulty') == 'Easy' ? 'selected' : '' }}>Easy</option>
                        <option value="Moderate" {{ request('difficulty') == 'Moderate' ? 'selected' : '' }}>Moderate</option>
                        <option value="Challenging" {{ request('difficulty') == 'Challenging' ? 'selected' : '' }}>Challenging</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label text-xs fw-semibold text-uppercase text-muted mb-1">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="upcoming" {{ request('sort') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="duration" {{ request('sort') == 'duration' ? 'selected' : '' }}>Duration</option>
                    </select>
                </div>

                <div class="col-lg-1 col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary" style="background-color: #6D1A95; border-color: #6D1A95; height: 38px;">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>

        @if ($tours->count() === 0)
            <div class="text-center py-5 card-box rounded-4 p-5 my-4" style="background: #fdfdfd; border: 2px dashed rgba(109,26,149,0.15);">
                <i class="fa-solid fa-route fa-3x mb-3" style="color: #6D1A95;"></i>
                <h3 class="fw-bold" style="font-size: 1.25rem;">No Tours Found</h3>
                <p class="text-muted" style="font-size: 0.9375rem;">Try adjusting your search filters or check back soon for new field expeditions.</p>
                <a href="{{ route('tours.index') }}" class="btn btn-outline-secondary mt-2 px-4" style="border-radius: 8px;">Reset Filters</a>
            </div>
        @else
            <div class="row g-4">
                @foreach ($tours as $tour)
                    @php
                        $remaining = $tour->remainingSeats();
                        $badgeColor = match($tour->difficulty_level) {
                            'Easy' => 'bg-success',
                            'Moderate' => 'bg-primary',
                            'Challenging' => 'bg-warning text-dark',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="tour-card card h-100 border-0 rounded-4 overflow-hidden shadow-sm transition-all" style="transition: transform 0.2s, box-shadow 0.2s;">
                            <!-- Image Container -->
                            <div class="position-relative overflow-hidden" style="height: 220px; background: #222;">
                                <img src="{{ $tour->featured_image_url }}" alt="{{ $tour->title }}" class="w-100 h-100 object-fit-cover" style="object-fit: cover;">
                                
                                <!-- Top Badges -->
                                <div class="position-absolute top-0 start-0 m-3 d-flex gap-2">
                                    @if ($tour->category)
                                        <span class="badge bg-dark bg-opacity-75 text-white px-2.5 py-1.5 rounded-pill text-xs fw-semibold backdrop-blur">
                                            {{ $tour->category }}
                                        </span>
                                    @endif
                                    <span class="badge {{ $badgeColor }} px-2.5 py-1.5 rounded-pill text-xs fw-semibold">
                                        {{ $tour->difficulty_level }}
                                    </span>
                                </div>

                                <!-- Duration Pill -->
                                <div class="position-absolute bottom-0 start-0 m-3">
                                    <span class="badge bg-white text-dark shadow-sm px-2.5 py-1.5 rounded-pill text-xs fw-bold">
                                        <i class="fa-regular fa-clock me-1 text-muted"></i> {{ $tour->duration_days }} {{ Str::plural('Day', $tour->duration_days) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="card-body p-4 d-flex flex-column">
                                <!-- Destination -->
                                <div class="text-xs fw-semibold text-uppercase text-muted mb-2 d-flex align-items-center">
                                    <i class="fa-solid fa-location-dot me-1.5" style="color: #6D1A95;"></i>
                                    <span>{{ $tour->destination }}</span>
                                </div>

                                <!-- Title (clamped for perfect balance) -->
                                <h3 class="card-title fw-bold mb-2" style="font-size: 1.05rem; line-height: 1.35; height: 43px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <a href="{{ route('tours.show', $tour->slug) }}" class="text-dark text-decoration-none hover-purple">
                                        {{ $tour->title }}
                                    </a>
                                </h3>

                                <!-- Summary -->
                                <p class="text-muted text-sm mb-3 flex-grow-1" style="font-size: 0.85rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $tour->summary ?? Str::limit(strip_tags($tour->description), 90) }}
                                </p>

                                <!-- Divider -->
                                <hr class="my-2.5 opacity-10">

                                <!-- Footer: Price & Seats -->
                                <div class="d-flex align-items-center justify-content-between pt-1">
                                    <div>
                                        <div class="text-xs text-muted">Price per person</div>
                                        <div class="fw-bold" style="font-size: 1.1rem; color: #6D1A95;">
                                            @if ($tour->price > 0)
                                                {{ $tour->currency }} {{ number_format($tour->price, 2) }}
                                            @else
                                                <span class="text-success">Free Entry</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        @if ($remaining > 0)
                                            <div class="badge bg-light text-dark border px-2 py-1 text-xs">
                                                <i class="fa-solid fa-users me-1 text-muted"></i> {{ $remaining }} seats left
                                            </div>
                                        @else
                                            <div class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 text-xs fw-semibold">
                                                Fully Booked
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-3 pt-2 d-flex gap-2">
                                    <a href="{{ route('tours.show', $tour->slug) }}" class="btn btn-outline-secondary w-50 py-2 text-xs fw-semibold rounded-3">
                                        Details
                                    </a>
                                    @if ($remaining > 0)
                                        <a href="{{ route('tours.book', $tour->slug) }}" class="btn text-white w-50 py-2 text-xs fw-semibold rounded-3" style="background-color: #6D1A95;">
                                            Book Now
                                        </a>
                                    @else
                                        <button disabled class="btn btn-secondary w-50 py-2 text-xs fw-semibold rounded-3 opacity-50">
                                            Sold Out
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-5">
                {{ $tours->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</section>
@endsection
