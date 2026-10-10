@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.about'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-about-section section-padding">
        <div class="container">
            <div class="mhq-about-wrapper">
                <div class="row align-items-center">
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-about-image">
                            <img src="{{ asset('assets/front/img/home-1/about/about-01.png') }}" alt="About {{ site_name() }}">
                            <div class="mhq-about-image-2 float-bob-x">
                                <img src="{{ asset('assets/front/img/home-1/about/about-02.png') }}" alt="Classroom">
                            </div>
                            <div class="mhq-about-image-3 float-bob-x">
                                <img src="{{ asset('assets/front/img/home-1/about/about-03.png') }}" alt="Students">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mhq-about-content">
                            <div class="section-title mb-0">
                                <h6 class="wow fadeInUp">{{ $heroSettings['subheading'] ?? __('frontend.about_us') }}</h6>
                                <h2 class="wow fadeInUp" data-wow-delay=".2s">
                                    {{ $heroSettings['heading'] ?? __('frontend.about_impact') }}</h2>
                            </div>
                            <p class="about-text wow fadeInUp" data-wow-delay=".3s">
                                {{ $heroSettings['description'] ?? site_name() . ' ' . __('frontend.about_fallback_desc') }}
                            </p>
                            <div class="mhq-counter-area">
                                <div class="mhq-counter-item wow fadeInUp" data-wow-delay=".4s">
                                    <h2><span class="count">{{ number_format($stats['courses']) }}</span>+</h2>
                                    <p>{{ __('frontend.active_courses') }}</p>
                                </div>
                                <div class="mhq-counter-item wow fadeInUp" data-wow-delay=".5s">
                                    <h2><span class="count">{{ number_format($stats['instructors']) }}</span>+</h2>
                                    <p>{{ __('frontend.expert_instructors') }}</p>
                                </div>
                                <div class="mhq-counter-item border-none wow fadeInUp" data-wow-delay=".6s">
                                    <h2><span class="count">{{ number_format($stats['students']) }}</span>+</h2>
                                    <p>{{ __('frontend.learners_enrolled') }}</p>
                                </div>
                            </div>
                            <div class="mhq-about-author wow fadeInUp" data-wow-delay=".9s">
                                <div class="mhq-about-button">
                                    <a href="{{ $heroSettings['cta_url'] ?? route('courses') }}" class="theme-btn">
                                        {{ $heroSettings['cta_label'] ?? __('frontend.explore_courses') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                </div>
                                <div class="mhq-author-image">
                                    <img src="{{ asset('assets/front/img/home-1/about/client-01.png') }}" alt="{{ site_name() }} Team">
                                    <div class="content">
                                        <h6>{{ $heroSettings['author_name'] ?? site_name() . ' ' . __('frontend.menu') }}</h6>
                                        <p>{{ $heroSettings['author_title'] ?? __('frontend.lifelong_learning') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mhq-choose-us-section-2 section-padding fix section-bg">
        <div class="container">
            <div class="mhq-choose-us-wrapper-2">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="mhq-choose-us-content">
                            <div class="section-title mb-0">
                                <h6 class="wow fadeInUp">{{ $featureSettings['title'] ?? __('frontend.why_choose_us') }}</h6>
                                <h2 class="wow fadeInUp" data-wow-delay=".2s">
                                    {{ $featureSettings['subtitle'] ?? __('frontend.learning_aligns') }}
                                </h2>
                            </div>
                            <p class="choose-text wow fadeInUp" data-wow-delay=".4s">
                                {{ $featureSettings['description'] ?? __('frontend.unlock_potential') }}
                            </p>
                            <ul class="wow fadeInUp" data-wow-delay=".6s">
                                @foreach ($featureSettings['items'] ?? [] as $item)
                                    <li><i
                                            class="{{ $item['icon'] ?? 'fa-solid fa-check' }} mr-2"></i>{{ $item['text'] ?? '' }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay=".8s">
                        <div class="mhq-icon-box-items-area">
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="mhq-box-items">
                                        @foreach ($highlightSettings['left'] ?? [] as $item)
                                            @php
                                                $countValue = $item['count'] ?? '';
                                                $isNumeric = is_numeric($countValue);
                                            @endphp
                                            <div class="mhq-box mb-3 style-3">
                                                <div class="icon"><i
                                                        class="{{ $item['icon'] ?? 'fa-solid fa-star' }}"></i></div>
                                                <div class="mhq-counter-item">
                                                    <h2>
                                                        @if ($isNumeric)
                                                            <span class="count">{{ $countValue }}</span>
                                                        @else
                                                            {{ $countValue }}
                                                        @endif
                                                    </h2>
                                                    <p>{{ $item['label'] ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <div class="mhq-box-items style-2">
                                        @foreach ($highlightSettings['right'] ?? [] as $highlight)
                                            @php
                                                $countValue = $highlight['count'] ?? '';
                                                $isNumeric = is_numeric($countValue);
                                            @endphp
                                            <div class="mhq-box mb-3 style-4">
                                                <div class="icon"><i
                                                        class="{{ $highlight['icon'] ?? 'fa-solid fa-star' }}"></i></div>
                                                <div class="mhq-counter-item">
                                                    <h2>
                                                        @if ($isNumeric)
                                                            <span class="count">{{ $countValue }}</span>
                                                        @else
                                                            {{ $countValue }}
                                                        @endif
                                                    </h2>
                                                    <p>{{ $highlight['label'] ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($topCategories->isNotEmpty())
        <section class="mhq-top-category-section section-padding bg-cover"
            style="background-image: url('{{ asset('assets/front/img/home-1/category/category-bg.jpg') }}');">
            <div class="container">
                <div class="section-title text-center">
                    <h6 class="wow fadeInUp style-2">{{ __('frontend.top_categories') }}</h6>
                    <h2 class="wow fadeInUp text-white" data-wow-delay=".2s">{{ __('frontend.learning_paths_love') }}</h2>
                </div>
                <div class="row g-4">
                    @foreach ($topCategories as $category)
                        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ ($loop->index % 3) * 0.2 }}s">
                            <div class="mhq-category-box-items">
                                <div class="mhq-icon">
                                    <img src="{{ asset('assets/front/img/home-1/category/category-0' . (($loop->index % 4) + 1) . '.svg') }}"
                                        alt="{{ $category->name }}">
                                </div>
                                <div class="mhq-content">
                                    <h3>{{ $category->name }}</h3>
                                    <p>{{ $category->description_excerpt }}</p>
                                    <a href="{{ url('/courses?category=' . $category->slug) }}" class="theme-btn">
                                        {{ $category->courses_count }} {{ __('frontend.courses') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featuredInstructors->isNotEmpty())
        <section class="mhq-team-section section-padding">
            <div class="container">
                <div class="section-title text-center">
                    <h6 class="wow fadeInUp">{{ __('frontend.featured_teachers') }}</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.greatest_teachers') }}</h2>
                </div>
                <div class="row g-4">
                    @foreach ($featuredInstructors as $featured)
                        <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp"
                            data-wow-delay="{{ ($loop->index % 4) * 0.2 }}s">
                            <div class="mhq-team-box-items">
                                <div class="mhq-image">
                                    <img src="{{ $featured->profile_photo ? asset('storage/' . $featured->profile_photo) : asset('assets/front/img/home-1/team/team-01.jpg') }}"
                                        alt="{{ $featured->name }}">
                                    <div class="mhq-content">
                                        <h4><a
                                                href="{{ route('instructors.show', $featured) }}">{{ $featured->name }}</a>
                                        </h4>
                                        <p>{{ $featured->specialization ?? 'Instructor' }}</p>
                                    </div>
                                </div>
                                <div class="mhq-team-meta small text-muted mt-3">
                                    <div><i class="fa-solid fa-book-open mr-2"></i>{{ $featured->courses_count }} {{ __('frontend.courses') }}
                                    </div>
                                    <div><i
                                            class="fa-solid fa-user mr-2"></i>{{ number_format($featured->students_count) }}
                                        {{ __('frontend.students') }}</div>
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
                    <h6 class="wow fadeInUp">{{ __('frontend.learner_feedback') }}</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.what_students_say') }}</h2>
                </div>
                <div class="row justify-content-center">
                    @foreach ($recentReviews as $review)
                        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ ($loop->index % 3) * 0.2 }}s">
                            <div class="mhq-testimonial-box-2 h-100">
                                <div class="star">
                                    @for ($i = 0; $i < 5; $i++)
                                        <i
                                            class="fa-solid fa-star-sharp {{ $i < $review->rating ? 'text-warning' : '' }}"></i>
                                    @endfor
                                </div>
                                <h3>“{{ $review->comment_excerpt }}”</h3>
                                <div class="mhq-info mt-4 d-flex align-items-center">
                                    <img src="{{ $review->user && $review->user->profile_photo ? asset('storage/' . $review->user->profile_photo) : asset('assets/front/img/home-2/testimonial/client-01.png') }}"
                                        alt="{{ $review->user->name ?? 'Student' }}" class="mr-3 rounded-circle"
                                        width="60" height="60" style="object-fit: cover;">
                                    <div>
                                        <h5 class="mb-0">{{ $review->user->name ?? 'Student' }}</h5>
                                        <small class="text-muted">{{ $review->course->title ?? 'Course' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

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
                    {{ $ctaSettings['heading'] ?? __('frontend.ready_take_step') }}
                </h2>
                <p class="wow fadeInUp mt-4" data-wow-delay=".4s">
                    {{ $ctaSettings['description'] ?? site_name() . ' ' . __('frontend.upskilling_desc') }}
                </p>
                <div class="btn-box wow fadeInUp mt-4" data-wow-delay=".5s">
                    <a href="{{ $ctaSettings['primary_url'] ?? route('courses') }}" class="theme-btn">
                        {{ $ctaSettings['primary_label'] ?? __('frontend.browse_courses') }}
                        <i class="fa-solid fa-arrow-up-right"></i>
                    </a>
                    @if (!empty($ctaSettings['secondary_label']))
                        <a href="{{ $ctaSettings['secondary_url'] ?? route('instructors.index') }}"
                            class="theme-btn style-2">
                            {{ $ctaSettings['secondary_label'] }}
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
