@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('content')
<!-- Hero Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover"
        style="background-image: url('{{ asset('assets/front/img/home-1/hero/hero-bg.jpg') }}');">
    <div class="hero-shape-1 float-bob-x">
        <img src="{{ asset('assets/front/img/home-1/hero/shape-01.png') }}" alt="img">
    </div>
    <div class="hero-shape-2 float-bob-y">
        <img src="{{ asset('assets/front/img/home-1/hero/shape-02.png') }}" alt="img">
    </div>
    <div class="hero-shape-3 float-bob-y">
        <img src="{{ asset('assets/front/img/home-1/hero/shape-03.png') }}" alt="img">
    </div>
    <div class="hero-shape-4 float-bob-x">
        <img src="{{ asset('assets/front/img/home-1/hero/shape-04.png') }}" alt="img">
    </div>
    <div class="hero-shape-5 float-bob-y">
        <img src="{{ asset('assets/front/img/home-1/hero/shape-05.png') }}" alt="img">
    </div>
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="mhq-hero-content">
                    <span class="wow fadeInUp">{{ $hero['preheading'] }}</span>
                    <h1 class="wow fadeInUp" data-wow-delay=".2s">{{ $hero['heading'] }}</h1>
                    <p class="wow fadeInUp" data-wow-delay=".4s">
                        {{ $hero['description'] }}
                    </p>
                    <a href="{{ $hero['cta_url'] }}" class="theme-btn wow fadeInUp" data-wow-delay=".6s">
                        {{ $hero['cta_label'] }}
                        <i class="fa-solid fa-arrow-up-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mhq-right-items">
                    <div class="client-counter-box float-bob-x">
                        <div class="content">
                            <h3><span class="count">{{ number_format($stats['students']) }}</span>+</h3>
                            <p>{{ __('frontend.students') }}</p>
                        </div>
                        <div class="image">
                            <img src="{{ asset('assets/front/img/home-1/hero/client-img.png') }}" alt="img">
                        </div>
                    </div>
                    <div class="counter-box float-bob-y">
                        <h3><span class="count">{{ number_format($stats['courses']) }}</span>+</h3>
                        <p>{{ __('frontend.courses_published') }}</p>
                    </div>
                    <div class="row">
                        <div class="col-lg-5 col-sm-5 col-md-5 col-6">
                            <div class="mhq-hero-image style-2">
                                    <img src="{{ asset('assets/front/img/home-1/hero/hero-01.png') }}" alt="img"
                                        class="wow img-custom-anim-left">
                            </div>
                        </div>
                        <div class="col-lg-7 col-sm-7 col-md-7 col-6">
                            <div class="mhq-hero-imag-2">
                                    <img src="{{ asset('assets/front/img/home-1/hero/hero-02.png') }}" alt="img"
                                        class="wow img-custom-anim-right">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section Start -->
