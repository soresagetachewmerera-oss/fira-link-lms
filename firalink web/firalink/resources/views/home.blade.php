@php
    use Illuminate\Support\Str;
@endphp
@extends('layouts.app')

@push('styles')
<style>
/* ==========================================================================
   Critical Hero & Nav Polish Styles (Inline Priority)
   ========================================================================== */
.header-1 .header-main .main-menu ul li a.fira-nav-active,
#header-sticky .header-main .main-menu ul li a.fira-nav-active {
    border: none !important;
    border-bottom: 2px solid #6D1A95 !important;
    border-radius: 0 !important;
    color: #6D1A95 !important;
    font-weight: 600 !important;
    background: transparent !important;
}
.header-1 .header-main .main-menu ul li a,
#header-sticky .header-main .main-menu ul li a {
    color: #334155 !important;
    border-bottom: 2px solid transparent !important;
}
.header-1 .header-main .main-menu ul li a:hover,
#header-sticky .main-menu ul li a:hover {
    color: #6D1A95 !important;
}
.fira-hero-section {
    position: relative !important;
    background-color: #ffffff !important;
    background-image: radial-gradient(circle at 75% 50%, rgba(109, 26, 149, 0.06) 0%, rgba(109, 26, 149, 0.015) 45%, rgba(255, 255, 255, 0) 70%) !important;
    padding: 96px 0 !important;
    overflow: hidden !important;
    width: 100% !important;
}
.fira-hero-container {
    max-width: 1200px !important;
    width: 100% !important;
    margin: 0 auto !important;
    padding: 0 24px !important;
}
.fira-hero-grid {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    align-items: center !important;
    gap: 48px !important;
}
.fira-hero-left-col {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}
.fira-hero-left-content {
    display: flex !important;
    flex-direction: column !important;
    gap: 24px !important;
}
.fira-hero-headline {
    font-family: 'Inter', sans-serif !important;
    font-size: clamp(36px, 5vw, 56px) !important;
    line-height: 1.1 !important;
    font-weight: 700 !important;
    color: #0F172A !important;
    letter-spacing: -0.02em !important;
    margin: 0 !important;
    max-width: 580px !important;
    text-transform: none !important;
}
.fira-hero-headline .purple-brand {
    color: #6D1A95 !important;
}
.fira-hero-subtext {
    font-family: 'Inter', sans-serif !important;
    font-size: 18px !important;
    color: #475569 !important;
    max-width: 520px !important;
    line-height: 1.6 !important;
    margin: 0 !important;
}
.fira-hero-action-block {
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
}
.fira-hero-cta-row {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
    flex-wrap: wrap !important;
    margin: 0 !important;
}
.fira-hero-btn-primary {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 52px !important;
    padding: 0 28px !important;
    border-radius: 12px !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    background-color: #6D1A95 !important;
    color: #ffffff !important;
    text-decoration: none !important;
    border: none !important;
    box-shadow: 0 8px 20px rgba(109, 26, 149, 0.25) !important;
    transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease !important;
    white-space: nowrap !important;
}
.fira-hero-btn-primary:hover {
    background-color: #5A1479 !important;
    color: #ffffff !important;
    transform: translateY(-1px) !important;
}
.fira-hero-btn-ghost {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    height: 52px !important;
    padding: 0 24px !important;
    border-radius: 12px !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    background-color: transparent !important;
    color: #6D1A95 !important;
    border: 1.5px solid #6D1A95 !important;
    text-decoration: none !important;
    transition: background-color 0.2s ease, transform 0.2s ease, border-color 0.2s ease !important;
    white-space: nowrap !important;
}
.fira-hero-btn-ghost:hover {
    background-color: rgba(109, 26, 149, 0.06) !important;
    color: #6D1A95 !important;
    transform: translateY(-1px) !important;
}
.fira-hero-proof-row {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 14px !important;
    color: #475569 !important;
    line-height: 1.4 !important;
    flex-wrap: wrap !important;
}
.fira-hero-proof-row .fira-proof-item strong {
    color: #0F172A !important;
    font-weight: 700 !important;
}
.fira-hero-proof-row .fira-proof-divider {
    color: #CBD5E1 !important;
}
.fira-hero-right-col {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
}
.fira-hero-visual-frame {
    position: relative !important;
    width: 100% !important;
    max-width: 580px !important;
}
.fira-hero-img-box {
    border-radius: 24px !important;
    overflow: hidden !important;
    background: linear-gradient(145deg, #fbf8fe 0%, #f6ecfc 50%, #eee1f8 100%) !important;
    box-shadow: 0 24px 60px rgba(109, 26, 149, 0.15) !important;
    position: relative !important;
    width: 100% !important;
    display: flex !important;
    align-items: flex-end !important;
    justify-content: center !important;
}
.fira-hero-image {
    width: 100% !important;
    height: auto !important;
    max-height: 480px !important;
    object-fit: cover !important;
    display: block !important;
}
.fira-hero-floating-card {
    position: absolute !important;
    bottom: -14px !important;
    left: -20px !important;
    background: #ffffff !important;
    border: 1px solid #E9E3F0 !important;
    border-radius: 12px !important;
    padding: 10px 16px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 12px !important;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08), 0 4px 12px rgba(109, 26, 149, 0.06) !important;
    z-index: 10 !important;
    max-width: 290px !important;
}
.fira-hero-floating-card .fira-card-icon {
    width: 32px !important;
    height: 32px !important;
    min-width: 32px !important;
    border-radius: 50% !important;
    background: rgba(109, 26, 149, 0.08) !important;
    color: #6D1A95 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 15px !important;
}
.fira-hero-floating-card .fira-card-title {
    font-family: 'Inter', sans-serif !important;
    font-size: 13.5px !important;
    font-weight: 700 !important;
    color: #0F172A !important;
    line-height: 1.25 !important;
    display: block !important;
}
.fira-hero-floating-card .fira-card-desc {
    font-family: 'Inter', sans-serif !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    color: #475569 !important;
    line-height: 1.25 !important;
    margin-top: 2px !important;
    display: block !important;
}
@media (max-width: 899px) {
    .fira-hero-section { padding: 64px 0 !important; }
    .fira-hero-grid { grid-template-columns: 1fr !important; gap: 40px !important; }
    .fira-hero-left-col { order: 1 !important; text-align: left !important; }
    .fira-hero-right-col { order: 2 !important; max-width: 520px !important; margin: 0 auto !important; }
    .fira-hero-cta-row { flex-direction: column !important; width: 100% !important; gap: 12px !important; }
    .fira-hero-btn-primary, .fira-hero-btn-ghost { width: 100% !important; }
    .fira-hero-floating-card { left: 12px !important; bottom: 12px !important; }
}
@media (max-width: 480px) {
    .fira-hero-container { padding: 0 16px !important; }
    .fira-hero-proof-row { gap: 8px 10px !important; }
}

