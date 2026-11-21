@php
    use Illuminate\Support\Str;
@endphp
@extends('layouts.app')

@push('styles')
<style>
/* ==========================================================================
   FIRA LINK HOMEPAGE (DESIGN SYSTEM v2.0)
   Strict 1200px container, authentic Ethiopian photography, tabular figures
   ========================================================================== */
.lms-hero {
    position: relative;
    background-color: var(--fira-page-bg, #F9F7FA);
    padding: clamp(48px, 6vw, 80px) 0 clamp(48px, 7vw, 88px);
    border-bottom: 1px solid var(--fira-border-hairline, #E9E4F0);
}

.lms-hero__container {
    max-width: 1200px;
    margin-inline: auto;
    padding-inline: 24px;
}

@media (max-width: 768px) {
    .lms-hero__container {
        padding-inline: 16px;
    }
}

.lms-hero__grid {
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    align-items: center;
    gap: clamp(32px, 5vw, 64px);
}

@media (max-width: 991px) {
    .lms-hero__grid {
        grid-template-columns: 1fr;
        gap: 36px;
    }
}

.lms-hero__title {
    color: var(--fira-text-primary, #1E1B4B);
    font-size: clamp(2.25rem, 4.2vw, 3.5rem);
    font-weight: 700;
    line-height: 1.12;
    letter-spacing: -0.03em;
    margin: 12px 0 16px;
}

.lms-hero__copy {
    color: var(--fira-text-secondary, #4B4869);
    font-size: clamp(1rem, 1.25vw, 1.125rem);
    line-height: 1.65;
    max-width: 580px;
    margin-bottom: 28px;
}

.lms-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.lms-hero__primary, .lms-hero__secondary {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: var(--fira-radius-input, 12px);
    padding: 0 22px;
    font-size: 14.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease-out;
}

.lms-hero__primary {
    border: 1px solid var(--fira-primary);
    background: var(--fira-primary);
    color: #FFFFFF;
}

.lms-hero__primary:hover {
    background: var(--fira-primary-dark);
    border-color: var(--fira-primary-dark);
    color: #FFFFFF;
}

.lms-hero__secondary {
    border: 1px solid var(--fira-border-hairline, #E9E4F0);
    background: #FFFFFF;
    color: var(--fira-text-primary, #1E1B4B);
}

.lms-hero__secondary:hover {
    border-color: var(--fira-primary);
    background: var(--fira-primary-tint, #F3E8FF);
    color: var(--fira-primary);
}

.lms-hero__frame {
    position: relative;
    border-radius: var(--fira-radius-card, 16px);
    overflow: hidden;
    background: #EAD8F8;
    box-shadow: 0 16px 36px -4px rgba(59, 7, 100, 0.12), 0 4px 12px -2px rgba(59, 7, 100, 0.06);
    border: 1px solid var(--fira-border-hairline, #E9E4F0);
    aspect-ratio: 4 / 3;
}

.lms-hero__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.lms-hero__caption {
    position: absolute;
    bottom: 16px;
    left: 16px;
    right: 16px;
    background: rgba(30, 27, 75, 0.88);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    border-radius: 10px;
    padding: 10px 14px;
    color: #FFFFFF;
    font-size: 0.8125rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Route Corridor Marker */
.fira-route {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--fira-primary);
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.fira-route__line {
    position: relative;
    display: inline-flex;
    justify-content: space-between;
    width: 36px;
    flex: none;
}

.fira-route__line::before {
    content: '';
    position: absolute;
    left: 3px;
    right: 3px;
    top: 50%;
    height: 1px;
    background: currentColor;
    opacity: 0.6;
}

.fira-route__line i {
    position: relative;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 1px solid currentColor;
}

.fira-route__line i:first-child {
    background: var(--fira-primary);
    border-color: var(--fira-primary);
}

/* Live Market Reference Strip */
.fira-market-strip {
    background: var(--fira-primary-dark, #3B0764);
    color: #FFFFFF;
    padding: 12px 0;
    overflow-x: auto;
    white-space: nowrap;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.fira-ticker-flex {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.fira-ticker-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8125rem;
    font-variant-numeric: tabular-nums;
}

.fira-ticker-name {
    color: #E9D5FF;
    font-weight: 500;
}

.fira-ticker-price {
    font-weight: 700;
    color: #FFFFFF;
}

.fira-ticker-pos {
    color: #4ADE80;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Trust Signals Bar */
.fira-trust-bar {
    padding: 28px 0;
    background: #FFFFFF;
    border-bottom: 1px solid var(--fira-border-hairline, #E9E4F0);
}

.fira-trust-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .fira-trust-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.fira-trust-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.fira-trust-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--fira-primary-tint, #F3E8FF);
    color: var(--fira-primary);
    display: grid;
    place-content: center;
    flex-shrink: 0;
}

.fira-trust-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--fira-neutral-900, #1E1B4B);
    line-height: 1.25;
}

.fira-trust-sub {
    font-size: 0.75rem;
    color: var(--fira-neutral-500, #6B7280);
    margin-top: 2px;
}

/* Catalog Tabs & Cards */
.fira-catalog-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.fira-tab-pill {
    display: inline-flex;
    align-items: center;
    height: 34px;
    padding: 0 14px;
    border-radius: 999px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--fira-neutral-700);
    border: 1px solid var(--fira-neutral-300);
    background: #FFFFFF;
    text-decoration: none;
    transition: all 0.15s ease;
}

.fira-tab-pill:hover {
    border-color: var(--fira-primary);
    color: var(--fira-primary);
}

.fira-tab-pill.active {
    background: var(--fira-primary);
    border-color: var(--fira-primary);
    color: #FFFFFF;
    font-weight: 600;
}

/* Sections */
.fira-home-band {
    background: #FFFFFF;
    border-top: 1px solid var(--fira-border-hairline, #E9E4F0);
    padding: clamp(48px, 6vw, 80px) 0;
}

.fira-home-band--muted {
    background: var(--fira-page-bg, #F9F7FA);
}

.fira-home-pillars {
    padding: 32px;
    background: var(--fira-page-bg, #F9F7FA);
    border: 1px solid var(--fira-border-hairline, #E9E4F0);
    border-radius: 16px;
}

.fira-home-pillar {
    display: grid;
    grid-template-columns: 20px 1fr;
    gap: 14px;
    align-items: start;
}

.fira-home-pillar + .fira-home-pillar {
    margin-top: 20px;
}

.fira-home-pillar i {
    color: var(--fira-primary);
    font-size: 16px;
    line-height: 24px;
    text-align: center;
}

.fira-home-pillar h4 {
    margin: 0 0 4px;
    color: var(--fira-neutral-900);
    font-size: 15px;
    font-weight: 700;
}

.fira-home-pillar p {
    margin: 0;
    color: var(--fira-neutral-500);
    font-size: 13.5px;
    line-height: 1.55;
}

.fira-home-link {
    height: 100%;
    padding: 24px;
    background: #FFFFFF;
    border: 1px solid var(--fira-border-hairline, #E9E4F0);
    border-radius: 12px;
    transition: border-color 0.15s ease-out;
}

.fira-home-link:hover {
    border-color: var(--fira-primary);
}

.fira-home-link__head {
    display: grid;
    grid-template-columns: 20px 1fr;
    gap: 12px;
    align-items: center;
    margin-bottom: 12px;
}

.fira-home-link__head i {
    color: var(--fira-primary);
}

.fira-home-link h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
}

.fira-home-link h3 a {
    color: var(--fira-neutral-900);
    text-decoration: none;
}

.fira-home-link p {
    margin: 0 0 16px;
    color: var(--fira-neutral-500);
    font-size: 13.5px;
    line-height: 1.6;
}

.fira-home-link__more {
    color: var(--fira-primary);
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
}
</style>
@endpush

@section('content')

<!-- 1. Hero Section: Full Company Scope (Coffee B2B, Terminal, Academy, Logistics) -->
<section class="lms-hero" aria-labelledby="homepage-hero-title">
    <div class="container lms-hero__container">
        <div class="lms-hero__grid">
            <div>
                <p class="fira-route"><span class="fira-route__line" aria-hidden="true"><i></i><i></i><i></i></span>Addis Ababa · Modjo · Djibouti</p>
                <h1 id="homepage-hero-title" class="lms-hero__title">Learn to import and export through Ethiopia</h1>
                <p class="lms-hero__copy">
                    Practical courses on sourcing, customs, logistics, and coffee export — backed by our live B2B coffee marketplace, market terminal, and origin expeditions.
                </p>
                <div class="lms-hero__actions">
                    <a href="{{ route('courses') }}" class="lms-hero__primary">Explore courses <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ route('marketplace.index') }}" class="lms-hero__secondary">Browse coffee lots</a>
                </div>
            </div>
            <div class="lms-hero__visual">
                <div class="lms-hero__frame">
                    <img src="{{ asset('storage/hero/fira_hero_drying_beds.jpg') }}" alt="Authentic Ethiopian raised drying beds in Yirgacheffe" class="lms-hero__image" width="791" height="485" loading="eager" fetchpriority="high">
                    <div class="lms-hero__caption">
                        <strong>Yirgacheffe Washing Station</strong> · Raised African drying beds
                        <span>Grade 1 Specialty</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Live Coffee Market Reference Strip (Tabular Numbers) -->
<div class="fira-market-strip" aria-label="Ethiopian Coffee Reference Prices">
    <div class="container lms-hero__container">
        <div class="fira-ticker-flex">
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <span class="fira-badge fira-badge-pending" style="background: rgba(243, 232, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); color: #FFFFFF; font-size: 11px;">
                    ECX / FOB Djibouti
                </span>
            </div>
            <div class="fira-ticker-item">
                <span class="fira-ticker-name">Yirgacheffe G1 Natural:</span>
                <span class="fira-ticker-price">$5.20 / lb</span>
                <span class="fira-ticker-pos">+2.4%</span>
            </div>
            <div class="fira-ticker-item">
                <span class="fira-ticker-name">Sidama G1 Washed:</span>
                <span class="fira-ticker-price">$4.85 / lb</span>
                <span class="fira-ticker-pos">+1.1%</span>
            </div>
            <div class="fira-ticker-item">
                <span class="fira-ticker-name">Guji G1 Anaerobic:</span>
                <span class="fira-ticker-price">$5.60 / lb</span>
                <span class="fira-ticker-pos">+3.2%</span>
            </div>
            <div class="fira-ticker-item">
                <span class="fira-ticker-name">Limu G2 Washed:</span>
                <span class="fira-ticker-price">$3.95 / lb</span>
                <span class="fira-ticker-pos">+0.5%</span>
            </div>
            <a href="{{ route('market-prices.index') }}" style="color: #DDD6FE; font-size: 12px; font-weight: 600; text-decoration: underline; flex-shrink: 0;">
                Full terminal →
            </a>
        </div>
    </div>
</div>

<!-- 3. Institutional Trust Signals Bar -->
<section class="fira-trust-bar">
    <div class="container lms-hero__container">
        <div class="fira-trust-grid">
            <div class="fira-trust-item">
                <div class="fira-trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div>
                    <div class="fira-trust-title">ECTA Compliance</div>
                    <div class="fira-trust-sub">Direct exporter licensing</div>
                </div>
            </div>

            <div class="fira-trust-item">
                <div class="fira-trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8l-8 8"></path><path d="M8 8l8 8"></path></svg>
                </div>
                <div>
                    <div class="fira-trust-title">Q-Grader Cupping</div>
                    <div class="fira-trust-sub">Certified moisture &amp; sensory</div>
                </div>
            </div>

            <div class="fira-trust-item">
                <div class="fira-trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div>
                    <div class="fira-trust-title">Escrow Protection</div>
                    <div class="fira-trust-sub">Secured trade milestones</div>
                </div>
            </div>

            <div class="fira-trust-item">
                <div class="fira-trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                <div>
                    <div class="fira-trust-title">Corridor Freight</div>
                    <div class="fira-trust-sub">Addis to Djibouti customs</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. Featured B2B Coffee Lots -->
<section class="fira-home-band fira-home-band--muted">
    <div class="container lms-hero__container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div>
                <p class="fira-route"><span class="fira-route__line" aria-hidden="true"><i></i><i></i><i></i></span>Direct Sourcing</p>
                <h2 class="fira-home-h2">Verified Ethiopian Coffee Lots</h2>
                <p style="color: var(--fira-neutral-500); margin-top: 4px;">Spot and forward contracts from certified washing stations in Gedeo, Sidama, and Guji.</p>
            </div>
            <a href="{{ route('marketplace.index') }}" class="lms-hero__secondary" style="min-height: 40px; padding: 0 16px; font-size: 13.5px;">
                View all lots →
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="fira-lot-card">
                    <div class="fira-lot-thumb" style="background-image: url('{{ asset('storage/hero/fira_hero_drying_beds.jpg') }}');">
                        <span class="fira-badge fira-badge-pending fira-lot-thumb-badge" style="background: rgba(243, 232, 255, 0.95);">Grade 1 Natural</span>
                    </div>
                    <div class="fira-lot-body">
                        <span class="fira-lot-origin">Yirgacheffe • Chelchele Station</span>
                        <h4 class="fira-lot-title">Chelchele Anaerobic Micro-Lot</h4>
                        <div class="fira-lot-meta-grid">
                            <div class="fira-lot-meta-item">
                                <div>SCA Cupping</div>
                                <div class="fira-lot-meta-val">88.75 Q</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Elevation</div>
                                <div class="fira-lot-meta-val">2,150 MASL</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Available Volume</div>
                                <div class="fira-lot-meta-val">120 Bags (7.2 MT)</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Moisture</div>
                                <div class="fira-lot-meta-val">10.8%</div>
                            </div>
                        </div>
                        <div class="fira-lot-pricing">
                            <div>
                                <div class="fira-lot-price-val">$5.20 / lb</div>
                                <div class="fira-lot-price-sub">FOB Djibouti Port</div>
                            </div>
                            <a href="{{ route('marketplace.index') }}" class="fira-btn fira-btn-primary fira-btn-md">Request sample</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="fira-lot-card">
                    <div class="fira-lot-thumb" style="background-image: url('{{ asset('storage/hero/fira_hero_cupping_lab.jpg') }}');">
                        <span class="fira-badge fira-badge-success fira-lot-thumb-badge" style="background: rgba(220, 252, 231, 0.95);">Grade 2 Washed</span>
                    </div>
                    <div class="fira-lot-body">
                        <span class="fira-lot-origin">Sidama • Bensa Station</span>
                        <h4 class="fira-lot-title">Bensa Washed 74158 Selection</h4>
                        <div class="fira-lot-meta-grid">
                            <div class="fira-lot-meta-item">
                                <div>SCA Cupping</div>
                                <div class="fira-lot-meta-val">87.50 Q</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Elevation</div>
                                <div class="fira-lot-meta-val">1,980 MASL</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Available Volume</div>
                                <div class="fira-lot-meta-val">320 Bags (1 FCL)</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Moisture</div>
                                <div class="fira-lot-meta-val">11.1%</div>
                            </div>
                        </div>
                        <div class="fira-lot-pricing">
                            <div>
                                <div class="fira-lot-price-val">$4.65 / lb</div>
                                <div class="fira-lot-price-sub">FOB Djibouti Port</div>
                            </div>
                            <a href="{{ route('marketplace.index') }}" class="fira-btn fira-btn-primary fira-btn-md">Request sample</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="fira-lot-card">
                    <div class="fira-lot-thumb" style="background-image: url('{{ asset('storage/hero/fira_hero_highland_harvest.jpg') }}');">
                        <span class="fira-badge fira-badge-pending fira-lot-thumb-badge" style="background: rgba(243, 232, 255, 0.95);">Grade 1 Natural</span>
                    </div>
                    <div class="fira-lot-body">
                        <span class="fira-lot-origin">Guji • Hambela Wamena</span>
                        <h4 class="fira-lot-title">Hambela Highland Natural Lot</h4>
                        <div class="fira-lot-meta-grid">
                            <div class="fira-lot-meta-item">
                                <div>SCA Cupping</div>
                                <div class="fira-lot-meta-val">88.25 Q</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Elevation</div>
                                <div class="fira-lot-meta-val">2,200 MASL</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Available Volume</div>
                                <div class="fira-lot-meta-val">180 Bags (10.8 MT)</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Moisture</div>
                                <div class="fira-lot-meta-val">10.5%</div>
                            </div>
                        </div>
                        <div class="fira-lot-pricing">
                            <div>
                                <div class="fira-lot-price-val">$5.10 / lb</div>
                                <div class="fira-lot-price-sub">FOB Djibouti Port</div>
                            </div>
                            <a href="{{ route('marketplace.index') }}" class="fira-btn fira-btn-primary fira-btn-md">Request sample</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. Courses Section Start (Practical trade courses) -->
<section class="mhq-courses-section fix pt-5 pb-5">
    <div class="container lms-hero__container">
        <div class="fira-catalog-section-header">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <p class="fira-route"><span class="fira-route__line" aria-hidden="true"><i></i><i></i><i></i></span>Course catalog</p>
                    <h2 class="fira-home-h2">Practical trade courses</h2>
                    <p style="color: var(--fira-neutral-500); margin-top: 4px;">Sourcing, customs, logistics, and export operations</p>
                </div>
                <div class="fira-catalog-tabs">
                    <a href="{{ route('courses') }}" class="fira-tab-pill active">All courses</a>
                    @foreach($topCategories as $category)
                        <a href="{{ route('courses', ['category' => $category['slug']]) }}" class="fira-tab-pill">{{ $category['name'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="row g-4">
            @forelse($featuredCourses as $index => $course)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="fira-course-card">
                        <div class="fira-course-thumb" style="background-image: url('{{ $course->featured_image_url }}');">
                            <span class="fira-badge fira-badge-info" style="position: absolute; top: 12px; left: 12px;">
                                {{ $course->getAttribute('display_category') }}
                            </span>
                        </div>
                        <div class="fira-course-body">
                            <div class="fira-course-instructor">
                                Mentor: <strong>{{ optional($course->instructor)->name ?? 'Firaol Kebede' }}</strong>
                            </div>
                            <h4 style="font-size: 1.0625rem; font-weight: 700; color: var(--fira-text-primary); margin-bottom: 8px; line-height: 1.35;">
                                <a href="{{ route('courses.show', $course) }}" style="color: inherit; text-decoration: none;">
                                    {{ $course->title }}
                                </a>
                            </h4>
                            <p style="font-size: 0.8125rem; color: var(--fira-text-secondary); line-height: 1.6; margin-bottom: 16px;">
                                {{ Str::limit(strip_tags($course->description ?? ''), 110) }}
                            </p>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--fira-border-hairline);">
                                <div>
                                    <span style="font-size: 1rem; font-weight: 700; color: {{ $course->getAttribute('is_free') ? 'var(--fira-success)' : 'var(--fira-brand-deep)' }}; font-variant-numeric: tabular-nums;">
                                        {{ $course->getAttribute('is_free') ? 'Free Enrollment' : currency_format($course->getAttribute('display_price')) }}
                                    </span>
                                </div>
                                <a href="{{ route('courses.show', $course) }}" class="fira-btn fira-btn-secondary fira-btn-md" style="height: 38px; padding: 0 14px; font-size: 13px;">
                                    View course
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="fira-card text-center" style="padding: 40px;">
                        <p style="color: var(--fira-neutral-500);">No courses currently available.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- 6. Origin Expeditions & Tours Highlight -->
<section class="fira-home-band fira-home-band--muted">
    <div class="container lms-hero__container">
        <div class="fira-card" style="padding: 0; overflow: hidden; display: grid; grid-template-columns: 1.1fr 0.9fr; align-items: center;">
            <div style="padding: clamp(24px, 4vw, 44px);">
                <p class="fira-route"><span class="fira-route__line" aria-hidden="true"><i></i><i></i><i></i></span>Origin Expeditions</p>
                <h3 class="fira-home-h2" style="margin-bottom: 12px;">
                    Yirgacheffe &amp; Guji Harvest Coffee Safaris
                </h3>
                <p style="font-size: 0.9375rem; color: var(--fira-text-secondary); line-height: 1.65; margin-bottom: 24px;">
                    Join our expert-led origin expeditions. Walk the highland drying beds, cup with cooperative mill managers, evaluate fresh parchment on cupping tables, and establish verified direct trade relationships.
                </p>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="{{ route('tours.index') }}" class="lms-hero__primary" style="min-height: 42px; font-size: 13.5px;">
                        Explore trade tours
                    </a>
                    <a href="{{ route('contact') }}" class="lms-hero__secondary" style="min-height: 42px; font-size: 13.5px;">
                        Inquire for delegation
                    </a>
                </div>
            </div>
            <div style="height: 100%; min-height: 280px; background-image: url('{{ asset('storage/tours/tour-1-yirgacheffe-origin-expedition.jpg') }}'); background-size: cover; background-position: center;">
            </div>
        </div>
    </div>
</section>

<!-- 7. What Learners Say (Real Video Testimonials) -->
@include('partials.tiktok-testimonials')

<!-- 8. Brand Overview & Pillars Section (Core pillars of Fira Link Platform) -->
<section class="fira-brand-story-section fira-home-band">
    <div class="container lms-hero__container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="section-title mb-0">
                    <p class="fira-route"><span class="fira-route__line" aria-hidden="true"><i></i><i></i><i></i></span>Fira Link Platform and Fira Link Business Solution</p>
                    <h2 class="fira-home-h2">Practical import and export education for Ethiopia and East Africa</h2>
                </div>
                <div class="fira-home-prose">
                    <p>
                        <strong>Fira Link Platform</strong> is the digital learning and business development platform of <strong>Fira Link Business Solution</strong>, headquartered in Addis Ababa, Ethiopia. It connects business instruction with the way trade actually runs, for entrepreneurs, coffee producers, logistics officers and commercial importers who work across borders.
                    </p>
                    <p>
                        Our curriculum is built alongside experienced customs clearing agents, freight forwarders, and commercial bankers. Learners work through Ethiopian Electronic Single Window (eSW) declarations, HS tariff classification, Letter of Credit (LC) documentation under National Bank of Ethiopia (NBE) directives, and multimodal transport connecting Addis Ababa, Modjo Dry Port and the Port of Djibouti.
                    </p>
                    <div class="fira-home-actions">
                        <a href="{{ route('about') }}" class="lms-hero__primary">About Fira Link</a>
                        <a href="{{ route('programs') }}" class="lms-hero__secondary">Programs</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="fira-home-pillars">
                    <h3>Core pillars of Fira Link Platform</h3>

                    <div class="fira-home-pillar">
                        <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                        <div>
                            <h4>Customs and regulatory compliance</h4>
                            <p>Ethiopian Customs Commission procedures, valuation rules, eSW document uploads and origin certification.</p>
                        </div>
                    </div>

                    <div class="fira-home-pillar">
                        <i class="fa-solid fa-ship" aria-hidden="true"></i>
                        <div>
                            <h4>Corridor logistics and freight</h4>
                            <p>Transport costing, multimodal container tracking, avoiding demurrage, and marine insurance.</p>
                        </div>
                    </div>

                    <div class="fira-home-pillar">
                        <i class="fa-solid fa-mug-saucer" aria-hidden="true"></i>
                        <div>
                            <h4>Coffee and commodity trade</h4>
                            <p>Coffee grading and contracts, ECX exchange rules and direct origin export licensing.</p>
                        </div>
                    </div>

                    <div class="fira-home-pillar">
                        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                        <div>
                            <h4>Free trade tools</h4>
                            <p>Export costing calculators and coffee price references, free to use.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 9. Primary Sitelinks Directory Block (Explore Fira Link Platform) -->
<section class="fira-sitelinks-section fira-home-band fira-home-band--muted">
    <div class="container lms-hero__container">
        <div class="section-title text-center mb-5">
            <h2 class="fira-home-h2">Explore Fira Link Platform</h2>
            <p class="text-muted mt-2" style="max-width: 640px; margin: 0 auto;">
                Courses and programs, pricing, free trade tools and the people behind them
            </p>
        </div>

        @php
            $siteLinks = [
                ['route' => 'programs', 'icon' => 'fa-graduation-cap', 'title' => 'Programs', 'text' => 'A four-pillar trade curriculum: customs, logistics, coffee export and cross-border management.', 'more' => 'View programs'],
                ['route' => 'courses', 'icon' => 'fa-book-open', 'title' => 'Courses', 'text' => 'Self-paced and cohort video courses taught by verified Ethiopian trade practitioners.', 'more' => 'View courses'],
                ['route' => 'free-tools.index', 'icon' => 'fa-chart-line', 'title' => 'Free tools', 'text' => 'Coffee price references, corridor freight costing and an export margin calculator.', 'more' => 'Open free tools'],
                ['route' => 'pricing', 'icon' => 'fa-tags', 'title' => 'Pricing', 'text' => 'Tuition for individual online courses, corporate packages and trade expeditions.', 'more' => 'View pricing'],
                ['route' => 'about', 'icon' => 'fa-building-columns', 'title' => 'About', 'text' => 'Our founding mission, leadership and connection to Fira Link Business Solution.', 'more' => 'Read about us'],
                ['route' => 'blog.index', 'icon' => 'fa-newspaper', 'title' => 'Blog', 'text' => 'Market updates, regulatory notes, customs notices and export guides for Ethiopian trade.', 'more' => 'Read the blog'],
                ['route' => 'faq', 'icon' => 'fa-circle-question', 'title' => 'FAQ', 'text' => 'Enrollment, payment through Telebirr or CBE, certificates and logistics.', 'more' => 'Read the FAQ'],
                ['route' => 'contact', 'icon' => 'fa-headset', 'title' => 'Contact', 'text' => 'Speak with our admissions and trade advisory team at our Bole Sub-City offices in Addis Ababa.', 'more' => 'Contact us'],
            ];
        @endphp
        <div class="row g-4">
            @foreach ($siteLinks as $link)
                <div class="col-lg-3 col-md-6">
                    <div class="fira-home-link">
                        <div class="fira-home-link__head">
                            <i class="fa-solid {{ $link['icon'] }} fira-home-link__icon" aria-hidden="true"></i>
                            <h3><a href="{{ route($link['route']) }}">{{ $link['title'] }}</a></h3>
                        </div>
                        <p>{{ $link['text'] }}</p>
                        <a href="{{ route($link['route']) }}" class="fira-home-link__more">{{ $link['more'] }} <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i></a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
