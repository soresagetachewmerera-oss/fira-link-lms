@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.event_details'),
        'base_url' => route('events.index'),
    ])
@endsection

@section('content')
    <section class="mhq-event-details-section section-padding">
        <div class="container">
            <div class="mhq-event-details-area">
                <div class="mhq-image wow fadeInUp" data-wow-delay=".3s">
                    @php
                        $featured = $event->featured_image
                            ? asset('storage/' . $event->featured_image)
                            : asset('assets/front/img/inner/event-details/01.jpg');
                    @endphp
                    <img src="{{ $featured }}" alt="{{ $event->title }}">
                </div>
                <div class="mhq-event-details-wrapper">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="details-content">
                                <h2>{{ $event->title }}</h2>
                                <p class="mt-2">{{ $event->summary }}</p>
                                <div class="mt-4">
                                    {!! nl2br(e($event->description)) !!}
                                </div>

                                @if ($event->highlights->isNotEmpty())
                                    <h2 class="mt-5">{{ __('frontend.what_you_experience') }}</h2>
                                    <ul class="offer-list">
                                        @foreach ($event->highlights as $highlight)
                                            <li>
                                                <i class="fal fa-check-circle"></i>
                                                {{ $highlight->title ? $highlight->title . ' – ' : '' }}{{ $highlight->description }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <!-- Event Venue & Interactive Location Section -->
                                <div class="fira-venue-section mt-5 p-4 rounded-4" style="background: #ffffff; border: 1.5px solid #E9D5FF; box-shadow: 0 4px 20px rgba(109,26,149,0.06);">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 mb-3 border-bottom">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="badge" style="background: #F3E8FF; color: #6D1A95; font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 20px;">
                                                    <i class="fa-solid fa-building me-1"></i> {{ $event->is_online ? 'Online Event' : 'In-Person Venue' }}
                                                </span>
                                                <span class="badge" style="background: #ECFDF5; color: #059669; font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 20px;">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Venue Confirmed
                                                </span>
                                            </div>
                                            <h3 class="fw-bold mb-1" style="color: #1E1B4B; font-size: 1.35rem;">
                                                {{ $event->location ?? 'Vamdas Cinema, Addis Ababa' }}
                                            </h3>
                                            <p class="text-muted mb-0" style="font-size: 13.5px;">
                                                <i class="fa-solid fa-location-dot me-1 text-danger"></i> Africa Avenue (Bole Road, near Olympia), Addis Ababa, Ethiopia
                                            </p>
                                        </div>
                                        <div>
                                            <a href="https://maps.app.goo.gl/ro2Q5YnH8B7Ybu9j6" target="_blank" rel="noopener noreferrer" class="theme-btn btn-sm d-inline-flex align-items-center gap-2 text-nowrap" style="padding: 10px 20px; font-size: 13.5px; border-radius: 12px;">
                                                <i class="fa-solid fa-diamond-turn-right"></i>
                                                <span>Open in Google Maps</span>
                                            </a>
                                        </div>
                                    </div>

                                    @if ($event->location_description)
                                        <div class="p-3 mb-3 rounded-3" style="background: #FAF5FF; border-left: 4px solid #8B5CF6;">
                                            <div class="text-dark small" style="line-height: 1.6;">
                                                <i class="fa-solid fa-circle-info text-primary me-1"></i> {{ $event->location_description }}
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Responsive Interactive Google Map Embed -->
                                    <div class="fira-map-container position-relative overflow-hidden rounded-3 shadow-sm" style="height: 380px; width: 100%; border: 1px solid #E5E7EB;">
                                        <iframe
                                            src="https://maps.google.com/maps?q=Vamdas+Cinema,+Addis+Ababa,+Ethiopia&t=&z=16&ie=UTF8&iwloc=&output=embed"
                                            width="100%"
                                            height="100%"
                                            style="border:0; position:absolute; top:0; left:0; width:100%; height:100%;"
                                            allowfullscreen=""
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"
                                            title="Vamdas Cinema Location Map">
                                        </iframe>
                                    </div>

                                    <!-- Venue Contact & Details -->
                                    <div class="row g-3 mt-2 pt-2 text-muted" style="font-size: 12.5px;">
                                        <div class="col-sm-4 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-door-open text-primary"></i>
                                            <span>Doors open at 08:30 AM EAT</span>
                                        </div>
                                        @if ($event->contact_phone)
                                            <div class="col-sm-4 d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-phone text-success"></i>
                                                <a href="tel:{{ $event->contact_phone }}" class="text-decoration-none text-muted">{{ $event->contact_phone }}</a>
                                            </div>
                                        @endif
                                        @if ($event->contact_email)
                                            <div class="col-sm-4 d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-envelope text-info"></i>
                                                <a href="mailto:{{ $event->contact_email }}" class="text-decoration-none text-muted">{{ $event->contact_email }}</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Confirmed Attendees & Participants Section -->
                                <div class="fira-attendees-section mt-5 p-4 rounded-4" style="background: #ffffff; border: 1.5px solid #E5E7EB; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 pb-3 mb-3 border-bottom">
                                        <div>
                                            <h3 class="fw-bold mb-0" style="color: #1E1B4B; font-size: 1.25rem;">
                                                <i class="fa-solid fa-users text-primary me-2"></i>Confirmed Attendees &amp; Exporters
                                            </h3>
                                            <small class="text-muted">Registered coffee &amp; pulses exporters, A8/A9/A10 cohort graduates attending in-person at Vamdas Cinema.</small>
                                        </div>
                                        <span class="badge rounded-pill px-3 py-2" style="background: #F3E8FF; color: #6D1A95; font-size: 12px; font-weight: 700;">
                                            {{ $event->confirmed_bookings_count ?? (isset($attendees) ? $attendees->count() : 0) }} Registered
                                        </span>
                                    </div>

                                    @if(auth()->check() && isset($userBooking) && $userBooking)
                                        <div class="alert p-3 mb-4 rounded-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3" style="background: linear-gradient(135deg, #ECFDF5, #F0FDF4); border: 1px solid #A7F3D0;">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #059669; color: #ffffff;">
                                                    <i class="fa-solid fa-ticket fs-5"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark" style="font-size: 14px;">You're Registered for Sunday, Dec 20 at Vamdas Cinema!</div>
                                                    <div class="text-muted" style="font-size: 12.5px;">
                                                        Booking Reference: <strong class="text-dark">{{ $userBooking->reference }}</strong> · Ticket: Free Admission
                                                        @if(!auth()->user()->hasProfilePhoto())
                                                            · <span class="text-danger fw-semibold"><i class="fa-solid fa-camera"></i> Please upload your real profile photo before Dec 20.</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if(!auth()->user()->hasProfilePhoto())
                                                <a href="{{ route('student.settings') }}" class="btn btn-sm btn-outline-success text-nowrap rounded-pill px-3" style="font-size: 12px;">
                                                    <i class="fa-solid fa-arrow-up-from-bracket me-1"></i> Upload Photo
                                                </a>
                                            @endif
                                        </div>
                                    @endif

                                    @if(isset($attendees) && $attendees->isNotEmpty())
                                        <div class="row g-3">
                                            @foreach($attendees as $att)
                                                @php
                                                    $u = $att->user;
                                                    $hasPhoto = $u && $u->hasProfilePhoto();
                                                    $photoUrl = $hasPhoto ? $u->profile_photo_url : null;
                                                    $initials = $u ? $u->initials : mb_strtoupper(mb_substr($att->first_name, 0, 1) . mb_substr($att->last_name, 0, 1));
                                                    $fullName = trim($att->first_name . ' ' . $att->last_name);
                                                @endphp
                                                <div class="col-md-6">
                                                    <div class="p-3 rounded-3 h-100 d-flex align-items-center gap-3" style="background: #FAFAFA; border: 1px solid #F1F5F9; transition: all 0.2s ease;">
                                                        <div class="position-relative flex-shrink-0">
                                                            @if($hasPhoto)
                                                                <img src="{{ $photoUrl }}" alt="{{ $fullName }}" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #8B5CF6;">
                                                            @else
                                                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, #6D1A95, #3B0764); font-size: 15px;">
                                                                    {{ $initials }}
                                                                </div>
                                                            @endif
                                                            <span class="position-absolute bottom-0 end-0 rounded-circle" style="width: 12px; height: 12px; background: #10B981; border: 2px solid #ffffff;"></span>
                                                        </div>
                                                        <div class="overflow-hidden flex-grow-1">
                                                            <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;" title="{{ $fullName }}">
                                                                {{ $fullName }}
                                                            </div>
                                                            <div class="text-muted text-truncate" style="font-size: 11.5px;" title="{{ $att->company ?? 'Coffee & Pulses Exporter' }}">
                                                                {{ $att->company ?? 'Coffee & Pulses Exporter' }}
                                                            </div>
                                                            <span class="badge mt-1" style="background: #F3E8FF; color: #6D1A95; font-size: 10px; font-weight: 600;">
                                                                Confirmed Seat
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-users fs-2 mb-2 d-block text-secondary"></i>
                                            <span>Seats are filling fast. Book your free seat below to be listed among confirmed attendees!</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="event-details-information sticky-style">
                                <h4 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.event_info') }}</h4>
                                <ul class="information-list wow fadeInUp" data-wow-delay=".4s">
                                    <li>
                                        <span><i class="fas fa-calendar-alt"></i>{{ __('frontend.start_date') }}</span>
                                        <span
                                            class="text">{{ optional($event->start_date)->format('d M Y') ?? __('frontend.date_tbd') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="fas fa-calendar-alt"></i>{{ __('frontend.end_date') }}</span>
                                        <span
                                            class="text">{{ optional($event->end_date)->format('d M Y') ?? __('frontend.same_day') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="far fa-clock"></i>{{ __('frontend.start_time') }}</span>
                                        <span
                                            class="text">{{ $event->start_time ? optional($event->start_time)->format('h:i A') : __('frontend.date_tbd') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="far fa-clock"></i>{{ __('frontend.end_time') }}</span>
                                        <span
                                            class="text">{{ $event->end_time ? optional($event->end_time)->format('h:i A') : __('frontend.date_tbd') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="far fa-money-bill-wave"></i>{{ __('frontend.ticket_price') }}</span>
                                        <span
                                            class="text color-2">{{ ($event->price ?? 0) > 0 ? currency_format($event->price ?? 0) : __('frontend.free') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="far fa-loveseat"></i>{{ __('frontend.total_seats') }}</span>
                                        <span class="text">{{ $event->total_seats ?? __('frontend.unlimited') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="fas fa-chart-pie"></i>{{ __('frontend.remaining_seats') }}</span>
                                        <span
                                            class="text">{{ $event->total_seats ? $event->remainingSeats() ?? 0 : __('frontend.unlimited') }}</span>
                                    </li>
                                    <li>
                                        <span><i class="fas fa-users"></i>{{ __('frontend.bookings') }}</span>
                                        <span class="text">
                                            {{ $event->bookings_count ?? 0 }} {{ __('frontend.total_count') }}
                                            @if (($event->confirmed_bookings_count ?? 0) > 0)
                                                · {{ $event->confirmed_bookings_count }} {{ __('frontend.confirmed') }}
                                            @endif
                                        </span>
                                    </li>
                                    <li>
                                        <span><i class="fas fa-layer-group"></i>Event Type</span>
                                        <span class="text fw-bold" style="color: #6D1A95;">
                                            <i class="fa-solid {{ !$event->is_online ? 'fa-building' : 'fa-video' }} me-1"></i>
                                            {{ !$event->is_online ? 'In-Person Gathering' : 'Online Event' }}
                                        </span>
                                    </li>
                                    <li>
                                        <span><i class="fas fa-map-pin"></i>Venue</span>
                                        <span class="text text-truncate" title="{{ $event->location ?? 'Vamdas Cinema, Addis Ababa' }}">
                                            {{ $event->location ? Str::limit($event->location, 24) : 'Vamdas Cinema' }}
                                        </span>
                                    </li>
                                </ul>
                                @if ($event->is_published)
                                    @auth
                                        <a href="{{ route('events.book', $event->slug) }}" class="theme-btn w-100 text-center wow fadeInUp"
                                            data-wow-delay=".6s">{{ __('frontend.book_your_seat') }}</a>
                                    @else
                                        <a href="{{ route('login') }}" class="theme-btn style-2 w-100 text-center wow fadeInUp"
                                            data-wow-delay=".6s">{{ __('frontend.login_to_book') }}</a>
                                    @endauth
                                    <div class="mt-3 text-center">
                                        <a href="https://maps.app.goo.gl/ro2Q5YnH8B7Ybu9j6" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold small" style="color: #6D1A95;">
                                            <i class="fa-solid fa-diamond-turn-right me-1"></i> Get Directions (Google Maps) &rarr;
                                        </a>
                                    </div>
                                @else
                                    <div class="alert alert-warning mt-3" role="alert">
                                        {{ __('frontend.event_draft_mode') }}
                                    </div>
                                @endif

                                @if ($countdownTarget)
                                    <div class="event-countdown" data-countdown="{{ $countdownTarget }}">
                                        <div class="event-countdown__card">
                                            <span class="event-countdown__value" data-countdown-part="days">00</span>
                                            <span class="event-countdown__label">{{ __('frontend.days') }}</span>
                                        </div>
                                        <div class="event-countdown__card">
                                            <span class="event-countdown__value" data-countdown-part="hours">00</span>
                                            <span class="event-countdown__label">{{ __('frontend.hours') }}</span>
                                        </div>
                                        <div class="event-countdown__card">
                                            <span class="event-countdown__value" data-countdown-part="minutes">00</span>
                                            <span class="event-countdown__label">{{ __('frontend.minutes') }}</span>
                                        </div>
                                        <div class="event-countdown__card">
                                            <span class="event-countdown__value" data-countdown-part="seconds">00</span>
                                            <span class="event-countdown__label">{{ __('frontend.seconds') }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($event->speakers->isNotEmpty())
        <section class="team-section fix section-padding pt-0">
            <div class="container">
                <div class="section-title text-center">
                    <h6 class="wow fadeInUp">{{ __('frontend.speakers') }}</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.event_speakers') }}</h2>
                </div>
                <div class="row">
                    @foreach ($event->speakers as $speaker)
                        <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp"
                            data-wow-delay=".{{ ($loop->iteration % 4) + 2 }}s">
                            <div class="team-box-items-4 style-2">
                                <div class="team-image">
                                    @php
                                        $speakerImage = $speaker->image_path
                                            ? asset('storage/' . $speaker->image_path)
                                            : asset(
                                                'assets/img/home-1/team/team-0' . (($loop->index % 4) + 1) . '.jpg',
                                            );
                                    @endphp
                                    <img src="{{ $speakerImage }}" alt="{{ $speaker->name }}">
                                </div>
                                <div class="team-content">
                                    <h4>{{ $speaker->name }}</h4>
                                    <p>{{ $speaker->title }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const countdownEl = document.querySelector('[data-countdown]');
                if (!countdownEl) {
                    return;
                }

                const targetIso = countdownEl.getAttribute('data-countdown');
                if (!targetIso) {
                    countdownEl.style.display = 'none';
                    return;
                }

                const parts = {
                    days: countdownEl.querySelector('[data-countdown-part="days"]'),
                    hours: countdownEl.querySelector('[data-countdown-part="hours"]'),
                    minutes: countdownEl.querySelector('[data-countdown-part="minutes"]'),
                    seconds: countdownEl.querySelector('[data-countdown-part="seconds"]'),
                };

                const pad = (num) => num.toString().padStart(2, '0');

                function updateCountdown() {
                    const target = new Date(targetIso).getTime();
                    if (Number.isNaN(target)) {
                        countdownEl.style.display = 'none';
                        return false;
                    }

                    const now = Date.now();
                    let distance = Math.max(0, target - now);

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    distance -= days * (1000 * 60 * 60 * 24);
                    const hours = Math.floor(distance / (1000 * 60 * 60));
                    distance -= hours * (1000 * 60 * 60);
                    const minutes = Math.floor(distance / (1000 * 60));
                    distance -= minutes * (1000 * 60);
                    const seconds = Math.floor(distance / 1000);

                    if (parts.days) parts.days.textContent = pad(days);
                    if (parts.hours) parts.hours.textContent = pad(hours);
                    if (parts.minutes) parts.minutes.textContent = pad(minutes);
                    if (parts.seconds) parts.seconds.textContent = pad(seconds);

                    return target - now > 0;
                }

                if (!updateCountdown()) {
                    return;
                }

                setInterval(updateCountdown, 1000);
            });
        </script>
    @endpush

@endsection