/* 2026 homepage hero — product-led split layout, intentionally isolated from
   legacy hero selectors so this composition remains predictable. */
.lms-hero {
    position: relative;
    overflow: hidden;
    background: #fcfbfd;
    padding: clamp(56px, 8vw, 112px) 0 clamp(64px, 9vw, 128px);
}
.lms-hero::before {
    content: '';
    position: absolute;
    width: min(52vw, 720px);
    aspect-ratio: 1;
    right: -16vw;
    top: -32%;
    border-radius: 50%;
    background: #f4ecf8;
    z-index: 0;
}
.lms-hero__container { position: relative; z-index: 1; max-width: 1200px; padding: 0 24px; }
.lms-hero__grid { display: grid; grid-template-columns: minmax(0, .95fr) minmax(420px, 1.05fr); align-items: center; gap: clamp(40px, 7vw, 96px); }
.lms-hero__eyebrow { display: inline-flex; align-items: center; gap: 8px; margin: 0 0 20px; color: #6d1a95; font: 700 12px/1.2 Inter, sans-serif; letter-spacing: .09em; text-transform: uppercase; }
.lms-hero__eyebrow::before { content: ''; width: 22px; height: 1px; background: currentColor; }
.lms-hero__title { max-width: 640px; margin: 0; color: #182034; font: 700 clamp(2.7rem, 5vw, 4.65rem)/1.04 Inter, sans-serif; letter-spacing: -.055em; }
.lms-hero__title em { color: #6d1a95; font-style: normal; }
.lms-hero__copy { max-width: 530px; margin: 24px 0 0; color: #526075; font: 400 clamp(1rem, 1.35vw, 1.125rem)/1.65 Inter, sans-serif; }
.lms-hero__actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
.lms-hero__primary, .lms-hero__secondary { display: inline-flex; min-height: 52px; align-items: center; justify-content: center; gap: 10px; border-radius: 10px; padding: 0 21px; font: 600 15px/1 Inter, sans-serif; text-decoration: none; transition: transform .2s ease, background-color .2s ease, border-color .2s ease; }
.lms-hero__primary { border: 1px solid #6d1a95; background: #6d1a95; color: #fff; box-shadow: 0 12px 24px rgba(109, 26, 149, .18); }
.lms-hero__secondary { border: 1px solid #d8d0df; background: rgba(255,255,255,.75); color: #2f3a4b; }
.lms-hero__primary:hover { color: #fff; background: #581578; transform: translateY(-2px); }
.lms-hero__secondary:hover { color: #6d1a95; border-color: #bfa6cd; transform: translateY(-2px); }
.lms-hero__proof { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: 28px 0 0; padding: 0; list-style: none; color: #64748b; font: 500 13px/1.4 Inter, sans-serif; }
.lms-hero__proof li { display: inline-flex; align-items: center; gap: 7px; }
.lms-hero__proof i { color: #6d1a95; font-size: 12px; }
.lms-hero__visual { position: relative; }
.lms-hero__frame { position: relative; display: flex; align-items: end; min-height: 430px; overflow: hidden; border: 1px solid #e7dfeb; border-radius: 24px; background: #ead8f8; box-shadow: 0 30px 60px rgba(46, 24, 57, .14); }
.lms-hero__frame::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,.2), transparent 46%); pointer-events: none; }
.lms-hero__image { position: relative; z-index: 1; width: 100%; height: auto; object-fit: contain; object-position: bottom center; }
.lms-hero__caption { position: absolute; z-index: 2; left: 22px; bottom: 20px; max-width: 210px; padding: 11px 13px; border: 1px solid rgba(255,255,255,.74); border-radius: 10px; background: rgba(255,255,255,.88); color: #374151; font: 600 12px/1.35 Inter, sans-serif; backdrop-filter: blur(8px); }
.lms-hero__caption span { display: block; margin-top: 2px; color: #6d1a95; font-size: 11px; font-weight: 700; }
@media (max-width: 991px) { .lms-hero { padding: 64px 0 72px; } .lms-hero__grid { grid-template-columns: 1fr; gap: 42px; } .lms-hero__visual { max-width: 620px; } .lms-hero__frame { min-height: 0; } }
@media (max-width: 575px) { .lms-hero { padding: 46px 0 54px; } .lms-hero__container { padding: 0 18px; } .lms-hero__title { font-size: clamp(2.35rem, 12vw, 3.15rem); } .lms-hero__actions { display: grid; grid-template-columns: 1fr; } .lms-hero__primary, .lms-hero__secondary { width: 100%; } .lms-hero__proof { display: grid; gap: 10px; } .lms-hero__frame { border-radius: 18px; } .lms-hero__caption { left: 12px; bottom: 12px; } }
</style>
@endpush

@section('content')
<!-- Hero Section Start -->
<section class="lms-hero" aria-labelledby="homepage-hero-title">
    <div class="container lms-hero__container">
        <div class="lms-hero__grid">
            <div>
                <p class="lms-hero__eyebrow">Fira Link Academy</p>
                <h1 id="homepage-hero-title" class="lms-hero__title">Learn the trade skills that move <em>business forward.</em></h1>
                <p class="lms-hero__copy">Practical import and export training for Ethiopia—covering sourcing, customs, logistics, and the decisions that turn plans into working trade operations.</p>
                <div class="lms-hero__actions">
                    <a href="{{ route('courses') }}" class="lms-hero__primary">Explore courses <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="#intro-video-section" class="lms-hero__secondary" id="hero-watch-intro-btn"><i class="fa-solid fa-play" aria-hidden="true"></i> See how it works</a>
                </div>
                <ul class="lms-hero__proof" aria-label="Platform highlights">
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Practical, trade-focused learning</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Learn at your own pace</li>
                </ul>
            </div>
            <div class="lms-hero__visual">
                <div class="lms-hero__frame">
                    <img src="{{ asset('assets/front/img/hero/fira_hero_cutout.png') }}" alt="Fira Link trade professionals presenting coffee and the learning platform" class="lms-hero__image" width="791" height="485" loading="eager" fetchpriority="high">
                    <div class="lms-hero__caption">Learning built around real trade work<span>Customs · Logistics · Sourcing</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section class="fira-partners-section">
    <div class="container">
        <h3 class="fira-partners-heading">Official Industry Partner</h3>
        <div class="fira-partners-wrap">
                            <img src="{{ asset('assets/front/img/hero/koffera_coffee_logo.png') }}" alt="Koffera Coffee, Fira Link Academy's official industry partner" class="fira-partners-img koffera-partner-logo" width="250" height="75" loading="lazy">
        </div>
    </div>
</section>

<!-- Introduction Video Section -->
@include('partials.intro-video-section')

<!-- Courses Section Start -->
<section class="mhq-courses-section fix pt-4 pb-5">
    <div class="container">
        <div class="fira-catalog-section-header">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <div class="fira-catalog-sub">FIRA LINK ACADEMY CATALOG</div>
                    <h2 class="fira-catalog-title">Practical Trade Courses</h2>
                    <p class="fira-catalog-desc">Build practical knowledge for sourcing, customs, logistics, and export operations.</p>
                </div>
                <div class="fira-catalog-tabs">
                    <a href="{{ route('courses') }}" class="fira-tab-pill active">All Courses</a>
                    @foreach($topCategories as $category)
                        <a href="{{ route('courses', ['category' => $category['slug']]) }}" class="fira-tab-pill">{{ $category['name'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
            <div class="row g-4">
                @forelse($featuredCourses as $index => $course)
                    @php $delay = number_format(($index % 3) * 0.2, 1); @endphp
                    <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="{{ $delay }}s">
                        <div class="mhq-courses-box-items">
                            <div class="mhq-courses-image">
                                <img src="{{ $course->getAttribute('display_image') }}" alt="{{ $course->title }}" loading="lazy">
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
                                                alt="{{ optional($course->instructor)->name ?? 'Course instructor' }}" loading="lazy">
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



<!-- Conversion CTA: settings may tailor the message, but no unsupported discount is claimed by default. -->
    <section class="mhq-discount-section parallaxie section-padding fix bg-cover"
        style="background-image: url('{{ asset('assets/front/img/home-1/discount/bg-img.jpg') }}');">
    <div class="container">
        <div class="discount-box-items">
                <h2 class="wow fadeInUp">
                    {{ $discountSettings['heading'] ?? 'Ready to build practical trade skills?' }}</h2>
            <p class="wow fadeInUp" data-wow-delay=".2s">
                    {{ $discountSettings['description'] ?? 'Choose a course that fits your goals and begin learning at your own pace.' }}
            </p>
            <div class="btn-box wow fadeInUp" data-wow-delay=".4s">
                    @if (!empty($discountSettings['primary_label']))
                        <a href="{{ $discountSettings['primary_url'] ?? route('courses') }}" class="theme-btn">
                            {{ $discountSettings['primary_label'] }}
                    <i class="fa-solid fa-arrow-up-right"></i>
                </a>
                    @else
                        <a href="{{ route('courses') }}" class="theme-btn">
                            Explore courses
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

<!-- What Learners Say About Our Platform (TikTok Video Testimonials) -->
@include('partials.tiktok-testimonials')

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

<!-- Brand Overview & Positioning Section (SEO & Brand Search Booster) -->
<section class="fira-brand-story-section section-padding" style="background: #FFFFFF; border-top: 1px solid #E8E8EF;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="section-title mb-0">
                    <span class="badge mb-2 px-3 py-2" style="background: #F4ECF8; color: #6D1A95; font-weight: 600; border-radius: 20px;">Fira Link Academy &amp; Fira Link Business Solution</span>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s" style="font-size: 32px; font-weight: 700; color: #1A1A2E; line-height: 1.3;">
                        Practical Import &amp; Export Education for Ethiopia and East Africa
                    </h2>
                </div>
                <div class="brand-prose mt-4 text-secondary" style="font-size: 16px; line-height: 1.7; color: #4A4D5E;">
                    <p>
                        <strong>Fira Link Academy</strong> is the digital learning and business development platform of <strong>Fira Link Business Solution</strong>, headquartered in Addis Ababa, Ethiopia. We bridge the gap between theoretical business instruction and operational trade reality, giving entrepreneurs, coffee producers, logistics officers, and commercial importers the exact competencies needed to navigate cross-border commerce.
                    </p>
                    <p>
                        Our curriculum is built alongside experienced customs clearing agents, freight forwarders, and commercial bankers. Learners gain actionable knowledge in Ethiopian Electronic Single Window (eSW) declarations, HS tariff classification, Letter of Credit (LC) documentation under National Bank of Ethiopia (NBE) directives, and multimodal transport logistics connecting Addis Ababa, Modjo Dry Port, and the Port of Djibouti.
                    </p>
                    <div class="mt-4 pt-2">
                        <a href="{{ route('about') }}" class="theme-btn" style="background: #6D1A95; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <span>Learn About Our Mission</span>
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                        <a href="{{ route('programs') }}" class="theme-btn style-2 ms-3" style="border: 1px solid #6D1A95; color: #6D1A95; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <span>View All Programs</span>
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="brand-pillars-card p-4 p-md-5" style="background: #FAFAFC; border: 1px solid #E8E8EF; border-radius: 16px;">
                    <h3 class="h5 mb-4" style="color: #1A1A2E; font-weight: 700;">Core Pillars of Fira Link Academy</h3>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="pillar-icon me-3 p-3 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-file-invoice text-xl"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1" style="font-weight: 600; color: #1A1A2E;">Customs &amp; Regulatory Compliance</h4>
                            <p class="mb-0 text-muted small">Master Ethiopian Customs Commission protocols, valuation rules, eSW document uploads, and origin certification.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="pillar-icon me-3 p-3 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-ship text-xl"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1" style="font-weight: 600; color: #1A1A2E;">Corridor Logistics &amp; Freight</h4>
                            <p class="mb-0 text-muted small">Calculate real transport costing, multimodal container tracking, demurrage prevention, and marine insurance.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="pillar-icon me-3 p-3 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-mug-saucer text-xl"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1" style="font-weight: 600; color: #1A1A2E;">Coffee &amp; Commodity Trade</h4>
                            <p class="mb-0 text-muted small">Access live ICO benchmark pricing, coffee grading contracts, ECX exchange rules, and direct origin export licensing.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="pillar-icon me-3 p-3 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-screwdriver-wrench text-xl"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1" style="font-weight: 600; color: #1A1A2E;">Free Digital Trade Terminal</h4>
                            <p class="mb-0 text-muted small">Utilize our live market data feeds, export costing calculators, and trade advisory tools at zero cost.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Primary Sitelinks Directory Block (Internal Links Driving Google Sitelinks) -->
<section class="fira-sitelinks-section section-padding" style="background: #FAFAFC; border-top: 1px solid #E8E8EF;">
    <div class="container">
        <div class="section-title text-center mb-5">
            <span class="badge mb-2 px-3 py-2" style="background: #F4ECF8; color: #6D1A95; font-weight: 600; border-radius: 20px;">Site Navigation &amp; Resources</span>
            <h2 class="wow fadeInUp" style="font-size: 28px; font-weight: 700; color: #1A1A2E;">
                Explore Fira Link Academy: Key Programs, Tools &amp; Guides
            </h2>
            <p class="text-muted mt-2" style="max-width: 650px; margin: 0 auto;">
                Quickly navigate our public learning modules, transparent tuition pricing, free trade calculation suite, and corporate trade services.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-graduation-cap fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('programs') }}" class="text-dark text-decoration-none">Programs</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Specialized four-pillar trade curriculum: Customs, Logistics, Coffee Export, and Cross-Border Management.</p>
                    <a href="{{ route('programs') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">View Programs <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-book-open fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('courses') }}" class="text-dark text-decoration-none">Courses</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Self-paced and cohort practical video courses taught by verified Ethiopian trade practitioners.</p>
                    <a href="{{ route('courses') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">Explore Courses <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-chart-line fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('free-tools.index') }}" class="text-dark text-decoration-none">Free Tools</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Live Ethiopian coffee benchmark prices, corridor freight costing, export margin calculator, and AI trade advisor.</p>
                    <a href="{{ route('free-tools.index') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">Launch Free Suite <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-tags fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('pricing') }}" class="text-dark text-decoration-none">Pricing</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Transparent tuition tiers for individual online courses, corporate packages, and physical trade expeditions.</p>
                    <a href="{{ route('pricing') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">Review Tuition <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-building-columns fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('about') }}" class="text-dark text-decoration-none">About</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Learn about our founding mission, executive leadership, and connection to Fira Link Business Solution.</p>
                    <a href="{{ route('about') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">Read About Us <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-newspaper fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('blog.index') }}" class="text-dark text-decoration-none">Blog</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Expert market updates, regulatory analyses, customs notices, and export guidebooks for Ethiopian trade.</p>
                    <a href="{{ route('blog.index') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">Browse Articles <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-circle-question fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('faq') }}" class="text-dark text-decoration-none">FAQ</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Answers to frequently asked questions about enrollment, payment through Telebirr/CBE, certificates, and logistics.</p>
                    <a href="{{ route('faq') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">View All FAQs <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 p-4 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 p-2 rounded" style="background: #F4ECF8; color: #6D1A95;">
                            <i class="fa-solid fa-headset fa-lg"></i>
                        </div>
                        <h3 class="h6 mb-0" style="font-weight: 700;"><a href="{{ route('contact') }}" class="text-dark text-decoration-none">Contact</a></h3>
                    </div>
                    <p class="text-muted small mb-3">Speak directly with our admissions and trade advisory team at our Bole Sub-City offices in Addis Ababa.</p>
                    <a href="{{ route('contact') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">Contact Admissions <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>
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
