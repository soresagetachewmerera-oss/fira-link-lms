@extends('layouts.instructor')

@section('content')
    <!-- Instructor Courses Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container ">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6>{{ __('instructor.my_courses') }}</h6>
                            <h2>{{ __('instructor.manage_courses_title') }}</h2>
                            <p>{{ __('instructor.manage_courses_subtitle') }}</p>
                        </div>
                        <div class="dashboard-header-actions">
                            <a href="{{ route('instructor.courses.create') }}" class="theme-btn">
                                {{ __('instructor.create_new_course') }}
                                <i class="fa-solid fa-plus"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Courses Section with Gradient Background -->
                    <div class="dashboard-courses-section">
                        <div class="row">
                            @forelse($courses as $index => $course)
                                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp"
                                    data-wow-delay="{{ ($index % 3) * 0.2 }}s">
                                    <div class="mhq-courses-box-items">
                                        <div class="mhq-courses-image">
                                            <img src="{{ $course['image_url'] }}" alt="{{ $course['title'] }}"
                                                onerror="this.src='{{ asset('assets/front/img/home-1/courses/courses-01.jpg') }}'">
                                            <span class="post-box">{{ $course['category'] }}</span>
                                            @if ($course['is_live_course'])
                                                <span class="post-box" style="left: auto; right: 20px; background-color: #dc3545; border-color: #dc3545;">
                                                    <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                                                </span>
                                            @endif
                                            @if ($course['status'] === 'draft')
                                                <span class="course-status-badge draft">{{ __('instructor.draft') }}</span>
                                            @elseif($course['status'] === 'pending')
                                                <span class="course-status-badge pending">{{ __('instructor.pending_approval') }}</span>
                                            @elseif($course['status'] === 'published')
                                                <span class="course-status-badge published">{{ __('instructor.published') }}</span>
                                            @endif
                                        </div>
                                        <div class="mhq-courses-content">
                                            <div class="star">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @php $rating = $course['rating_for_stars']; @endphp
                                                    @if ($rating >= $i)
                                                        <i class="fa-solid fa-star"></i>
                                                    @elseif($rating >= $i - 0.5)
                                                        <i class="fa-solid fa-star-half-stroke"></i>
                                                    @else
                                                        <i class="fa-regular fa-star"></i>
                                                    @endif
                                                @endfor
                                                <span class="star-average">
                                                    {{ $course['rating'] !== null ? number_format($course['rating'], 1) : '—' }}
                                                </span>
                                            </div>
                                            <h3><a
                                                    href="{{ route('courses.show', $course['id']) }}">{{ $course['title'] }}</a>
                                            </h3>
                                            <ul class="post-date">
                                                <li>
                                                    <a href="#" class="icon video-popup">
                                                        <i class="fa-regular fa-circle-play"></i>
                                                    </a>
                                                    {{ $course['lessons'] }}{{ __('instructor.lessons_count_postfix') }}
                                                </li>
                                                <li>
                                                    <div class="icon">
                                                        <i class="fa-regular fa-user"></i>
                                                    </div>
                                                    {{ __('instructor.students') }} {{ number_format($course['students']) }}
                                                </li>
                                            </ul>
                                            <div class="client-info-area">
                                                <div class="client-info">
                                                    <div class="img">
                                                        <img src="{{ asset('assets/front/img/home-1/courses/client-01.png') }}"
                                                            alt="img">
                                                    </div>
                                                    {{ explode(' ', $user->name)[0] }}
                                                </div>
                                                <h2>
                                                    @if ($course['pricing_model'] === 'free')
                                                        {{ __('instructor.free') }}
                                                    @else
                                                        {{ $course['price'] }}
                                                    @endif
                                                </h2>
                                            </div>
                                            <div class="course-actions">
                                                <a href="{{ route('instructor.courses.edit', $course['id']) }}"
                                                    class="theme-btn py-3">
                                                    {{ __('instructor.edit_course') }}
                                                    <i class="fa-solid fa-edit"></i>
                                                </a>
                                                <a href="{{ route('courses.show', $course['id']) }}"
                                                    class="theme-btn style-2 py-3">
                                                    {{ __('instructor.view_course') }}
                                                    <i class="fa-solid fa-arrow-up-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="dashboard-empty-state">
                                        <i class="fa-solid fa-book-open"></i>
                                        <h3>{{ __('instructor.no_courses_title') }}</h3>
                                        <p>{{ __('instructor.no_courses_desc') }}</p>

                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- Dashboard Courses Section End -->
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>
@endsection
