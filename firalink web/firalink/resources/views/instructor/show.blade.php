@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? 'Instructor',
        'base_url' => route('instructors.index'),
    ])
@endsection

@section('content')
    <section class="mhq-team-details-section section-padding">
        <div class="container">
            <div class="mhq-team-details-wrapper">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-team-details-left-image">
                            <div class="mhq-image">
                                <img src="{{ $instructor->display_avatar }}" alt="{{ $instructor->name }}">
                                @if ($instructor->years_experience)
                                    <span class="text-team">{{ $instructor->years_experience }} {{ __('frontend.experience') }} ({{ __('frontend.year_of') }})</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mhq-team-details-right-content">
                            <h2 class="wow fadeInUp">{{ $instructor->name }}</h2>
                            <h4 class="wow fadeInUp" data-wow-delay=".2s">{{ $instructor->headline }}</h4>
                            <div class="mhq-counter-area wow fadeInUp" data-wow-delay=".3s">
                                <div class="mhq-counter-item">
                                    <h3>{{ $instructor->stats['courses'] }}</h3>
                                    <p>{{ __('frontend.courses_count') }}</p>
                                </div>
                                <div class="mhq-counter-item">
                                    <h3>{{ number_format($instructor->stats['students']) }}</h3>
                                    <p>{{ __('frontend.total_students') }}</p>
                                </div>
                                <div class="mhq-counter-item">
                                    <h3>{{ $instructor->stats['rating'] ? number_format($instructor->stats['rating'], 1) : '—' }}
                                    </h3>
                                    <p>{{ __('frontend.rating') }}</p>
                                </div>
                                <div class="mhq-counter-item border-none">
                                    <h3>{{ number_format($instructor->stats['reviews']) }}</h3>
                                    <p>{{ __('frontend.reviews') }}</p>
                                </div>
                            </div>
                            @if ($instructor->bio)
                                <p class="wow fadeInUp" data-wow-delay=".4s">{{ $instructor->bio }}</p>
                            @endif
                            <ul class="wow fadeInUp mt-4 mhq-team-details-contact" data-wow-delay=".5s">
                                @if ($instructor->contact['phone'])
                                    <li><i class="fa-solid fa-phone"></i> <a
                                            href="tel:{{ $instructor->contact['phone_link'] }}">{{ $instructor->contact['phone'] }}</a>
                                    </li>
                                @endif
                                @if ($instructor->contact['email'])
                                    <li><i class="fas fa-envelope"></i> <a
                                            href="mailto:{{ $instructor->contact['email'] }}">{{ $instructor->contact['email'] }}</a>
                                    </li>
                                @endif
                            </ul>
                            <div class="social-icon wow fadeInUp" data-wow-delay=".6s">
                                @if ($instructor->facebook)
                                    <a href="{{ $instructor->facebook }}" target="_blank" rel="noopener"><i
                                            class="fab fa-facebook-f"></i></a>
                                @endif
                                @if ($instructor->linkedin)
                                    <a href="{{ $instructor->linkedin }}" target="_blank" rel="noopener"><i
                                            class="fa-brands fa-linkedin-in"></i></a>
                                @endif
                                @if ($instructor->twitter)
                                    <a href="{{ $instructor->twitter }}" target="_blank" rel="noopener"><i
                                            class="fab fa-twitter"></i></a>
                                @endif
                                @if ($instructor->youtube)
                                    <a href="{{ $instructor->youtube }}" target="_blank" rel="noopener"><i
                                            class="fab fa-youtube"></i></a>
                                @endif
                            </div>
                            @auth
                                <a href="{{ route('student.messages.show', $instructor->id) }}" class="theme-btn mt-5 wow fadeInUp" data-wow-delay=".7s">
                                    <i class="fa-solid fa-comment-dots me-1"></i>
                                    {{ __('frontend.message') }} {{ Str::before($instructor->name, ' ') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="theme-btn mt-5 wow fadeInUp" data-wow-delay=".7s">
                                    <i class="fa-solid fa-comment-dots me-1"></i>
                                    {{ __('frontend.message') }} {{ Str::before($instructor->name, ' ') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
                <div class="mhq-middle-content mt-5">
                    <h3 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.about_instructor') }}</h3>
                    <p class="wow fadeInUp" data-wow-delay=".3s">
                        {{ $instructor->previous_experience ?? __('frontend.instr_fallback_bio') }}
                    </p>
                    @if ($instructor->certifications)
                        <h5 class="mt-4">{{ __('frontend.certifications') }}</h5>
                        <ul class="list-unstyled">
                            @foreach (preg_split("/\r?\n/", $instructor->certifications) as $certification)
                                @if (trim($certification) !== '')
                                    <li><i class="fa-solid fa-check text-success mr-2"></i>{{ trim($certification) }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($courses->isNotEmpty())
        <section class="mhq-courses-section mhq-courses-section-2 section-padding bg-cover"
            style="background-image: url('{{ asset('assets/front/img/home-3/courses/courses-bg.jpg') }}');">
            <div class="container">
                <div class="section-title text-center">
                    <h6 class="wow fadeInUp">{{ __('frontend.popular_courses') }}</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">
                        {{ __('frontend.featured_courses_by', ['name' => Str::words($instructor->name, 1, '')]) }}
                    </h2>
                </div>
                <div class="row">
                    @foreach ($courses->take(6) as $course)
                        @php $rating = round($course->average_rating ?? 0); @endphp
                        <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp"
                            data-wow-delay="{{ ($loop->index % 3) * 0.2 }}s">
                            <div class="mhq-courses-box-items bg-white">
                                <div class="mhq-courses-image">
                                    <img src="{{ $course->display_image }}" alt="{{ $course->title }}">
                                    @if ($course->category)
                                        <span class="post-box">{{ $course->category }}</span>
                                    @endif
                                    @if ($course->is_live_course)
                                        <span class="post-box" style="left: auto; right: 20px; background-color: #dc3545; border-color: #dc3545;">
                                            <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mhq-courses-content">
                                    <div class="star">
                                        @for ($i = 0; $i < 5; $i++)
                                            <i class="fa-solid fa-star-sharp {{ $i < $rating ? 'text-warning' : '' }}"></i>
                                        @endfor
                                    </div>
                                    <h3><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a></h3>
                                    <ul class="post-date">
                                        <li>
                                            <div class="icon"><i class="fa-regular fa-user"></i></div>
                                            {{ __('frontend.total_students') }} {{ $course->students_count }}
                                        </li>
                                        <li>
                                            <div class="icon"><i class="fa-regular fa-star"></i></div>
                                            {{ __('frontend.reviews') }} {{ $course->reviews_count }}
                                        </li>
                                    </ul>
                                    <div class="client-info-area">
                                        <div class="client-info">
                                            <div class="img">
                                                <img src="{{ $instructor->display_avatar }}"
                                                    alt="{{ $instructor->name }}">
                                            </div>
                                            {{ $instructor->name }}
                                        </div>
                                        <h2>
                                            @if ($course->pricing_model === 'free' || (float) $course->sale_price === 0.0)
                                                {{ __('frontend.free') }}
                                            @else
                                                {{ currency_format($course->sale_price ?? $course->regular_price) }}
                                            @endif
                                        </h2>
                                    </div>
                                    <a href="{{ route('courses.show', $course) }}" class="theme-btn">{{ __('frontend.enroll_now') }}</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($recentReviews->isNotEmpty())
        <section class="mhq-testimonial-section-2 mt-0 mb-0 style-inner section-padding section-bg">
            <div class="container">
                <div class="section-title text-center">
                    <h6 class="wow fadeInUp">{{ __('frontend.recent_reviews') }}</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">
                        {{ __('frontend.instr_feedback_desc', ['name' => Str::words($instructor->name, 1, '')]) }}
                    </h2>
                </div>
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-10">
                        <div class="swiper-container fix testimonial-slider-content">
                            <div class="swiper-wrapper">
                                @foreach ($recentReviews as $review)
                                    <div class="swiper-slide">
                                        <div class="mhq-testimonial-box-2">
                                            <div class="star">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <i
                                                        class="fa-solid fa-star-sharp {{ $i < $review->rating ? 'text-warning' : '' }}"></i>
                                                @endfor
                                            </div>
                                            <h3>“{{ Str::limit($review->comment, 250) }}”</h3>
                                            <div class="mhq-info mt-4 d-flex align-items-center">
                                                <img src="{{ $review->student_avatar }}"
                                                    alt="{{ $review->user->name ?? 'Student' }}"
                                                    class="mr-3 rounded-circle" width="60" height="60"
                                                    style="object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">{{ $review->user->name ?? 'Student' }}</h5>
                                                    <small class="text-muted">{{ __('frontend.course') }}:
                                                        {{ $review->course->title ?? 'Course' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
