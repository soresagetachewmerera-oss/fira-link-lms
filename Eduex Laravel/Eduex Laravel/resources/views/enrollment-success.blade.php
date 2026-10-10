@extends('layouts.app')

@section('breadcrumb')
    @php
        $page_title = __('frontend.enrollment_successful');
        $base_url = route('home');
    @endphp
    @include('partials.breadcrumb')
@endsection

@section('content')
    <!-- Success Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover py-2 hero-bg-default">
        <div class="hero-shape-1 float-bob-x">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-01.png') }}" alt="img">
        </div>
        <div class="hero-shape-2 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-02.png') }}" alt="img">
        </div>
        <div class="hero-shape-3 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-03.png') }}" alt="img">
        </div>
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100 py-5">
                <div class="col-lg-8 col-md-10">
                    <div class="success-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="success-icon mb-4">
                            <div class="success-checkmark">
                                <div class="check-icon">
                                    <span class="icon-line line-tip"></span>
                                    <span class="icon-line line-long"></span>
                                    <div class="icon-circle"></div>
                                    <div class="icon-fix"></div>
                                </div>
                            </div>
                        </div>

                        <h2 class="mb-3 wow fadeInUp" data-wow-delay=".4s">
                            @if ($enrollment->isApproved())
                                {{ __('frontend.enrollment_successful_title') }}
                            @else
                                {{ __('frontend.enrollment_request_submitted') }}
                            @endif
                        </h2>

                        <p class="mb-4 wow fadeInUp" data-wow-delay=".5s">
                            @if ($bundle)
                                @if ($enrollment->isApproved())
                                    {{ __('frontend.congratulations_enrolled') }}
                                    <strong>{{ $bundle->title }}</strong> bundle.
                                @else
                                    Your enrollment request for <strong>{{ $bundle->title }}</strong> bundle
                                    {{ __('frontend.submitted_successfully') }}
                                @endif
                            @else
                                @if ($enrollment->isApproved())
                                    {{ __('frontend.congratulations_enrolled') }}
                                    <strong>{{ $enrollment->course->title }}</strong>.
                                @else
                                    {{ __('frontend.enrollment_request_for') }} <strong>{{ $enrollment->course->title }}</strong> {{ __('frontend.submitted_successfully') }}
                                    @if ($enrollment->payment && $enrollment->payment->isOffline())
                                        {{ __('frontend.payment_pending_verification') }}
                                    @endif
                                @endif
                            @endif
                        </p>

                        {{-- ===== BUNDLE: Show all courses in the bundle ===== --}}
                        @if ($bundle && $bundleCourses->isNotEmpty())
                            <div class="mb-4 wow fadeInUp" data-wow-delay=".55s">
                                <h5 class="mb-3">
                                    <i class="fa-solid fa-layer-group me-2 text-success"></i>
                                    Courses Included in This Bundle
                                </h5>

                                @if ($enrollment->payment)
                                    <p class="small text-muted mb-3">
                                        <strong>Amount Paid: {{ currency_format($enrollment->payment->amount) }}</strong>
                                    </p>
                                @endif

                                <div class="list-group">
                                    @foreach ($bundleCourses as $bundleCourse)
                                        <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                            @if ($bundleCourse->featured_image)
                                                <img src="{{ asset('storage/' . $bundleCourse->featured_image) }}"
                                                    alt="{{ $bundleCourse->title }}"
                                                    class="course-thumb-xs rounded"
                                                    style="width:56px;height:44px;object-fit:cover;">
                                            @else
                                                <img src="{{ asset('assets/front/img/inner/courses-details/courses-details.jpg') }}"
                                                    alt="{{ $bundleCourse->title }}"
                                                    class="course-thumb-xs rounded"
                                                    style="width:56px;height:44px;object-fit:cover;">
                                            @endif
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">{{ $bundleCourse->title }}</div>
                                                @if ($bundleCourse->instructor)
                                                    <small class="text-muted">{{ __('frontend.instructor_label') }} {{ $bundleCourse->instructor->name }}</small>
                                                @endif
                                            </div>
                                            @if ($enrollment->isApproved())
                                                <a href="{{ route('student.courses.access', $bundleCourse->id) }}"
                                                    class="btn btn-sm btn-outline-success">
                                                    Access <i class="fa-solid fa-arrow-up-right ms-1"></i>
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        {{-- ===== SINGLE COURSE: existing card ===== --}}
                        @else
                            <div class="course-details mb-4 wow fadeInUp course-details-card" data-wow-delay=".6s">
                                <div class="d-flex align-items-start mb-3">
                                    @if ($enrollment->course->featured_image)
                                        <img src="{{ asset('storage/' . $enrollment->course->featured_image) }}"
                                            alt="{{ $enrollment->course->title }}" class="me-3 course-thumb-sm">
                                    @else
                                        <img src="{{ asset('assets/front/img/inner/courses-details/courses-details.jpg') }}"
                                            alt="{{ $enrollment->course->title }}" class="me-3 course-thumb-sm">
                                    @endif
                                    <div>
                                        <h5 class="mb-1">{{ $enrollment->course->title }}</h5>
                                        @if ($enrollment->course->instructor)
                                            <p class="mb-1 small text-muted">{{ __('frontend.instructor_label') }}
                                                {{ $enrollment->course->instructor->name }}</p>
                                        @endif
                                        @if ($enrollment->payment)
                                            <p class="mb-0 small"><strong>{{ __('frontend.amount_paid') }}
                                                    {{ currency_format($enrollment->payment->amount) }}</strong></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="action-buttons wow fadeInUp" data-wow-delay=".7s">
                            @if ($enrollment->isApproved() && !$bundle)
                                <a href="{{ route('student.courses.access', $enrollment->course->id) }}"
                                    class="theme-btn me-3 mb-2">
                                    {{ __('frontend.access_course') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            @endif
                            <a href="{{ route('courses') }}" class="theme-btn style-2 mb-2">
                                {{ __('frontend.browse_more_courses') }}
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
