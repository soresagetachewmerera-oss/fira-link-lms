@extends('layouts.app')

@section('title', 'Verified Ethiopian Coffee Exporters & Washing Stations | Fira Link')

@php
    $activeFilterCount = collect(['search', 'type', 'origin'])->filter(fn ($key) => request()->filled($key))->count();
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
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .marketplace-page *, .marketplace-page *::before, .marketplace-page *::after {
        box-sizing: border-box;
    }

    .mkt-shell {
        width: 100%;
        max-width: 1200px;
        margin-inline: auto;
        padding-inline: 24px;
    }

    /* Hero Section */
    .mkt-hero {
        position: relative;
        overflow: hidden;
        padding: 56px 0 64px;
        color: #fff;
        background: linear-gradient(135deg, #1b0726 0%, #3d0e54 50%, #6d1a95 100%);
    }

    .mkt-hero::before {
        content: '';
        position: absolute;
        width: 520px;
        height: 520px;
        top: -300px;
        right: -60px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 50%;
        box-shadow: 0 0 0 80px rgba(255, 255, 255, 0.03), 0 0 0 160px rgba(255, 255, 255, 0.02);
        pointer-events: none;
    }

    .mkt-hero-inner {
        position: relative;
        z-index: 1;
        max-width: 960px;
    }

    .mkt-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #f3e8ff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .mkt-eyebrow .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #34d399;
        box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.25);
    }

    .mkt-hero h1 {
        margin: 0;
        color: #ffffff;
        font-size: clamp(28px, 3.8vw, 46px);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.03em;
    }

    .mkt-hero-copy {
        max-width: 720px;
        margin: 14px 0 0;
        color: #f3e8ff;
        font-size: 15px;
        line-height: 1.65;
        opacity: 0.95;
    }

    /* Hero Search Form */
    .mkt-search-form {
        margin-top: 28px;
    }

    .mkt-search {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 210px 180px auto;
        align-items: center;
        gap: 8px;
        max-width: 980px;
        padding: 8px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 16px 40px rgba(15, 3, 21, 0.24);
    }

    .mkt-search-field {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        color: #8b8290;
    }

    .mkt-search-field svg {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        color: #8b8290;
    }

    .mkt-search input,
    .mkt-search select {
        width: 100%;
        height: 44px;
        margin: 0;
        padding: 0;
        border: 0;
        outline: 0;
        color: var(--mkt-ink);
        background: transparent;
        box-shadow: none;
        font: 500 14px/1.3 'Inter', sans-serif;
    }

    .mkt-search-divider {
        padding-inline: 12px;
        border-left: 1px solid var(--mkt-line);
    }

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
        transition: all 0.2s ease;
    }

    .mkt-btn svg {
        width: 16px;
        height: 16px;
    }

    .mkt-btn-primary {
        color: #ffffff !important;
        background: var(--mkt-brand);
        box-shadow: 0 6px 16px rgba(109, 26, 149, 0.22);
    }

    .mkt-btn-primary:hover {
        background: var(--mkt-brand-dark);
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(109, 26, 149, 0.28);
    }

    .mkt-btn-secondary {
        color: var(--mkt-brand) !important;
        border-color: var(--mkt-brand-border);
        background: #ffffff;
    }

    .mkt-btn-secondary:hover {
        color: var(--mkt-brand-dark) !important;
        border-color: #cda8df;
        background: var(--mkt-brand-soft);
    }

    .mkt-btn-ghost {
        color: rgba(255, 255, 255, 0.95) !important;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .mkt-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff !important;
    }

    .mkt-quick-links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 18px;
    }

    .mkt-quick-label {
        margin-right: 4px;
        color: #e9d5ff;
        font-size: 12px;
        font-weight: 600;
    }

    .mkt-origin-pill {
        padding: 5px 12px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 999px;
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.08);
        font-size: 12px;
        font-weight: 550;
        text-decoration: none !important;
        transition: 0.18s ease;
    }

    .mkt-origin-pill:hover,
    .mkt-origin-pill.is-active {
        border-color: #ffffff;
        background: rgba(255, 255, 255, 0.24);
    }

    /* Main Content Layout */
    .mkt-content {
        margin-top: 36px;
    }

    .mkt-mobile-filter {
        display: none;
        margin-bottom: 16px;
    }

    .mkt-mobile-filter button {
        width: 100%;
        justify-content: space-between;
    }

    .mkt-filter-count {
        min-width: 22px;
        height: 22px;
        display: inline-grid;
        place-items: center;
        border-radius: 999px;
        color: #ffffff;
        background: var(--mkt-brand);
        font-size: 11px;
    }

    .mkt-layout {
        display: grid;
        grid-template-columns: 270px minmax(0, 1fr);
        align-items: start;
        gap: 28px;
    }

    /* Filter Sidebar */
    .mkt-filter-card {
        padding: 22px;
        border: 1px solid var(--mkt-line);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 18px rgba(42, 30, 46, 0.04);
    }

    .mkt-filter-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--mkt-line);
    }

    .mkt-filter-head h2 {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: var(--mkt-ink);
        font-size: 15px;
        font-weight: 700;
    }

    .mkt-filter-head svg {
        width: 16px;
        height: 16px;
        color: var(--mkt-brand);
    }

    .mkt-reset {
        color: var(--mkt-brand);
        font-size: 12px;
        font-weight: 650;
        text-decoration: none;
    }

    .mkt-reset:hover {
        color: var(--mkt-brand-dark);
        text-decoration: underline;
    }

    .mkt-filter-group {
        padding-top: 18px;
    }

    .mkt-filter-label {
        display: block;
        margin-bottom: 8px;
        color: #4c4450;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .mkt-select {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        border: 1px solid #ddd7df;
        border-radius: 10px;
        color: var(--mkt-ink);
        background-color: #ffffff;
        font: 500 13px/1 'Inter', sans-serif;
    }

    .mkt-filter-submit {
        width: 100%;
        margin-top: 20px;
    }

    .mkt-rfq-card {
        margin-top: 16px;
        padding: 20px;
        border: 1px solid #eed8ad;
        border-radius: 16px;
        background: #fffaf0;
    }

    .mkt-rfq-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        margin-bottom: 12px;
        border-radius: 10px;
        color: #a85a05;
        background: #ffedc7;
    }

    .mkt-rfq-icon svg {
        width: 18px;
        height: 18px;
    }

    .mkt-rfq-card h3 {
        margin: 0;
        color: #5d390a;
        font-size: 14px;
        font-weight: 700;
    }

    .mkt-rfq-card p {
        margin: 6px 0 14px;
        color: #795b32;
        font-size: 12px;
        line-height: 1.6;
    }

    .mkt-rfq-card .mkt-btn {
        width: 100%;
        color: #8a4900 !important;
        border-color: #e8c17c;
        background: #ffffff;
    }

    .mkt-rfq-card .mkt-btn:hover {
        background: #ffedc7;
    }

    /* Results Toolbar */
    .mkt-results {
        min-width: 0;
    }

    .mkt-results-toolbar {
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 22px;
        padding: 12px 18px;
        border: 1px solid var(--mkt-line);
        border-radius: 14px;
        background: #ffffff;
    }

    .mkt-result-count {
        margin: 0;
        color: var(--mkt-muted);
        font-size: 13px;
    }

    .mkt-result-count strong {
        color: var(--mkt-ink);
        font-weight: 700;
    }

    .mkt-active-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    .mkt-filter-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 9px;
        border-radius: 999px;
        background: var(--mkt-brand-soft);
        border: 1px solid var(--mkt-brand-border);
        color: var(--mkt-brand);
        font-size: 11px;
        font-weight: 600;
    }

    .mkt-filter-tag a {
        color: var(--mkt-brand);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .mkt-filter-tag a:hover {
        color: var(--mkt-brand-dark);
    }

    /* Supplier Cards Grid */
    .mkt-suppliers-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .mkt-supplier-card {
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 22px;
        border: 1px solid var(--mkt-line);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 18px rgba(42, 30, 46, 0.04);
        transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
    }

    .mkt-supplier-card:hover {
        transform: translateY(-3px);
        border-color: var(--mkt-brand-border);
        box-shadow: 0 14px 30px rgba(109, 26, 149, 0.1);
    }

    /* Card Header */
    .mkt-supplier-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .mkt-supplier-identity {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mkt-supplier-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6d1a95 0%, #4a1066 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 17px;
        display: grid;
        place-items: center;
        border: 1px solid #c084fc;
        flex: 0 0 auto;
        box-shadow: 0 4px 10px rgba(109, 26, 149, 0.18);
    }

    .mkt-supplier-title-wrap {
        min-width: 0;
    }

    .mkt-supplier-name {
        margin: 0;
        font-size: 16px;
        font-weight: 750;
        line-height: 1.25;
        letter-spacing: -0.015em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mkt-supplier-name a {
        color: var(--mkt-ink);
        text-decoration: none;
        transition: color 0.18s ease;
    }

    .mkt-supplier-name a:hover {
        color: var(--mkt-brand);
    }

    .mkt-supplier-location {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 3px;
        color: var(--mkt-muted);
        font-size: 12px;
        font-weight: 500;
    }

    .mkt-supplier-location svg {
        width: 14px;
        height: 14px;
        color: var(--mkt-brand);
        flex: 0 0 auto;
    }

    .mkt-verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .mkt-verified-badge svg {
        width: 13px;
        height: 13px;
        color: #059669;
    }

    .mkt-type-pill {
        display: inline-block;
        margin-bottom: 10px;
        padding: 3px 8px;
        border-radius: 6px;
        background: #f4f3f6;
        color: #554a5c;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .mkt-supplier-bio {
        margin: 0 0 14px;
        color: var(--mkt-body);
        font-size: 13px;
        line-height: 1.55;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Metrics Row */
    .mkt-metrics-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 10px;
        margin-bottom: 14px;
        border: 1px solid var(--mkt-line);
        border-radius: 12px;
        background: #faf9fb;
        text-align: center;
    }

    .mkt-metric-cell {
        min-width: 0;
    }

    .mkt-metric-cell:not(:last-child) {
        border-right: 1px solid var(--mkt-line);
    }

    .mkt-metric-label {
        display: block;
        color: var(--mkt-muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .mkt-metric-value {
        display: block;
        color: var(--mkt-ink);
        font-size: 13px;
        font-weight: 750;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mkt-metric-value.highlight {
        color: var(--mkt-brand);
    }

    /* Origins Handled */
    .mkt-origins-wrap {
        margin-bottom: 16px;
    }

    .mkt-origins-label {
        display: block;
        margin-bottom: 6px;
        color: var(--mkt-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .mkt-origins-list {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .mkt-origin-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: var(--mkt-brand-soft);
        border: 1px solid var(--mkt-brand-border);
        color: var(--mkt-brand);
        font-size: 11px;
        font-weight: 600;
    }

    /* Card Footer */
    .mkt-card-footer {
        padding-top: 14px;
        border-top: 1px solid var(--mkt-line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .mkt-license-tag {
        color: var(--mkt-muted);
        font-size: 11px;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mkt-license-tag code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #4b5563;
        font-size: 11px;
        font-weight: 600;
    }

    .mkt-view-profile-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: var(--mkt-brand);
        font-size: 12px;
        font-weight: 700;
        text-decoration: none !important;
        white-space: nowrap;
        transition: color 0.18s ease, transform 0.18s ease;
    }

    .mkt-view-profile-btn:hover {
        color: var(--mkt-brand-dark);
        transform: translateX(2px);
    }

    .mkt-view-profile-btn svg {
        width: 14px;
        height: 14px;
    }

    /* Empty State */
    .mkt-empty {
        padding: 64px 24px;
        border: 1px solid var(--mkt-line);
        border-radius: 16px;
        background: #ffffff;
        text-align: center;
    }

    .mkt-empty-icon {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        margin: 0 auto 14px;
        border-radius: 50%;
        color: var(--mkt-brand);
        background: var(--mkt-brand-soft);
    }

    .mkt-empty-icon svg {
        width: 27px;
        height: 27px;
    }

    .mkt-empty h3 {
        margin: 0;
        color: var(--mkt-ink);
        font-size: 18px;
        font-weight: 750;
    }

    .mkt-empty p {
        max-width: 440px;
        margin: 8px auto 18px;
        color: var(--mkt-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .mkt-pagination {
        margin-top: 32px;
        display: flex;
        justify-content: center;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1180px) {
        .mkt-search {
            grid-template-columns: minmax(220px, 1fr) 180px 160px auto;
        }
    }

    @media (max-width: 991px) {
        .mkt-hero {
            padding: 48px 0 56px;
        }

        .mkt-search {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 10px;
        }

        .mkt-search-divider {
            border-left: 0;
            padding-inline: 0;
        }

        .mkt-search .mkt-btn {
            grid-column: 1 / -1;
            width: 100%;
        }

        .mkt-mobile-filter {
            display: block;
        }

        .mkt-layout {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .mkt-filter-sidebar {
            display: none;
            margin-bottom: 20px;
        }

        .mkt-filter-sidebar.is-open {
            display: block;
        }

        .mkt-filter-card {
            box-shadow: none;
        }

        .mkt-rfq-card {
            display: none;
        }
    }

    @media (max-width: 767px) {
        .mkt-shell {
            padding-inline: 16px;
        }

        .mkt-hero {
            padding: 38px 0 44px;
        }

        .mkt-hero-copy {
            font-size: 14px;
        }

        .mkt-search {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .mkt-search input,
        .mkt-search select {
            height: 40px;
        }

        .mkt-suppliers-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .mkt-supplier-card {
            padding: 18px;
        }

        .mkt-results-toolbar {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }

    @media (max-width: 440px) {
        .mkt-supplier-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .mkt-card-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .mkt-view-profile-btn {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="marketplace-page">
    <!-- Hero Banner -->
    <section class="mkt-hero" aria-labelledby="suppliers-title">
        <div class="mkt-shell">
            <div class="mkt-hero-inner">
                <div class="mkt-eyebrow">
                    <span class="dot" aria-hidden="true"></span>
                    Verified Ethiopian Exporters & Washing Stations
                </div>
                <h1 id="suppliers-title">Direct Sourcing from Licensed Ethiopian Exporters</h1>
                <p class="mkt-hero-copy">
                    Connect directly with verified commercial exporters, grower cooperative unions, and processing stations across Sidama, Yirgacheffe, Guji, Limu, and Harrar.
                </p>

                <!-- Search & Filter Bar -->
                <form action="{{ route('marketplace.suppliers') }}" method="GET" class="mkt-search-form" role="search">
                    <div class="mkt-search">
                        <label class="mkt-search-field">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                            </svg>
                            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search company, city, license..." aria-label="Search exporters">
                        </label>
                        <div class="mkt-search-divider">
                            <select name="type" aria-label="Filter by business type">
                                <option value="">All business types</option>
                                <option value="exporter" @selected(request('type') == 'exporter')>Commercial Exporter</option>
                                <option value="cooperative" @selected(request('type') == 'cooperative')>Cooperative Union</option>
                                <option value="commercial_farm" @selected(request('type') == 'commercial_farm')>Estate & Farm</option>
                            </select>
                        </div>
                        <div class="mkt-search-divider">
                            <select name="origin" aria-label="Filter by origin region">
                                <option value="">All origins</option>
                                @foreach($taxonomies['origins'] ?? ['Sidama', 'Yirgacheffe', 'Guji', 'Limu', 'Harrar', 'Jimma', 'Keffa'] as $orig)
                                    <option value="{{ $orig }}" @selected(request('origin') == $orig)>{{ $orig }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="mkt-btn mkt-btn-primary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                            </svg>
                            Filter Exporters
                        </button>
                    </div>
                </form>

                <!-- Popular Origins Quick Tags -->
                <div class="mkt-quick-links" aria-label="Popular exporter origins">
                    <span class="mkt-quick-label">Popular origins:</span>
                    @foreach(['Sidama', 'Yirgacheffe', 'Guji', 'Limu', 'Harrar', 'Jimma'] as $quickOrigin)
                        <a href="{{ route('marketplace.suppliers', ['origin' => $quickOrigin]) }}" 
                           class="mkt-origin-pill {{ request('origin') == $quickOrigin ? 'is-active' : '' }}">
                            {{ $quickOrigin }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Main Section -->
    <div class="mkt-shell mkt-content">
        <!-- Mobile Filter Toggle -->
        <div class="mkt-mobile-filter">
            <button type="button" class="mkt-btn mkt-btn-secondary" data-filter-toggle aria-expanded="false" aria-controls="suppliers-filters">
                <span>Filter Options {{ $activeFilterCount ? '(' . $activeFilterCount . ' active)' : '' }}</span>
                <span class="mkt-filter-count" aria-hidden="true">{{ $activeFilterCount }}</span>
            </button>
        </div>

        <div class="mkt-layout">
            <!-- Sidebar Filters -->
            <aside id="suppliers-filters" class="mkt-filter-sidebar {{ $activeFilterCount ? 'is-open' : '' }}" aria-label="Exporter filters">
                <form action="{{ route('marketplace.suppliers') }}" method="GET" class="mkt-filter-card">
                    @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                    <div class="mkt-filter-head">
                        <h2>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12m-9 7h6"/>
                            </svg>
                            Filter Exporters
                        </h2>
                        @if($activeFilterCount > 0)
                            <a href="{{ route('marketplace.suppliers') }}" class="mkt-reset">Reset All</a>
                        @endif
                    </div>

                    <div class="mkt-filter-group">
                        <label for="filter-type" class="mkt-filter-label">Business Type</label>
                        <select id="filter-type" name="type" class="mkt-select">
                            <option value="">All business types</option>
                            <option value="exporter" @selected(request('type') == 'exporter')>Commercial Exporter</option>
                            <option value="cooperative" @selected(request('type') == 'cooperative')>Cooperative Union</option>
                            <option value="commercial_farm" @selected(request('type') == 'commercial_farm')>Estate & Commercial Farm</option>
                        </select>
                    </div>

                    <div class="mkt-filter-group">
                        <label for="filter-origin" class="mkt-filter-label">Origin Region</label>
                        <select id="filter-origin" name="origin" class="mkt-select">
                            <option value="">All regions</option>
                            @foreach($taxonomies['origins'] ?? ['Sidama', 'Yirgacheffe', 'Guji', 'Limu', 'Harrar', 'Jimma', 'Keffa', 'Teppi'] as $orig)
                                <option value="{{ $orig }}" @selected(request('origin') == $orig)>{{ $orig }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="mkt-btn mkt-btn-primary mkt-filter-submit">Apply Filters</button>
                </form>

                <div class="mkt-rfq-card">
                    <div class="mkt-rfq-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.5L19 7.5V19a2 2 0 0 1-2 2Z"/>
                        </svg>
                    </div>
                    <h3>Looking for lots?</h3>
                    <p>Browse active specialty micro-lots and commercial containers ready for forward contracting.</p>
                    <a href="{{ route('marketplace.index') }}" class="mkt-btn">Browse Coffee Lots</a>
                </div>
            </aside>

            <!-- Results Stream -->
            <main class="mkt-results">
                <!-- Results Toolbar -->
                <div class="mkt-results-toolbar">
                    <p class="mkt-result-count">
                        Showing <strong>{{ $suppliers->total() }}</strong> verified {{ Str::plural('exporter', $suppliers->total()) }}
                    </p>

                    @if($activeFilterCount > 0)
                        <div class="mkt-active-filters">
                            @if(request('search'))
                                <span class="mkt-filter-tag">
                                    Keyword: "{{ request('search') }}"
                                    <a href="{{ route('marketplace.suppliers', request()->except('search')) }}" title="Remove keyword filter">✕</a>
                                </span>
                            @endif
                            @if(request('type'))
                                <span class="mkt-filter-tag">
                                    Type: {{ ucwords(str_replace('_', ' ', request('type'))) }}
                                    <a href="{{ route('marketplace.suppliers', request()->except('type')) }}" title="Remove type filter">✕</a>
                                </span>
                            @endif
                            @if(request('origin'))
                                <span class="mkt-filter-tag">
                                    Origin: {{ request('origin') }}
                                    <a href="{{ route('marketplace.suppliers', request()->except('origin')) }}" title="Remove origin filter">✕</a>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                @if($suppliers->isEmpty())
                    <div class="mkt-empty">
                        <div class="mkt-empty-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h3>No exporters match your search</h3>
                        <p>We couldn't find any licensed exporters matching your active search and filter combination.</p>
                        <a href="{{ route('marketplace.suppliers') }}" class="mkt-btn mkt-btn-secondary">Reset Filters</a>
                    </div>
                @else
                    <div class="mkt-suppliers-grid">
                        @foreach($suppliers as $seller)
                            <article class="mkt-supplier-card">
                                <div>
                                    <!-- Header -->
                                    <div class="mkt-supplier-header">
                                        <div class="mkt-supplier-identity">
                                            <div class="mkt-supplier-avatar" aria-hidden="true">
                                                {{ strtoupper(substr($seller->company_name, 0, 2)) }}
                                            </div>
                                            <div class="mkt-supplier-title-wrap">
                                                <h3 class="mkt-supplier-name">
                                                    <a href="{{ route('marketplace.suppliers.show', $seller->company_slug) }}">
                                                        {{ $seller->company_name }}
                                                    </a>
                                                </h3>
                                                <div class="mkt-supplier-location">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    <span>{{ $seller->city ?? 'Addis Ababa' }}, {{ $seller->country ?? 'Ethiopia' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Badge only from the reviewed tier (active, not suspended), never the old profile flag --}}
                                        @if($seller->organization?->hasActiveBadge())
                                            <span class="mkt-verified-badge" title="{{ __('verification.public.verified_tier', ['tier' => $seller->organization->verification_tier]) }}">
                                                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ __('verification.public.verified_tier', ['tier' => $seller->organization->verification_tier]) }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Business Type Pill -->
                                    <span class="mkt-type-pill">
                                        {{ str_replace('_', ' ', $seller->business_type ?? 'Commercial Exporter') }}
                                    </span>

                                    <!-- Bio Snippet -->
                                    @if($seller->company_bio)
                                        <p class="mkt-supplier-bio">
                                            {{ $seller->company_bio }}
                                        </p>
                                    @else
                                        <p class="mkt-supplier-bio" style="color: var(--mkt-muted); font-style: italic;">
                                            Licensed Ethiopian coffee enterprise producing and exporting traceable specialty coffee lots.
                                        </p>
                                    @endif

                                    <!-- Metrics Grid -->
                                    <div class="mkt-metrics-row">
                                        <div class="mkt-metric-cell">
                                            <span class="mkt-metric-label">Export Vol</span>
                                            <span class="mkt-metric-value">
                                                {{ $seller->annual_export_volume_mt ? $seller->annual_export_volume_mt . ' MT' : '50+ MT' }}
                                            </span>
                                        </div>
                                        <div class="mkt-metric-cell">
                                            <span class="mkt-metric-label">Wet Mills</span>
                                            <span class="mkt-metric-value">
                                                {{ $seller->washing_station_count ?? 1 }}
                                            </span>
                                        </div>
                                        <div class="mkt-metric-cell">
                                            <span class="mkt-metric-label">Active Lots</span>
                                            <span class="mkt-metric-value highlight">
                                                {{ $seller->listings_count }} Lots
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Origins Handled -->
                                    @if(!empty($seller->origin_regions))
                                        <div class="mkt-origins-wrap">
                                            <span class="mkt-origins-label">Origins Handled</span>
                                            <div class="mkt-origins-list">
                                                @foreach((array)$seller->origin_regions as $region)
                                                    <span class="mkt-origin-tag">{{ $region }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Card Footer -->
                                <div class="mkt-card-footer">
                                    <span class="mkt-license-tag">
                                        License: <code>{{ $seller->export_license_no ?? 'ECTA/LIC/EXP' }}</code>
                                    </span>
                                    <a href="{{ route('marketplace.suppliers.show', $seller->company_slug) }}" class="mkt-view-profile-btn">
                                        View Profile
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($suppliers->hasPages())
                        <div class="mkt-pagination">
                            {{ $suppliers->links() }}
                        </div>
                    @endif
                @endif
            </main>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-filter-toggle]');
        var sidebar = document.getElementById('suppliers-filters');
        if (toggle && sidebar) {
            toggle.addEventListener('click', function () {
                var isOpen = sidebar.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }
    });
</script>
@endpush
@endsection
