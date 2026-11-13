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

                                @if ($event->location_description)
                                    <h2 class="mt-5">{{ __('frontend.event_location') }}</h2>
                                    <p class="mt-2">{{ $event->location_description }}</p>
                                @endif

                                <ul class="location">
                                    <li>
                                        <i class="far fa-map-marker-alt"></i>
                                        {{ $event->location ?? __('frontend.venue_tba') }}
                                    </li>
                                    @if ($event->contact_phone)
                                        <li>
                                            <i class="far fa-phone"></i>
                                            <a href="tel:{{ $event->contact_phone }}">{{ $event->contact_phone }}</a>
                                        </li>
                                    @endif
                                    @if ($event->contact_email)
                                        <li>
                                            <i class="far fa-envelope"></i>
                                            <a href="mailto:{{ $event->contact_email }}">{{ $event->contact_email }}</a>
                                        </li>
                                    @endif
                                </ul>
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
                                </ul>
                                @if ($event->is_published)
                                    @auth
                                        <a href="{{ route('events.book', $event->slug) }}" class="theme-btn wow fadeInUp"
                                            data-wow-delay=".6s">{{ __('frontend.book_your_seat') }}</a>
                                    @else
                                        <a href="{{ route('login') }}" class="theme-btn style-2 wow fadeInUp"
                                            data-wow-delay=".6s">{{ __('frontend.login_to_book') }}</a>
                                    @endauth
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
