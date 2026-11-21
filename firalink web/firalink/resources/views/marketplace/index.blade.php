@extends('layouts.app')

@section('title', 'Ethiopian Coffee B2B Marketplace | Direct Export Sourcing | Fira Link')

@php
    $activeFilterCount = collect(['origin', 'processing', 'grade', 'min_score'])->filter(fn ($key) => request()->filled($key))->count();
    $currentSort = request('sort', 'featured');
@endphp

@push('styles')
<style>
    .marketplace-page {
        --mkt-brand: #6D1A95;
        --mkt-brand-dark: #3B0764;
        --mkt-brand-soft: #F3E8FF;
        --mkt-brand-border: #E9E4F0;
        --mkt-ink: #1E1B4B;
        --mkt-body: #4A4658;
        --mkt-muted: #6B7280;
        --mkt-line: #E9E4F0;
        --mkt-surface: #FFFFFF;
        min-height: 100vh;
        padding-bottom: 72px;
        color: var(--mkt-ink);
        background: #F9F7FA;
        font-family: 'Inter', sans-serif;
    }

    .marketplace-page *, .marketplace-page *::before, .marketplace-page *::after { box-sizing: border-box; }
    .mkt-shell {
        width: 100%;
        max-width: 1200px;
        margin-inline: auto;
        padding-inline: 24px;
    }

    .mkt-hero {
        position: relative;
        overflow: hidden;
        padding: 66px 0 74px;
        color: #fff;
        background: var(--mkt-brand-dark);
    }
    .mkt-hero::before {
        content: '';
        position: absolute;
        width: 520px;
        height: 520px;
        top: -310px;
        right: -70px;
        border: 1px solid rgba(255,255,255,.14);
        border-radius: 50%;
        box-shadow: 0 0 0 90px rgba(255,255,255,.035), 0 0 0 180px rgba(255,255,255,.025);
    }
    .mkt-hero-inner { position: relative; z-index: 1; max-width: 900px; }
    .mkt-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 16px;
        color: #f2ddfb;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
    .mkt-eyebrow::before { content: ''; width: 24px; height: 2px; border-radius: 2px; background: #ffffff; }
    .mkt-hero h1 { max-width: 790px; margin: 0; color: #fff; font-size: clamp(32px, 4vw, 52px); font-weight: 750; line-height: 1.08; letter-spacing: -.035em; }
    .mkt-hero-copy { max-width: 730px; margin: 18px 0 0; color: #eadff0; font-size: 16px; line-height: 1.75; }

    .mkt-search-form { margin-top: 30px; }
    .mkt-search {
        display: grid;
        grid-template-columns: minmax(280px, 1fr) 190px auto;
        align-items: center;
        gap: 8px;
        max-width: 900px;
        padding: 8px;
        border: 1px solid rgba(255,255,255,.35);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 18px 48px rgba(15, 3, 21, .28);
    }
    .mkt-search-field { min-width: 0; display: flex; align-items: center; gap: 11px; padding: 0 12px; color: #8b8290; }
    .mkt-search-field svg { width: 19px; flex: 0 0 auto; }
    .mkt-search input, .mkt-search select {
        width: 100%;
        height: 44px;
        margin: 0;
        border: 0;
        outline: 0;
        color: var(--mkt-ink);
        background: transparent;
        box-shadow: none;
        font: 500 14px/1.3 'Inter', sans-serif;
    }
    .mkt-search-region { padding-inline: 14px; border-left: 1px solid var(--mkt-line); }

    .mkt-btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 18px;
        border: 1px solid transparent;
        border-radius: 10px;
        font: 650 13px/1 'Inter', sans-serif;
        text-decoration: none !important;
        white-space: nowrap;
        cursor: pointer;
        transition: color .2s ease, background-color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .mkt-btn svg { width: 16px; height: 16px; }
    .mkt-btn-primary { color: #fff !important; background: var(--mkt-brand); box-shadow: 0 7px 18px rgba(109,26,149,.22); }
    .mkt-btn-primary:hover { color: #fff !important; background: var(--mkt-brand-dark); transform: translateY(-1px); box-shadow: 0 9px 22px rgba(109,26,149,.28); }
    .mkt-btn-secondary { color: var(--mkt-brand) !important; border-color: var(--mkt-brand-border); background: #fff; }
    .mkt-btn-secondary:hover { color: var(--mkt-brand-dark) !important; border-color: #cda8df; background: var(--mkt-brand-soft); }
    .mkt-btn:active { transform: translateY(0); box-shadow: none; }
    .mkt-btn:disabled, .mkt-btn[aria-disabled='true'] { opacity: .52; cursor: not-allowed; pointer-events: none; box-shadow: none; }

    .mkt-quick-links { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 17px; }
    .mkt-quick-label { margin-right: 2px; color: #d8c6e0; font-size: 12px; font-weight: 600; }
    .mkt-origin-pill { padding: 6px 12px; border: 1px solid rgba(255,255,255,.23); border-radius: 999px; color: #fff !important; background: rgba(255,255,255,.09); font-size: 12px; font-weight: 550; text-decoration: none !important; transition: .2s ease; }
    .mkt-origin-pill:hover, .mkt-origin-pill.is-active { border-color: rgba(255,255,255,.65); background: rgba(255,255,255,.2); }

    .mkt-content { margin-top: 36px; }
    .mkt-mobile-filter { display: none; margin-bottom: 16px; }
    .mkt-mobile-filter button { width: 100%; justify-content: space-between; }
    .mkt-filter-count { min-width: 22px; height: 22px; display: inline-grid; place-items: center; border-radius: 999px; color: #fff; background: var(--mkt-brand); font-size: 11px; }
    .mkt-layout { display: grid; grid-template-columns: 268px minmax(0, 1fr); align-items: start; gap: 28px; }

    .mkt-filter-card { padding: 22px; border: 1px solid var(--mkt-line); border-radius: 16px; background: #fff; box-shadow: 0 6px 24px rgba(42, 30, 46, .05); }
    .mkt-filter-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 17px; border-bottom: 1px solid var(--mkt-line); }
    .mkt-filter-head h2 { display: flex; align-items: center; gap: 8px; margin: 0; color: var(--mkt-ink); font-size: 15px; font-weight: 700; }
    .mkt-filter-head svg { width: 16px; color: var(--mkt-brand); }
    .mkt-reset { color: var(--mkt-brand); font-size: 12px; font-weight: 650; text-decoration: none; }
    .mkt-reset:hover { color: var(--mkt-brand-dark); text-decoration: underline; }
    .mkt-filter-group { padding-top: 20px; }
    fieldset.mkt-filter-group { min-width: 0; margin: 0; padding-right: 0; padding-bottom: 0; padding-left: 0; border: 0; }
    .mkt-filter-label { display: block; margin-bottom: 9px; color: #4c4450; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .mkt-select { width: 100%; height: 44px; padding: 0 36px 0 12px; border: 1px solid #ddd7df; border-radius: 10px; color: var(--mkt-ink); background-color: #fff; font: 500 13px/1 'Inter', sans-serif; }
    .mkt-radio-list { display: grid; gap: 9px; }
    .mkt-radio { display: flex; align-items: center; gap: 9px; color: var(--mkt-body); font-size: 13px; cursor: pointer; }
    .mkt-radio input { width: 16px; height: 16px; margin: 0; accent-color: var(--mkt-brand); }
    .mkt-filter-submit { width: 100%; margin-top: 22px; }

    .mkt-rfq-card { margin-top: 16px; padding: 20px; border: 1px solid #eed8ad; border-radius: 16px; background: #fffaf0; }
    .mkt-rfq-icon { width: 38px; height: 38px; display: grid; place-items: center; margin-bottom: 13px; border-radius: 10px; color: #a85a05; background: #ffedc7; }
    .mkt-rfq-icon svg { width: 19px; }
    .mkt-rfq-card h3 { margin: 0; color: #5d390a; font-size: 14px; font-weight: 700; }
    .mkt-rfq-card p { margin: 7px 0 15px; color: #795b32; font-size: 12px; line-height: 1.65; }
    .mkt-rfq-card .mkt-btn { width: 100%; color: #8a4900 !important; border-color: #e8c17c; background: #fff; }
    .mkt-rfq-card .mkt-btn:hover { background: #ffedc7; }

    .mkt-results { min-width: 0; }
    .mkt-results-toolbar { min-height: 66px; display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 22px; padding: 12px 16px 12px 20px; border: 1px solid var(--mkt-line); border-radius: 14px; background: #fff; }
    .mkt-result-count { margin: 0; color: var(--mkt-muted); font-size: 13px; }
    .mkt-result-count strong { color: var(--mkt-ink); font-weight: 700; }
    .mkt-sort { display: flex; align-items: center; gap: 6px; }
    .mkt-sort-label { margin-right: 3px; color: var(--mkt-muted); font-size: 12px; font-weight: 600; }
    .mkt-sort-link { min-height: 34px; display: inline-flex; align-items: center; padding: 0 11px; border: 1px solid transparent; border-radius: 8px; color: var(--mkt-body); background: #f7f6f8; font-size: 11px; font-weight: 650; text-decoration: none !important; transition: .18s ease; }
    .mkt-sort-link:hover { color: var(--mkt-brand); border-color: var(--mkt-brand-border); background: var(--mkt-brand-soft); }
    .mkt-sort-link.is-active { color: #fff; background: var(--mkt-brand); }

    .mkt-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
    .mkt-card { min-width: 0; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--mkt-line); border-radius: 16px; background: #fff; box-shadow: 0 6px 20px rgba(42,30,46,.045); transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease; }
    .mkt-card:hover { transform: translateY(-3px); border-color: var(--mkt-brand-border); box-shadow: 0 16px 34px rgba(73,31,88,.11); }
    .mkt-card-media { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: var(--mkt-brand-soft); }
    .mkt-card-media img { width: 100%; height: 100%; display: block; object-fit: cover; transition: transform .35s ease; }
    .mkt-card:hover .mkt-card-media img { transform: scale(1.025); }
    .mkt-image-fallback { width: 100%; height: 100%; display: grid; place-items: center; color: #ba93ca; }
    .mkt-image-fallback svg { width: 46px; }
    .mkt-score { position: absolute; top: 12px; right: 12px; padding: 6px 9px; border-radius: 8px; color: #fff; background: rgba(59,7,100,.92); backdrop-filter: blur(4px); box-shadow: 0 4px 12px rgba(31,8,41,.2); font-size: 11px; font-weight: 750; font-variant-numeric: tabular-nums; }
    .mkt-origin { position: absolute; bottom: 12px; left: 12px; padding: 6px 9px; border-radius: 7px; color: #fff; background: rgba(20,15,22,.74); backdrop-filter: blur(7px); font-size: 11px; font-weight: 600; }
    .mkt-card-body { min-height: 240px; display: flex; flex: 1; flex-direction: column; padding: 18px; }
    .mkt-seller { min-width: 0; display: flex; align-items: center; gap: 5px; margin-bottom: 8px; color: var(--mkt-muted); font-size: 11px; font-weight: 550; }
    .mkt-seller span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mkt-seller svg { width: 15px; flex: 0 0 auto; color: var(--mkt-brand); }
    .mkt-card h3 { margin: 0; color: var(--mkt-ink); font-size: 16px; font-weight: 700; line-height: 1.4; letter-spacing: -.01em; }
    .mkt-card h3 a { color: inherit; text-decoration: none; }
    .mkt-card h3 a:hover { color: var(--mkt-brand); }
    .mkt-specs { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
    .mkt-spec { padding: 5px 8px; border-radius: 6px; color: #625869; background: #f5f3f6; font-size: 10px; font-weight: 650; text-transform: capitalize; }
    .mkt-notes { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; }
    .mkt-note { padding: 4px 7px; border: 1px solid var(--mkt-brand-border); border-radius: 999px; color: var(--mkt-brand); background: var(--mkt-brand-soft); font-size: 10px; font-weight: 600; }
    .mkt-card-footer { display: flex; align-items: end; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 16px; border-top: 1px solid var(--mkt-line); }
    .mkt-price-label { margin-bottom: 2px; color: var(--mkt-muted); font-size: 9px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
    .mkt-price { color: var(--mkt-ink); font-size: 17px; font-weight: 750; font-variant-numeric: tabular-nums; }
    .mkt-price small { color: var(--mkt-muted); font-size: 10px; font-weight: 500; }
    .mkt-card-action { min-height: 38px; padding-inline: 13px; }

    .mkt-empty { grid-column: 1 / -1; padding: 64px 24px; border: 1px solid var(--mkt-line); border-radius: 16px; background: #fff; text-align: center; }
    .mkt-empty-icon { width: 58px; height: 58px; display: grid; place-items: center; margin: 0 auto 14px; border-radius: 50%; color: var(--mkt-brand); background: var(--mkt-brand-soft); }
    .mkt-empty-icon svg { width: 27px; }
    .mkt-empty h3 { margin: 0; color: var(--mkt-ink); font-size: 18px; }
    .mkt-empty p { max-width: 440px; margin: 8px auto 18px; color: var(--mkt-muted); font-size: 13px; line-height: 1.65; }
    .mkt-pagination { margin-top: 30px; }

    .marketplace-page :is(a, button, input, select):focus-visible { outline: 3px solid rgba(109,26,149,.3); outline-offset: 3px; }
    .marketplace-page :is(input, select):focus { border-color: var(--mkt-brand); box-shadow: 0 0 0 3px rgba(109,26,149,.1); }

    @media (max-width: 1180px) { .mkt-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 991px) {
        .mkt-hero { padding: 52px 0 60px; }
        .mkt-mobile-filter { display: block; }
        .mkt-layout { grid-template-columns: 1fr; gap: 0; }
        .mkt-filter-sidebar { display: none; margin-bottom: 20px; }
        .mkt-filter-sidebar.is-open { display: block; }
        .mkt-filter-card { box-shadow: none; }
        .mkt-rfq-card { display: none; }
    }
    @media (max-width: 767px) {
        .mkt-shell { padding-inline: 16px; }
        .mkt-hero { padding: 42px 0 48px; }
        .mkt-hero-copy { font-size: 14px; line-height: 1.65; }
        .mkt-search { grid-template-columns: 1fr; gap: 4px; padding: 10px; }
        .mkt-search-region { border-top: 1px solid var(--mkt-line); border-left: 0; }
        .mkt-search .mkt-btn { width: 100%; margin-top: 3px; }
        .mkt-quick-label { width: 100%; }
        .mkt-content { margin-top: 24px; }
        .mkt-results-toolbar { align-items: flex-start; flex-direction: column; }
        .mkt-sort { width: 100%; overflow-x: auto; padding-bottom: 3px; }
        .mkt-sort-label { display: none; }
        .mkt-grid { grid-template-columns: 1fr; }
        .mkt-card-media { aspect-ratio: 16 / 9; }
    }
    @media (max-width: 420px) {
        .mkt-card-footer { align-items: stretch; flex-direction: column; }
        .mkt-card-action { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) { .marketplace-page *, .marketplace-page *::before, .marketplace-page *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
</style>
@endpush

@section('content')
<div class="marketplace-page">
    <section class="mkt-hero" aria-labelledby="marketplace-title">
        <div class="mkt-shell">
            <div class="mkt-hero-inner">
                <div class="mkt-eyebrow">Ethiopian Coffee Marketplace</div>
                <h1 id="marketplace-title">Source exceptional coffee from verified Ethiopian exporters</h1>
                <p class="mkt-hero-copy">Discover traceable green coffee lots from Ethiopia's leading origins, compare verified details, and connect directly with licensed suppliers.</p>

                <form action="{{ route('marketplace.index') }}" method="GET" class="mkt-search-form" role="search">
                    <div class="mkt-search">
                        <label class="mkt-search-field">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                            <span class="visually-hidden">Search coffee lots</span>
                            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search lots, regions, stations, or varieties">
                        </label>
                        <div class="mkt-search-region">
                            <label class="visually-hidden" for="hero-origin">Coffee origin</label>
                            <select id="hero-origin" name="origin">
                                <option value="">All regions</option>
                                @foreach($taxonomies['origins'] as $orig)
                                    <option value="{{ $orig }}" @selected(request('origin') == $orig)>{{ $orig }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="mkt-btn mkt-btn-primary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                            Search lots
                        </button>
                    </div>
                </form>

                <div class="mkt-quick-links" aria-label="Popular coffee origins">
                    <span class="mkt-quick-label">Popular origins</span>
                    @foreach(['Sidama', 'Yirgacheffe', 'Guji', 'Limu', 'Harrar'] as $quickOrigin)
                        <a href="{{ route('marketplace.index', ['origin' => $quickOrigin]) }}" class="mkt-origin-pill {{ request('origin') == $quickOrigin ? 'is-active' : '' }}">{{ $quickOrigin }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="mkt-shell mkt-content">
        <div class="mkt-mobile-filter">
            <button type="button" class="mkt-btn mkt-btn-secondary" data-filter-toggle aria-expanded="false" aria-controls="marketplace-filters">
                <span>Filters {{ $activeFilterCount ? '(' . $activeFilterCount . ' active)' : '' }}</span>
                <span class="mkt-filter-count" aria-hidden="true">{{ $activeFilterCount }}</span>
            </button>
        </div>

        <div class="mkt-layout">
            <aside id="marketplace-filters" class="mkt-filter-sidebar {{ $activeFilterCount ? 'is-open' : '' }}" aria-label="Coffee lot filters">
                <form action="{{ route('marketplace.index') }}" method="GET" class="mkt-filter-card">
                    @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                    @if(request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif
                    <div class="mkt-filter-head">
                        <h2><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12m-9 7h6"/></svg>Refine results</h2>
                        <a href="{{ route('marketplace.index') }}" class="mkt-reset">Reset</a>
                    </div>

                    <div class="mkt-filter-group">
                        <label for="filter-origin" class="mkt-filter-label">Origin region</label>
                        <select id="filter-origin" name="origin" class="mkt-select">
                            <option value="">All origins</option>
                            @foreach($taxonomies['origins'] as $orig)
                                <option value="{{ $orig }}" @selected(request('origin') == $orig)>{{ $orig }}</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset class="mkt-filter-group">
                        <legend class="mkt-filter-label">Processing method</legend>
                        <div class="mkt-radio-list">
                            @foreach($taxonomies['processes'] as $proc)
                                <label class="mkt-radio"><input type="radio" name="processing" value="{{ $proc['value'] }}" @checked(request('processing') == $proc['value'])><span>{{ $proc['label'] }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="mkt-filter-group">
                        <label for="filter-grade" class="mkt-filter-label">Grade</label>
                        <select id="filter-grade" name="grade" class="mkt-select">
                            <option value="">All grades</option>
                            @foreach($taxonomies['grades'] as $grade)
                                <option value="{{ $grade['value'] }}" @selected(request('grade') == $grade['value'])>{{ $grade['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mkt-filter-group">
                        <label for="filter-score" class="mkt-filter-label">Minimum SCA score</label>
                        <select id="filter-score" name="min_score" class="mkt-select">
                            <option value="">Any score</option>
                            <option value="85" @selected(request('min_score') == '85')>85+ Specialty</option>
                            <option value="87" @selected(request('min_score') == '87')>87+ High specialty</option>
                            <option value="89" @selected(request('min_score') == '89')>89+ Exceptional</option>
                        </select>
                    </div>
                    <button type="submit" class="mkt-btn mkt-btn-primary mkt-filter-submit">Apply filters</button>
                </form>

                <div class="mkt-rfq-card">
                    <div class="mkt-rfq-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M8 4h7l4 4v12H5V4h3Z"/></svg></div>
                    <h3>Need a specific volume?</h3>
                    <p>Publish a sourcing request and receive offers from verified exporters.</p>
                </div>
            </aside>

            <main class="mkt-results" aria-label="Coffee lots">
                <div class="mkt-results-toolbar">
                    <p class="mkt-result-count">Showing <strong>{{ $listings->firstItem() ?? 0 }}–{{ $listings->lastItem() ?? 0 }}</strong> of <strong>{{ $listings->total() }}</strong> available lots</p>
                    <nav class="mkt-sort" aria-label="Sort marketplace listings">
                        <span class="mkt-sort-label">Sort by</span>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'featured']) }}" class="mkt-sort-link {{ $currentSort === 'featured' ? 'is-active' : '' }}" @if($currentSort === 'featured') aria-current="page" @endif>Featured</a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'score_desc']) }}" class="mkt-sort-link {{ $currentSort === 'score_desc' ? 'is-active' : '' }}" @if($currentSort === 'score_desc') aria-current="page" @endif>Highest score</a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}" class="mkt-sort-link {{ $currentSort === 'price_asc' ? 'is-active' : '' }}" @if($currentSort === 'price_asc') aria-current="page" @endif>Lowest price</a>
                    </nav>
                </div>

                <div class="mkt-grid">
                    @forelse($listings as $lot)
                        <article class="mkt-card">
                            <div class="mkt-card-media">
                                @if($lot->primaryImage && $lot->primaryImage->url)
                                    <img src="{{ $lot->primaryImage->url }}" alt="{{ $lot->title }}" loading="lazy">
                                @else
                                    <div class="mkt-image-fallback" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4 16 4.6-4.6a2 2 0 0 1 2.8 0L16 16m-2-2 1.6-1.6a2 2 0 0 1 2.8 0L20 14M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg></div>
                                @endif
                                @if($lot->cupping_score)<span class="mkt-score">{{ number_format($lot->cupping_score, 2) }} SCA</span>@endif
                                <span class="mkt-origin">{{ $lot->origin_region }}</span>
                            </div>

                            <div class="mkt-card-body">
                                <div class="mkt-seller">
                                    <span>{{ $lot->seller->sellerProfile->company_name ?? $lot->seller->name }}</span>
                                    @if($lot->seller->isVerifiedSeller())
                                        <svg fill="currentColor" viewBox="0 0 20 20" aria-label="Verified exporter"><path fill-rule="evenodd" d="M6.27 3.46a3.07 3.07 0 0 0 1.74-.73 3.07 3.07 0 0 1 3.98 0c.49.42 1.1.67 1.74.73a3.07 3.07 0 0 1 2.81 2.81c.06.64.31 1.25.73 1.74a3.07 3.07 0 0 1 0 3.98c-.42.49-.67 1.1-.73 1.74a3.07 3.07 0 0 1-2.81 2.81 3.07 3.07 0 0 0-1.74.73 3.07 3.07 0 0 1-3.98 0 3.07 3.07 0 0 0-1.74-.73 3.07 3.07 0 0 1-2.81-2.81 3.07 3.07 0 0 0-.73-1.74 3.07 3.07 0 0 1 0-3.98c.42-.49.67-1.1.73-1.74a3.07 3.07 0 0 1 2.81-2.81Zm7.44 5.25a1 1 0 0 0-1.42-1.42L9 10.59 7.71 9.29a1 1 0 0 0-1.42 1.42l2 2a1 1 0 0 0 1.42 0l4-4Z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>
                                <h3><a href="{{ route('marketplace.show', $lot->slug) }}">{{ $lot->title }}</a></h3>
                                <div class="mkt-specs">
                                    <span class="mkt-spec">{{ str_replace('_', ' ', $lot->processing_method) }}</span>
                                    <span class="mkt-spec">{{ str_replace('_', ' ', $lot->grade) }}</span>
                                    <span class="mkt-spec">{{ $lot->crop_year }}</span>
                                </div>
                                @if(!empty($lot->tasting_notes))
                                    <div class="mkt-notes" aria-label="Tasting notes">
                                        @foreach(array_slice($lot->tasting_notes, 0, 3) as $note)<span class="mkt-note">{{ $note }}</span>@endforeach
                                    </div>
                                @endif
                                <div class="mkt-card-footer">
                                    <div><div class="mkt-price-label">Indicative FOB</div><div class="mkt-price">${{ number_format($lot->indicative_price_usd_per_lb, 2) }} <small>/lb</small></div></div>
                                    <a href="{{ route('marketplace.show', $lot->slug) }}" class="mkt-btn mkt-btn-secondary mkt-card-action">View lot <span aria-hidden="true">→</span></a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="mkt-empty">
                            <div class="mkt-empty-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4m-14 0h14a2 2 0 0 1 2 2v6H3v-6a2 2 0 0 1 2-2Z"/></svg></div>
                            <h3>No coffee lots found</h3>
                            <p>No available lots match these filters. Adjust your search criteria to explore more of the current marketplace.</p>
                            <a href="{{ route('marketplace.index') }}" class="mkt-btn mkt-btn-primary">Clear filters</a>
                        </div>
                    @endforelse
                </div>

                @if($listings->hasPages())<div class="mkt-pagination">{{ $listings->withQueryString()->links() }}</div>@endif
            </main>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('[data-filter-toggle]');
    const panel = document.getElementById('marketplace-filters');
    if (!toggle || !panel) return;
    toggle.setAttribute('aria-expanded', String(panel.classList.contains('is-open')));
    toggle.addEventListener('click', function () {
        const open = panel.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(open));
        if (open) panel.querySelector('select, input, button, a')?.focus();
    });
});
</script>
@endpush
