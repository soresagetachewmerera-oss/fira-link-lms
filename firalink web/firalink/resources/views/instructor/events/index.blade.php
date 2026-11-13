@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('instructor.events') }}</h6>
                            <h2>{{ __('instructor.your_events') }}</h2>
                            <p>{{ __('instructor.events_subtitle') }}</p>
                        </div>
                        <div class="dashboard-header-actions">
                            <a href="{{ route('instructor.events.create') }}" class="theme-btn">
                                <span>{{ __('instructor.create_event') }}</span>
                                <i class="fa-solid fa-plus"></i>
                            </a>
                        </div>
                    </div>

                    <div class="dashboard-courses-section">
                        <div class="row">
                            @forelse($events as $index => $event)
                                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp"
                                    data-wow-delay="{{ ($index % 3) * 0.2 }}s">
                                    <div class="mhq-courses-box-items event-card">
                                        <div class="mhq-courses-image">
                                            <img src="{{ $event['image_url'] }}" alt="{{ $event['title'] }}">
                                            <span class="post-box">{{ $event['start_date_badge'] }}</span>
                                            <span
                                                class="course-status-badge {{ $event['status_badge'] === 'Published' ? 'published' : 'draft' }}">
                                                {{ $event['status_badge'] }}
                                            </span>
                                        </div>
                                        <div class="mhq-courses-content">
                                            <h3><a href="{{ route('events.show', $event['slug']) }}"
                                                    target="_blank">{{ $event['title'] }}</a></h3>
                                            <p class="event-summary">{{ $event['summary'] }}</p>

                                            <ul class="post-date">
                                                <li>
                                                    <span class="icon"><i class="fa-regular fa-clock"></i></span>
                                                    {{ $event['start_time_label'] }}
                                                </li>
                                                <li>
                                                    <span class="icon"><i class="fa-regular fa-user-group"></i></span>
                                                    {{ __('instructor.seats') }} {{ $event['seats_label'] }}
                                                </li>
                                            </ul>

                                            <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                                                <div class="booking-chip">
                                                    <span class="chip-label">{{ __('instructor.total') }}</span>
                                                    <span class="chip-value">{{ $event['bookings_total'] }}</span>
                                                </div>
                                                <div class="booking-chip">
                                                    <span class="chip-label">{{ __('instructor.confirmed') }}</span>
                                                    <span class="chip-value">{{ $event['bookings_confirmed'] }}</span>
                                                </div>
                                                <div class="booking-chip">
                                                    <span class="chip-label">{{ __('instructor.remaining') }}</span>
                                                    <span class="chip-value">{{ $event['remaining_label'] }}</span>
                                                </div>
                                                <div class="booking-chip">
                                                    <span class="chip-label">{{ __('instructor.status_col') }}</span>
                                                    <span class="chip-value {{ $event['status_chip_class'] }}">
                                                        {{ $event['status_chip_label'] }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="course-actions events-card-instructor mt-4" style="gap: 12px;">
                                                <a href="{{ $event['builder_url'] }}" class="theme-btn py-3">
                                                    {{ __('instructor.builder') }}
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="{{ $event['bookings_url'] }}" class="theme-btn style-2 py-3">
                                                    {{ __('instructor.bookings') }}
                                                    <i class="fa-solid fa-users"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="dashboard-empty-state">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                        <h3>{{ __('instructor.no_events_yet') }}</h3>
                                        <p>{{ __('instructor.create_first_event_desc') }}</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-3">
                        {{ $events->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
