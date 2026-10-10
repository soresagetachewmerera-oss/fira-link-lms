@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title wow fadeInUp">
                        <h6>{{ __('instructor.instructor_dashboard') }}</h6>
                        <h2>{{ __('instructor.welcome_back', ['name' => explode(' ', $user->name)[0]]) }}</h2>
                        <p>{{ __('instructor.performance_subtitle') }}</p>
                    </div>


                    <div class="dashboard-stats-minimal">
                        @foreach ($statCards as $card)
                            <div class="stat-card-minimal">
                                <div class="stat-icon-minimal {{ $card['class'] }}">
                                    <i class="{{ $card['icon'] }}"></i>
                                </div>
                                <div class="stat-content-minimal">
                                    <div class="stat-value-minimal">{{ $card['value'] }}</div>
                                    <div class="stat-label-minimal">{{ $card['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="section-title-area wow fadeInUp section-title-area-top section-title-area-spacing">
                        <div class="section-title">
                            <h6>{{ __('instructor.activity_feed') }}</h6>
                            <h2>{{ __('instructor.recent_activity') }}</h2>
                        </div>
                    </div>

                    <div class="dashboard-content-section">
                        @if ($activities->isEmpty())
                            <div class="dashboard-empty-state d-flex flex-column align-items-center justify-content-center">
                                <i class="fa-solid fa-wand-magic-sparkles text-center"></i>
                                <h3 class="text-center">{{ __('instructor.no_activity_yet') }}</h3>
                                <p class="text-center">{{ __('instructor.no_activity_desc') }}</p>
                            </div>
                        @else
                            <ul class="dashboard-activity">
                                @foreach ($activities as $activity)
                                    <li class="dashboard-activity-item">
                                        <div class="dashboard-activity-icon">
                                            <i class="{{ $activity['icon'] }}"></i>
                                        </div>
                                        <div class="dashboard-activity-content">
                                            <h5>{{ $activity['title'] }}</h5>
                                            <p>{{ $activity['description'] }}</p>
                                        </div>
                                        <div class="dashboard-activity-time">{{ $activity['relative_time'] ?? __('instructor.just_now') }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="dashboard-courses-section">
                        <div class="section-title-area wow fadeInUp section-title-area-spacing">
                            <div class="section-title">
                                <h6>{{ __('instructor.recent_courses') }}</h6>
                                <h2>{{ __('instructor.my_popular_courses') }}</h2>
                            </div>
                        </div>

                        <div class="row">
                            @forelse($recentCourses as $course)
                                @php
                                    $courseImage = $course->featured_image
                                        ? asset('storage/' . $course->featured_image)
                                        : asset('assets/front/img/home-1/courses/courses-01.jpg');
                                    $lessonsCount = $course->topics->sum(fn($topic) => $topic->lessons->count());
                                    $price = $course->sale_price ?? ($course->regular_price ?? 0);
                                    $difficulty = $course->difficulty ?: 'Course';
                                @endphp
                                <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp"
                                    @if (!$loop->first) data-wow-delay=".{{ $loop->iteration * 2 }}s" @endif>
                                    <div class="mhq-courses-box-items">
                                        <div class="mhq-courses-image">
                                            <img src="{{ $courseImage }}" alt="{{ $course->title }}">
                                            <span class="post-box">{{ $difficulty }}</span>
                                            @if ($course->is_live_course)
                                                <span class="post-box" style="left: auto; right: 20px; background-color: #dc3545; border-color: #dc3545;">
                                                    <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mhq-courses-content">
                                            <div class="star">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @php $ratingValue = $course->average_rating ? round($course->average_rating * 2) / 2 : 0; @endphp
                                                    @if ($ratingValue >= $i)
                                                        <i class="fa-solid fa-star"></i>
                                                    @elseif($ratingValue >= $i - 0.5)
                                                        <i class="fa-solid fa-star-half-stroke"></i>
                                                    @else
                                                        <i class="fa-regular fa-star"></i>
                                                    @endif
                                                @endfor
                                                <span class="star-average">
                                                    {{ $course->average_rating ? number_format($course->average_rating, 1) : '—' }}
                                                </span>
                                            </div>
                                            <h3>
                                                <a href="{{ route('instructor.courses.edit', $course->id) }}">
                                                    {{ $course->title }}
                                                </a>
                                            </h3>
                                            <ul class="post-date">
                                                <li>
                                                    <div class="icon">
                                                        <i class="fa-regular fa-circle-play"></i>
                                                    </div>
                                                    {{ $lessonsCount }} {{ __('instructor.lessons') }}
                                                </li>
                                                <li>
                                                    <div class="icon">
                                                        <i class="fa-regular fa-user"></i>
                                                    </div>
                                                    {{ __('instructor.students') }} {{ $course->students_count }}
                                                </li>
                                            </ul>
                                            <div class="client-info-area">
                                                <div class="client-info">
                                                    <div class="img">
                                                        <img src="{{ asset('assets/front/img/home-1/courses/client-01.png') }}"
                                                            alt="{{ $user->name }}">
                                                    </div>
                                                    {{ explode(' ', $user->name)[0] }}
                                                </div>
                                                <h2>{{ $course->pricing_model === 'free' ? __('instructor.free') : currency_format($price) }}
                                                </h2>
                                            </div>
                                            <a href="{{ route('instructor.courses.edit', $course->id) }}"
                                                class="theme-btn">{{ __('instructor.manage_course') }}</a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="dashboard-empty-state">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                        <h3>{{ __('instructor.no_courses_yet') }}</h3>
                                        <p>{{ __('instructor.no_courses_desc') }}</p>

                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="section-title-area wow fadeInUp section-title-area-top section-title-area-spacing">
                        <div class="section-title">
                            <h6>{{ __('instructor.quick_actions') }}</h6>
                            <h2>{{ __('instructor.create_and_manage') }}</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 mb-4 wow fadeInUp">
                            <div class="dashboard-content-section h-100">
                                <h3>
                                    <i class="fa-solid fa-plus-circle"></i>
                                    {{ __('instructor.quick_create') }}
                                </h3>
                                <div class="cta-button-group">
                                    <a href="{{ route('instructor.courses.create') }}" class="theme-btn cta-button">
                                        <i class="fa-solid fa-book"></i>
                                        <span class="cta-button__label">{{ __('instructor.create_new_course') }}</span>
                                    </a>
                                    <a href="{{ route('instructor.events.create') }}"
                                        class="theme-btn cta-button cta-button--outline-theme">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                        <span class="cta-button__label">{{ __('instructor.create_new_event') }}</span>
                                    </a>
                                    <a href="{{ route('instructor.events.index') }}"
                                        class="theme-btn cta-button cta-button--outline-theme-2">
                                        <i class="fa-solid fa-calendar-days"></i>
                                        <span class="cta-button__label">{{ __('instructor.manage_events') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4 wow fadeInUp" data-wow-delay=".2s">
                            <div class="dashboard-content-section h-100">
                                <h3>
                                    <i class="fa-solid fa-bullhorn"></i>
                                    {{ __('instructor.announcements') }}
                                </h3>
                                <div class="support-card">
                                    <h5 class="info-card-title text-theme">{{ __('instructor.need_help') }}</h5>
                                    <p class="info-card-text">
                                        {{ __('instructor.need_help_text') }}
                                    </p>
                                </div>
                                <div class="promotion-card">
                                    <h5 class="info-card-title text-theme-2">{{ __('instructor.promote_events') }}</h5>
                                    <p class="info-card-text">
                                        {{ __('instructor.promote_events_text') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