<section class="mhq-about-section section-padding">
    <div class="container">
        <div class="mhq-about-wrapper">
            <div class="row align-items-center">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay=".3s">
                    <div class="mhq-about-image">
                        <img src="{{ asset('assets/front/img/home-1/about/about-01.png') }}" alt="img">
                        <div class="mhq-about-image-2 float-bob-x">
                            <img src="{{ asset('assets/front/img/home-1/about/about-02.png') }}" alt="img">
                        </div>
                        <div class="mhq-about-image-3 float-bob-y">
                            <img src="{{ asset('assets/front/img/home-1/about/about-03.png') }}" alt="img">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mhq-about-content">
                        <div class="section-title mb-0">
                                <h6 class="wow fadeInUp">{{ $aboutSettings['subheading'] ?? 'About Us' }}</h6>
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">
                                    {{ $aboutSettings['heading'] ?? 'Transforming Learning Into Lasting Impact' }}
                            </h2>
                        </div>
                        <p class="about-text wow fadeInUp" data-wow-delay=".3s">
                                {{ $aboutSettings['description'] ?? 'We partner with expert instructors to create immersive learning pathways that deliver measurable results.' }}
                        </p>
                        <div class="mhq-counter-area">
                            <div class="mhq-counter-item wow fadeInUp" data-wow-delay=".4s">
                                <h2><span class="count">{{ number_format($stats['courses']) }}</span>+</h2>
                                <p>{{ __('frontend.courses_available') }}</p>
                            </div>
                            <div class="mhq-counter-item wow fadeInUp" data-wow-delay=".5s">
                                <h2><span class="count">{{ number_format($stats['enrollments']) }}</span>+</h2>
                                <p>{{ __('frontend.enrollments_completed') }}</p>
                            </div>
                            <div class="mhq-counter-item border-none wow fadeInUp" data-wow-delay=".6s">
                                <h2><span class="count">{{ number_format($stats['instructors']) }}</span>+</h2>
                                <p>{{ __('frontend.expert_instructors') }}</p>
                            </div>
                        </div>
                        <div class="mhq-about-author wow fadeInUp" data-wow-delay=".9s">
                            <div class="mhq-about-button">
                                    <a href="{{ $aboutSettings['cta_url'] ?? route('about') }}" class="theme-btn">
                                        {{ $aboutSettings['cta_label'] ?? 'Explore More' }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            </div>
                            <div class="mhq-author-image">
                                <img src="{{ asset('assets/front/img/home-1/about/client-01.png') }}" alt="author-img">
                                <div class="content">
                                        <h6>{{ $aboutSettings['author_name'] ?? 'Ronald Richards' }}</h6>
                                        <p>{{ $aboutSettings['author_title'] ?? 'Co-Founder' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Top Category Section Start -->
    <section class="mhq-top-category-section section-padding bg-cover"
        style="background-image: url('{{ asset('assets/front/img/home-1/category/category-bg.jpg') }}');">
    <div class="category-shape-1 float-bob-x">
        <img src="{{ asset('assets/front/img/home-1/category/shape-01.png') }}" alt="img">
    </div>
    <div class="container">
        <div class="section-title text-center">
            <h6 class="wow fadeInUp style-2">{{ __('frontend.top_category') }}</h6>
            <h2 class="wow fadeInUp text-white" data-wow-delay=".2s">{{ __('frontend.next_gen_education') }}</h2>
        </div>
        <div class="swiper mhq-top-category-slider">
            <div class="swiper-wrapper">
                @forelse($topCategories as $category)
                    <div class="swiper-slide">
                        <div class="mhq-category-box-items">
                            <div class="mhq-icon">
                                    <span class="mhq-category-icon"><i class="{{ $category['icon_class'] }}"></i></span>
                            </div>
                            <div class="mhq-content">
                                <h3>{{ $category['name'] }}</h3>
                                <p>{{ Str::limit($category['description'], 110) }}</p>
                                    <a href="{{ route('courses', ['category' => $category['slug']]) }}"
                                        class="theme-btn">
                                    {{ str_pad((string) $category['courses_count'], 2, '0', STR_PAD_LEFT) }} {{ __('frontend.courses') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="swiper-slide">
                        <div class="mhq-category-box-items text-center">
                            <div class="mhq-content">
                                <h3>{{ __('frontend.no_categories_yet') }}</h3>
                                <p>{{ __('frontend.categories_appear') }}</p>
                                <a href="{{ route('courses') }}" class="theme-btn">
                                    {{ __('frontend.browse_courses') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="array-button mt-5">
                <button class="array-prev"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="array-next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- Courses Section Start -->
<section class="mhq-courses-section section-padding fix">
    <div class="container">
        <div class="section-title-area align-items-end">
            <div class="section-title">
                <h6 class="wow fadeInUp">{{ __('frontend.popular_courses') }}</h6>
                <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.explore_featured') }}</h2>
            </div>
                <a href="{{ route('courses') }}" class="theme-btn wow fadeInUp" data-wow-delay=".4s">
                    {{ __('frontend.view_all_courses') }}
                    <i class="fa-solid fa-arrow-up-right"></i>
                </a>
        </div>
            <div class="row g-4">
                @forelse($featuredCourses as $index => $course)
                    @php $delay = number_format(($index % 3) * 0.2, 1); @endphp
                    <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="{{ $delay }}s">
                        <div class="mhq-courses-box-items">
                            <div class="mhq-courses-image">
                                <img src="{{ $course->getAttribute('display_image') }}" alt="{{ $course->title }}">
                                <span class="post-box">
                                    {{ $course->getAttribute('display_category') }}
                                </span>
                                @if ($course->is_live_course)
                                    <span class="post-box" style="left: auto; right: 20px; background-color: #dc3545; border-color: #dc3545; color: white;">
                                        <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                                    </span>
                                @endif
                            </div>
                            <div class="mhq-courses-content">
                                @if ($course->getAttribute('average_rating'))
                                <div class="star">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i
                                                class="fa-solid fa-star-sharp {{ $i <= round($course->getAttribute('average_rating')) ? '' : 'text-muted' }}"></i>
                                        @endfor
                                        <span class="rating-number">{{ $course->getAttribute('average_rating') }}</span>
                                        <span class="rating-reviews">({{ $course->reviews_count }})</span>
                                </div>
                                @endif
                                <h3><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a></h3>
                                <p>{{ Str::limit(strip_tags($course->description ?? ''), 110) }}</p>
                                <ul class="post-date">
                                    <li>
                                        <div class="icon">
                                            <i class="fa-regular fa-user"></i>
                                        </div>
                                        {{ number_format($course->students_count) }} {{ __('frontend.students') }}
                                    </li>
                                    <li>
                                        <div class="icon">
                                            <i class="fa-regular fa-clock"></i>
                                        </div>
                                        {{ $course->difficulty ? Str::headline($course->difficulty) : __('frontend.self_paced') }}
                                    </li>
                                </ul>
                                <div class="client-info-area">
                                    <div class="client-info">
                                        <div class="img">
                                            <img src="{{ $course->getAttribute('instructor_avatar') }}"
                                                alt="{{ optional($course->instructor)->name }}">
                                        </div>
                                        {{ optional($course->instructor)->name ?? __('frontend.unknown_instructor') }}
                                    </div>
                                    <h2>{{ $course->getAttribute('is_free') ? __('frontend.free') : currency_format($course->getAttribute('display_price')) }}
                                    </h2>
                                </div>
                                <a href="{{ route('courses.show', $course) }}" class="theme-btn">
                                    {{ __('frontend.view_course') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center mb-0">
                            {{ __('frontend.no_courses_yet') }}
                            </div>
                                </div>
                @endforelse
        </div>
    </div>
</section>

<!-- Bundles Section Start -->
<section class="mhq-courses-section section-padding fix bg-light">
    <div class="container">
        <div class="section-title-area align-items-end">
            <div class="section-title">
                <h6 class="wow fadeInUp">Exclusive Bundles</h6>
                <h2 class="wow fadeInUp" data-wow-delay=".2s">Unlock Complete Learning Paths</h2>
            </div>
            <div class="wow fadeInUp" data-wow-delay=".3s">
                <a href="{{ route('bundles.index') }}" class="theme-btn">
                    View All Bundles <i class="fa-solid fa-arrow-up-right"></i>
                </a>
            </div>
        </div>
        <div class="row g-4">
            @forelse($featuredBundles as $index => $bundle)
                @php $delay = number_format(($index % 3) * 0.2, 1); @endphp
                <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="{{ $delay }}s">
                    <div class="mhq-courses-box-items">
                        <div class="mhq-courses-image">
                            <img src="{{ $bundle->thumbnail ?? asset('assets/front/img/home-1/courses/courses-01.jpg') }}" alt="{{ $bundle->title }}">
                            <span class="post-box" style="background:#ff3c00; color:#fff;">
                                {{ $bundle->courses()->count() }} Courses Included
                            </span>
                        </div>
                        <div class="mhq-courses-content">
                            <h3><a href="{{ route('bundles.show', $bundle->id) }}">{{ $bundle->title }}</a></h3>
                            <p>{{ Str::limit(strip_tags($bundle->description ?? ''), 110) }}</p>
                            <div class="client-info-area">
                                <div class="client-info">
                                    <div class="img">
                                        <img src="{{ $bundle->vendor && $bundle->vendor->profile_photo ? asset('storage/' . $bundle->vendor->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}"
                                            alt="{{ optional($bundle->vendor)->name }}">
                                    </div>
                                    {{ optional($bundle->vendor)->name ?? __('frontend.unknown_instructor') }}
                                </div>
                                <h2 class="text-primary">{{ currency_format($bundle->price) }}</h2>
                            </div>
                            <a href="{{ route('bundles.show', $bundle->id) }}" class="theme-btn w-100 text-center">
                                View Bundle Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
               <!-- Hide section if no bundles exist -->
            @endforelse
        </div>
    </div>
</section>

<!-- Discount Section Start -->
    <section class="mhq-discount-section parallaxie section-padding fix bg-cover"
        style="background-image: url('{{ asset('assets/front/img/home-1/discount/bg-img.jpg') }}');">
    <div class="container">
        <div class="discount-box-items">
                <h2 class="wow fadeInUp">
                    {{ $discountSettings['heading'] ?? 'Act Fast: 50% Off for the First 50 Students!' }}</h2>
            <p class="wow fadeInUp" data-wow-delay=".2s">
                    {{ $discountSettings['description'] ?? 'The ability to learn at my own pace was a game-changer for me.' }}
            </p>
            <div class="btn-box wow fadeInUp" data-wow-delay=".4s">
                    @if (!empty($discountSettings['primary_label']))
                        <a href="{{ $discountSettings['primary_url'] ?? route('register') }}" class="theme-btn">
                            {{ $discountSettings['primary_label'] }}
                    <i class="fa-solid fa-arrow-up-right"></i>
                </a>
                    @endif
                    @if (!empty($discountSettings['secondary_label']))
                        <a href="{{ $discountSettings['secondary_url'] ?? route('instructor.register') }}"
                            class="theme-btn style-2">
                            {{ $discountSettings['secondary_label'] }}
                    <i class="fa-solid fa-arrow-up-right"></i>
                </a>
                    @endif
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section Start -->
<section class="mhq-why-choose-us-section section-padding fix">
    <div class="container">
        <div class="mhq-choose-us-wrapper">
            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="mhq-choose-us-content">
                        <div class="section-title mb-0">
                                <h6 class="wow fadeInUp">{{ $featureSettings['title'] ?? 'Why Choose Us' }}</h6>
                                <h2 class="wow fadeInUp" data-wow-delay=".2s">
                                    {{ $featureSettings['subtitle'] ?? 'Learning That Aligns With Your Personal Goals.' }}
                                </h2>
                        </div>
                        <p class="choose-text wow fadeInUp" data-wow-delay=".4s">
                                {{ $featureSettings['description'] ?? 'Unlock your full potential with education tailored to your personal aspirations. Learn, grow, and achieve success.' }}
                        </p>
                            @php $featureItems = collect($featureSettings['items'] ?? []); @endphp
                        <div class="mhq-choose-icon">
                                @foreach ($featureItems->chunk(2)->first() ?? [] as $feature)
                                    <div class="mhq-icon-items wow fadeInUp"
                                        data-wow-delay=".{{ $loop->iteration + 5 }}s">
                                <div class="icon">
                                            <i class="{{ $feature['icon'] ?? 'fa-solid fa-star' }}"></i>
                                </div>
                                <div class="content">
                                            <h4>{{ $feature['title'] ?? 'Feature' }}</h4>
                                            <p>{{ $feature['description'] ?? 'Description coming soon.' }}</p>
                                </div>
                            </div>
                                @endforeach
                        </div>
                        <div class="mhq-choose-icon">
                                @foreach ($featureItems->chunk(2)->skip(1)->first() ?? collect() as $feature)
                                    <div class="mhq-icon-items wow fadeInUp"
                                        data-wow-delay=".{{ $loop->iteration + 7 }}s">
                                        <div class="icon">
                                            <i class="{{ $feature['icon'] ?? 'fa-solid fa-star' }}"></i>
                                </div>
                                <div class="content">
                                            <h4>{{ $feature['title'] ?? 'Feature' }}</h4>
                                            <p>{{ $feature['description'] ?? 'Description coming soon.' }}</p>
                                </div>
                            </div>
                                @endforeach
                                @foreach ($featureItems->chunk(2)->skip(2) as $chunk)
                                    @foreach ($chunk as $feature)
                                        <div class="mhq-icon-items wow fadeInUp"
                                            data-wow-delay=".{{ $loop->parent->iteration + 9 }}s">
                                <div class="icon">
                                                <i class="{{ $feature['icon'] ?? 'fa-solid fa-star' }}"></i>
                                </div>
                                <div class="content">
                                                <h4>{{ $feature['title'] ?? 'Feature' }}</h4>
                                                <p>{{ $feature['description'] ?? 'Description coming soon.' }}</p>
                                </div>
                            </div>
                                    @endforeach
                                @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 wow fadeInUp" data-wow-delay=".4s">
                    <div class="mhq-choose-image">
                        <img src="{{ asset('assets/front/img/home-1/choose-us/choose-1.png') }}" alt="img">
                        <div class="mhq-counter-item float-bob-x">
                                <h2><span class="count">{{ $featureSettings['highlight_count'] ?? 92 }}</span>+</h2>
                                <p>{{ $featureSettings['highlight_label'] ?? 'Customizable Courses.' }}</p>
                        </div>
                        <div class="mhq-choose-image-2 float-bob-y">
                            <img src="{{ asset('assets/front/img/home-1/choose-us/choose-2.png') }}" alt="img">
                        </div>
                        <div class="mhq-choose-image-3 float-bob-x">
                            <img src="{{ asset('assets/front/img/home-1/choose-us/choose-3.png') }}" alt="img">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section Start -->
    <section class="mhq-team-section section-padding bg-cover"
        style="background-image: url('{{ asset('assets/front/img/home-1/team/team-bg.jpg') }}');">
    <div class="container">
        <div class="section-title text-center">
            <h6 class="wow fadeInUp">{{ __('frontend.teacher') }}</h6>
            <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.greatest_teachers') }}</h2>
        </div>
            <div class="row g-4">
                @forelse($featuredInstructors as $index => $instructor)
                    @php $delay = number_format(($index % 4) * 0.2, 1); @endphp
                    <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp"
                        @if ($delay > 0) data-wow-delay="{{ $delay }}s" @endif>
                <div class="mhq-team-box-items">
                    <div class="mhq-image">
                                <img src="{{ $instructor['avatar'] }}" alt="{{ $instructor['name'] }}">
                        <div class="mhq-content">
                                    <h4><a
                                            href="{{ route('instructors.show', $instructor['id']) }}">{{ $instructor['name'] }}</a>
                                    </h4>
                                    <p>{{ $instructor['headline'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center mb-0">{{ __('frontend.no_instructors') }}</div>
                        </div>
                @endforelse
        </div>
    </div>
</section>

<!-- Testimonial Section Start -->
<section class="mhq-testimonial-section section-padding fix">
    <div class="container">
        <div class="section-title-area">
            <div class="section-title">
                    <h6 class="wow fadeInUp">{{ $testimonialSettings['title'] ?? 'Students Reviews' }}</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">
                        {{ $testimonialSettings['subtitle'] ?? 'What Students Say About Our Platform.' }}</h2>
            </div>
            <div class="mhq-testimonial-author-items wow fadeInUp" data-wow-delay=".4s">
                <div class="mhq-testimonial-author">
                    <div class="icon">
                            <i class="fa-solid fa-star text-warning"></i>
                    </div>
                    <div class="content">
                            <h3>{{ number_format($testimonials->avg('rating'), 1) }}</h3>
                            <p>{{ __('frontend.average_rating') }}</p>
                    </div>
                </div>
                <div class="testimonial-btn">
                        @php
                            $testimonialCtaUrl = $testimonialSettings['cta_url'] ?? route('testimonials.index');
                            $testimonialCtaLabel = $testimonialSettings['cta_label'] ?? 'All Testimonials';
                        @endphp
                        <a href="{{ $testimonialCtaUrl }}" class="theme-btn">
                            {{ $testimonialCtaLabel }}
                        <i class="fa-solid fa-arrow-up-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="swiper mhq-testimonial-slider">
            <div class="swiper-wrapper">
                    @forelse($testimonials as $testimonial)
                <div class="swiper-slide">
                    <div class="mhq-testimonial-box-items">
                        <div class="quote-icon">
                                    <i class="fa-solid fa-quote-left"></i>
                        </div>
                                <div class="testimonial-rating mb-3">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i
                                            class="fa-solid fa-star {{ $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                        </div>
                        <h4>
                                    “{{ $testimonial['comment'] }}”
                        </h4>
                        <div class="client-info">
                            <div class="client-image">
                                        <img src="{{ $testimonial['student_avatar'] }}"
                                            alt="{{ $testimonial['student_name'] }}">
                            </div>
                            <div class="client-content">
                                        <h3>{{ $testimonial['student_name'] }}</h3>
                                        @if ($testimonial['course_title'])
                                            <p>on {{ $testimonial['course_title'] }}</p>
                                        @endif
                            </div>
                        </div>
                    </div>
                </div>
                    @empty
                <div class="swiper-slide">
                            <div class="mhq-testimonial-box-items text-center">
                                <h4>{{ __('frontend.no_reviews') }}</h4>
                        </div>
                            </div>
                    @endforelse
            </div>
        </div>
    </div>
</section>

<!-- News Section Start -->
<section class="mhq-news-section1 section-padding section-bg">
    <div class="container">
        <div class="row">
            <div class="col-xl-4 col-lg-6 mb-3 mt-xl-0">
                <div class="mhq-news-content">
                    <div class="section-title">
                            <h6 class="wow fadeInUp">{{ $blogSettings['title'] ?? 'Our Blogs' }}</h6>
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">
                                {{ $blogSettings['subtitle'] ?? 'Read Our Blog' }}</h2>
                    </div>
                        <p class="news-text wow fadeInUp" data-wow-delay=".4s">
                            {{ $blogSettings['description'] ?? 'Whether you\'re a lifelong learner or professional looking to upskill, our carefully curated courses cater to all levels and interests.' }}
                        </p>
                    <div class="array-button mt-4 justify-content-start">
                        <button class="array-prev"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="array-next"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="swiper mhq-news-slider">
                    <div class="swiper-wrapper">
                            @forelse($blogPosts as $post)
                        <div class="swiper-slide">
                            <div class="mhq-news-box-items">
                                <div class="mhq-image">
                                            <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}">
                                    <span class="post-box">
                                                {{ $post['category'] }}
                                    </span>
                                </div>
                                <div class="mhq-content">
                                    <ul class="list-box">
                                        <li>
                                            <i class="fa-solid fa-calendar-days"></i>
                                                    {{ $post['published_at'] ?? '—' }}
                                        </li>
                                        <li>
                                            <i class="fa-regular fa-message-minus"></i>
                                                    {{ str_pad((string) $post['comments_count'], 2, '0', STR_PAD_LEFT) }}
                                                    {{ __('frontend.comments') }}
                                        </li>
                                    </ul>
                                            <h3><a
                                                    href="{{ route('blog.show', $post['slug']) }}">{{ $post['title'] }}</a>
                                            </h3>
                                            <p>{{ $post['excerpt'] }}</p>
                                            <a href="{{ route('blog.show', $post['slug']) }}" class="theme-btn">
                                        {{ __('frontend.view_details') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                            @empty
                        <div class="swiper-slide">
                                    <div class="mhq-news-box-items text-center">
                                        <h3>{{ __('frontend.no_blog_posts') }}</h3>
                                </div>
                                </div>
                            @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cta Section Start -->
<section class="mhq-cta-new-section section-padding fix">
    <div class="container">
            <div class="mhq-cta-new-wrapper bg-cover"
                style="background-image: url('{{ asset('assets/front/img/home-1/cta/cta-bg.jpg') }}');">
            <div class="mhq-cta-shape-1 float-bob-x">
                <img src="{{ asset('assets/front/img/home-1/cta/shape-01.png') }}" alt="img">
            </div>
            <div class="mhq-cta-shape-2 float-bob-x">
                <img src="{{ asset('assets/front/img/home-1/cta/shape-02.png') }}" alt="img">
            </div>
            <div class="mhq-cta-shape-3 float-bob-y">
                <img src="{{ asset('assets/front/img/home-1/cta/shape-03.png') }}" alt="img">
            </div>
            <h2 class="wow fadeInUp" data-wow-delay=".3s">
                    {{ $ctaSettings['heading'] ?? 'Join our newsletter, spam-free.' }}
            </h2>
                <p class="wow fadeInUp mb-4" data-wow-delay=".4s">
                    {{ $ctaSettings['description'] ?? 'Get curated learning tips, course launches, and exclusive offers delivered to your inbox.' }}
                </p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="wow fadeInUp" data-wow-delay=".5s"
                      id="newsletterSubscribeForm">
                    @csrf
                    <input type="email" name="email" id="newsletter-email"
                        placeholder="{{ $ctaSettings['placeholder'] ?? 'Enter your email' }}" required>
                    <button type="submit" class="theme-btn" id="newsletterSubmitBtn">
                        {{ $ctaSettings['button_label'] ?? 'Subscribe Now' }}
                </button>
            </form>
                <div id="newsletterFeedback" class="mt-3"></div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script>
        $(function () {
            const form = $('#newsletterSubscribeForm');
            const submitBtn = $('#newsletterSubmitBtn');
            const defaultBtnLabel = submitBtn.text();
            const feedback = $('#newsletterFeedback');

            form.on('submit', function (event) {
                event.preventDefault();
                feedback.removeClass('text-success text-danger').text('');

                submitBtn.prop('disabled', true).text('Subscribing...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function (response) {
                        form[0].reset();
                        feedback.addClass('text-success').text(response.message || 'Subscribed successfully!');
                    },
                    error: function (xhr) {
                        let message = 'Something went wrong. Please try again later.';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            message = Object.values(errors)[0][0] || message;
                        }
                        feedback.addClass('text-danger').text(message);
                    },
                    complete: function () {
                        submitBtn.prop('disabled', false).text(defaultBtnLabel);
                    }
                });
            });
        });
    </script>
@endpush
