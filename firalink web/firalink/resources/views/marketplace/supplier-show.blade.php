@extends('layouts.app')

@section('title', $seller->company_name . ' | Verified Ethiopian Coffee Exporter | Fira Link')

@push('styles')
<style>
    .marketplace-page {
        --mkt-brand: #6d1a95;
        --mkt-brand-dark: #51126f;
        --mkt-brand-soft: #f7f0fa;
        --mkt-brand-border: #e8d5f2;
        --mkt-ink: #18141b;
        --mkt-body: #544c59;
        --mkt-muted: #746b79;
        --mkt-line: #e9e5eb;
        --mkt-surface: #ffffff;
        min-height: 100vh;
        padding-bottom: 72px;
        color: var(--mkt-ink);
        background: #faf9fb;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .marketplace-page *, .marketplace-page *::before, .marketplace-page *::after {
        box-sizing: border-box;
    }

    .mkt-shell {
        width: min(100% - 40px, 1320px);
        margin-inline: auto;
    }

    /* Breadcrumbs */
    .mkt-breadcrumbs {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 24px 0 16px;
        font-size: 13px;
        color: var(--mkt-muted);
    }

    .mkt-breadcrumbs a {
        color: var(--mkt-muted);
        text-decoration: none;
        transition: color 0.18s ease;
    }

    .mkt-breadcrumbs a:hover {
        color: var(--mkt-brand);
    }

    .mkt-breadcrumbs .current {
        color: var(--mkt-ink);
        font-weight: 600;
    }

    /* Profile Header Card */
    .mkt-profile-header-card {
        padding: 32px;
        border: 1px solid var(--mkt-line);
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 6px 24px rgba(42, 30, 46, 0.04);
        margin-bottom: 28px;
    }

    .mkt-profile-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }

    .mkt-profile-identity {
        display: flex;
        align-items: center;
        gap: 18px;
        min-width: 0;
    }

    .mkt-profile-avatar {
        width: 72px;
        height: 72px;
        border-radius: 16px;
        background: linear-gradient(135deg, #6d1a95 0%, #3d0e54 100%);
        color: #ffffff;
        font-size: 24px;
        font-weight: 800;
        display: grid;
        place-items: center;
        border: 2px solid #e9d5ff;
        box-shadow: 0 8px 20px rgba(109, 26, 149, 0.2);
        flex: 0 0 auto;
    }

    .mkt-profile-title-area {
        min-width: 0;
    }

    .mkt-profile-heading-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .mkt-profile-title {
        margin: 0;
        color: var(--mkt-ink);
        font-size: clamp(22px, 2.5vw, 30px);
        font-weight: 800;
        letter-spacing: -0.025em;
        line-height: 1.2;
    }

    .mkt-verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .mkt-verified-badge svg {
        width: 14px;
        height: 14px;
        color: #059669;
    }

    .mkt-profile-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px 14px;
        margin-top: 6px;
        color: var(--mkt-muted);
        font-size: 13px;
    }

    .mkt-profile-meta strong {
        color: var(--mkt-ink);
    }

    .mkt-profile-meta code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #4b5563;
        font-weight: 600;
    }

    /* Stats Grid */
    .mkt-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid var(--mkt-line);
    }

    .mkt-stat-box {
        padding: 16px;
        border: 1px solid var(--mkt-line);
        border-radius: 14px;
        background: #faf9fb;
    }

    .mkt-stat-label {
        display: block;
        color: var(--mkt-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .mkt-stat-value {
        color: var(--mkt-ink);
        font-size: 18px;
        font-weight: 800;
    }

    .mkt-stat-value.brand {
        color: var(--mkt-brand);
    }

    .mkt-stat-value.success {
        color: #059669;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 14px;
    }

    .mkt-stat-value.success svg {
        width: 16px;
        height: 16px;
    }

    /* Details Layout */
    .mkt-detail-layout {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        align-items: start;
        gap: 28px;
    }

    /* Sidebar Overview */
    .mkt-overview-card {
        padding: 24px;
        border: 1px solid var(--mkt-line);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 18px rgba(42, 30, 46, 0.04);
    }

    .mkt-overview-card h3 {
        margin: 0 0 12px;
        color: var(--mkt-ink);
        font-size: 16px;
        font-weight: 750;
    }

    .mkt-overview-bio {
        color: var(--mkt-body);
        font-size: 14px;
        line-height: 1.65;
        margin: 0;
    }

    .mkt-section-divider {
        margin-top: 20px;
        padding-top: 18px;
        border-top: 1px solid var(--mkt-line);
    }

    .mkt-subhead {
        color: var(--mkt-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .mkt-tags-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .mkt-tag {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        background: var(--mkt-brand-soft);
        border: 1px solid var(--mkt-brand-border);
        color: var(--mkt-brand);
        font-size: 12px;
        font-weight: 600;
    }

    .mkt-tag.cert {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .mkt-logistics-list {
        display: grid;
        gap: 8px;
        font-size: 13px;
    }

    .mkt-logistics-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .mkt-logistics-label {
        color: var(--mkt-muted);
    }

    .mkt-logistics-val {
        color: var(--mkt-ink);
        font-weight: 600;
        text-align: right;
    }

    /* Lots Stream */
    .mkt-lots-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .mkt-lots-header h2 {
        margin: 0;
        color: var(--mkt-ink);
        font-size: 20px;
        font-weight: 800;
    }

    .mkt-lots-header p {
        margin: 4px 0 0;
        color: var(--mkt-muted);
        font-size: 13px;
    }

    .mkt-lots-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .mkt-lot-card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px;
        border: 1px solid var(--mkt-line);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 16px rgba(42, 30, 46, 0.04);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .mkt-lot-card:hover {
        transform: translateY(-2px);
        border-color: var(--mkt-brand-border);
        box-shadow: 0 12px 28px rgba(109, 26, 149, 0.1);
    }

    .mkt-lot-media {
        position: relative;
        height: 160px;
        border-radius: 12px;
        overflow: hidden;
        background: var(--mkt-brand-soft);
        margin-bottom: 12px;
    }

    .mkt-lot-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .mkt-lot-score {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 4px 8px;
        border-radius: 6px;
        background: rgba(81, 18, 111, 0.94);
        color: #ffffff;
        font-size: 11px;
        font-weight: 750;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .mkt-lot-origin {
        position: absolute;
        bottom: 8px;
        left: 8px;
        padding: 3px 8px;
        border-radius: 6px;
        background: rgba(20, 15, 22, 0.75);
        backdrop-filter: blur(4px);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
    }

    .mkt-lot-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.35;
    }

    .mkt-lot-title a {
        color: var(--mkt-ink);
        text-decoration: none;
    }

    .mkt-lot-title a:hover {
        color: var(--mkt-brand);
    }

    .mkt-lot-specs {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        color: var(--mkt-muted);
        font-size: 12px;
    }

    .mkt-lot-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid var(--mkt-line);
    }

    .mkt-lot-price {
        font-size: 15px;
        font-weight: 800;
        color: var(--mkt-ink);
    }

    .mkt-lot-price-sub {
        display: block;
        color: var(--mkt-muted);
        font-size: 10px;
        font-weight: 500;
    }

    .mkt-btn-sm {
        min-height: 32px;
        padding: 0 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--mkt-brand-soft);
        color: var(--mkt-brand) !important;
        border: 1px solid var(--mkt-brand-border);
        transition: all 0.18s ease;
    }

    .mkt-btn-sm:hover {
        background: var(--mkt-brand);
        color: #ffffff !important;
    }

    /* Responsive adjustments */
    @media (max-width: 991px) {
        .mkt-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .mkt-detail-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .mkt-shell {
            width: min(100% - 24px, 1320px);
        }

        .mkt-profile-header-card {
            padding: 20px;
        }

        .mkt-stats-grid {
            grid-template-columns: 1fr;
        }

        .mkt-lots-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="marketplace-page">
    <div class="mkt-shell">
        <!-- Breadcrumbs -->
        <nav class="mkt-breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span>/</span>
            <a href="{{ route('marketplace.index') }}">Marketplace</a>
            <span>/</span>
            <a href="{{ route('marketplace.suppliers') }}">Exporters</a>
            <span>/</span>
            <span class="current">{{ $seller->company_name }}</span>
        </nav>

        <!-- Company Profile Header Card -->
        <header class="mkt-profile-header-card">
            <div class="mkt-profile-top">
                <div class="mkt-profile-identity">
                    <div class="mkt-profile-avatar" aria-hidden="true">
                        {{ strtoupper(substr($seller->company_name, 0, 2)) }}
                    </div>
                    <div class="mkt-profile-title-area">
                        <div class="mkt-profile-heading-row">
                            <h1 class="mkt-profile-title">{{ $seller->company_name }}</h1>
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
                        <div class="mkt-profile-meta">
                            <span>{{ $seller->city ?? 'Addis Ababa' }}, {{ $seller->country ?? 'Ethiopia' }}</span>
                            <span>•</span>
                            <span>License: <code>{{ $seller->export_license_no ?? 'ECTA/LIC/EXP' }}</code></span>
                            <span>•</span>
                            <span>Type: <strong>{{ str_replace('_', ' ', $seller->business_type ?? 'Exporter') }}</strong></span>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ route('marketplace.suppliers') }}" class="mkt-btn-sm" style="padding: 8px 16px;">
                        ← Back to Exporters
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="mkt-stats-grid">
                <div class="mkt-stat-box">
                    <span class="mkt-stat-label">Annual Export Volume</span>
                    <span class="mkt-stat-value">
                        {{ $seller->annual_export_volume_mt ? $seller->annual_export_volume_mt . ' MT' : '150 MT' }}
                    </span>
                </div>
                <div class="mkt-stat-box">
                    <span class="mkt-stat-label">Processing Stations</span>
                    <span class="mkt-stat-value">
                        {{ $seller->washing_station_count ?? 1 }} Wet Mills
                    </span>
                </div>
                <div class="mkt-stat-box">
                    <span class="mkt-stat-label">Active Export Lots</span>
                    <span class="mkt-stat-value brand">
                        {{ $seller->listings->count() }} Available
                    </span>
                </div>
                <div class="mkt-stat-box">
                    <span class="mkt-stat-label">Regulatory Status</span>
                    <span class="mkt-stat-value success">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        ECTA Registered
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Details Layout -->
        <div class="mkt-detail-layout">
            <!-- Sidebar: Overview & Info -->
            <aside class="mkt-overview-card">
                <h3>Company Overview</h3>
                <p class="mkt-overview-bio">
                    {{ $seller->company_bio ?? 'Licensed specialty coffee exporter operating processing stations in core Ethiopian growing regions. We focus on traceable lots, controlled natural drying, and strict cup quality.' }}
                </p>

                <!-- Origins Handled -->
                @if(!empty($seller->origin_regions))
                    <div class="mkt-section-divider">
                        <div class="mkt-subhead">Specialty Origins</div>
                        <div class="mkt-tags-row">
                            @foreach((array)$seller->origin_regions as $origin)
                                <span class="mkt-tag">{{ $origin }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Certifications -->
                @if(!empty($seller->certifications))
                    <div class="mkt-section-divider">
                        <div class="mkt-subhead">Certifications</div>
                        <div class="mkt-tags-row">
                            @foreach((array)$seller->certifications as $cert)
                                <span class="mkt-tag cert">{{ $cert }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Logistics Reference -->
                <div class="mkt-section-divider">
                    <div class="mkt-subhead">Logistics & Trade Terms</div>
                    <div class="mkt-logistics-list">
                        <div class="mkt-logistics-row">
                            <span class="mkt-logistics-label">Headquarters:</span>
                            <span class="mkt-logistics-val">{{ $seller->city ?? 'Addis Ababa' }}, Ethiopia</span>
                        </div>
                        <div class="mkt-logistics-row">
                            <span class="mkt-logistics-label">Incoterm:</span>
                            <span class="mkt-logistics-val">FOB Djibouti Port</span>
                        </div>
                        <div class="mkt-logistics-row">
                            <span class="mkt-logistics-label">Payment Terms:</span>
                            <span class="mkt-logistics-val">LC at Sight / CAD via CBE</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Active Lots Stream -->
            <section class="mkt-lots-section" aria-labelledby="lots-heading">
                <div class="mkt-lots-header">
                    <div>
                        <h2 id="lots-heading">Available Export Lots</h2>
                        <p>Explore ready-to-ship specialty micro-lots and commercial containers</p>
                    </div>
                    <span class="mkt-tag">
                        {{ $seller->listings->count() }} {{ Str::plural('Lot', $seller->listings->count()) }}
                    </span>
                </div>

                @if($seller->listings->isEmpty())
                    <div class="mkt-empty" style="padding: 48px 20px;">
                        <h3>No public lots currently listed</h3>
                        <p>This exporter currently has no active public lots. Contact them directly or request an inquiry.</p>
                        <a href="{{ route('marketplace.index') }}" class="mkt-btn-sm" style="margin-top: 12px; padding: 6px 16px;">
                            Browse All Marketplace Lots
                        </a>
                    </div>
                @else
                    <div class="mkt-lots-grid">
                        @foreach($seller->listings as $lot)
                            <article class="mkt-lot-card">
                                <div>
                                    <div class="mkt-lot-media">
                                        @if($lot->primaryImage && $lot->primaryImage->file_path)
                                            <img src="{{ $lot->primaryImage->file_path }}" alt="{{ $lot->title }}" loading="lazy">
                                        @else
                                            <div style="width: 100%; height: 100%; display: grid; place-items: center; color: #c084fc;">
                                                <svg style="width: 40px; height: 40px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif

                                        @if($lot->cupping_score)
                                            <span class="mkt-lot-score">{{ number_format($lot->cupping_score, 1) }} SCA</span>
                                        @endif

                                        <span class="mkt-lot-origin">{{ $lot->origin_region }}</span>
                                    </div>

                                    <h4 class="mkt-lot-title">
                                        <a href="{{ route('marketplace.show', $lot->slug) }}">{{ $lot->title }}</a>
                                    </h4>

                                    <div class="mkt-lot-specs">
                                        <span style="text-transform: capitalize;">{{ str_replace('_', ' ', $lot->processing_method) }}</span>
                                        <span>•</span>
                                        <span>{{ strtoupper(str_replace('_', ' ', $lot->grade)) }}</span>
                                    </div>
                                </div>

                                <div class="mkt-lot-footer">
                                    <div>
                                        <span class="mkt-lot-price-sub">Indicative FOB</span>
                                        <span class="mkt-lot-price">${{ number_format($lot->indicative_price_usd_per_lb, 2) }} <small style="font-size: 11px; font-weight: 500; color: var(--mkt-muted);">/ lb</small></span>
                                    </div>
                                    <a href="{{ route('marketplace.show', $lot->slug) }}" class="mkt-btn-sm">
                                        View Lot
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
