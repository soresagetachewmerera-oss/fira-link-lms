@extends('layouts.app')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@push('meta')
    <meta property="og:title" content="{{ __('market.web.title') }}">
    <meta property="og:description" content="{{ __('market.web.meta') }}">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ route('market-prices.index') }}">
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/front/css/market-watch.css') }}?v={{ file_exists(public_path('assets/front/css/market-watch.css')) ? filemtime(public_path('assets/front/css/market-watch.css')) : '1' }}">
<style>
    .prices-page { --prices-brand: #6D1A95; --prices-brand-dark: #3B0764; --prices-brand-tint: #F3E8FF;
        --prices-ink: #1E1B4B; --prices-body: #4A4658; --prices-muted: #6B7280;
        --prices-border: #E9E4F0; --prices-subtle: #F9F7FA;
        min-height: 100vh; padding-bottom: 64px; color: var(--prices-body); background: #fff; font-family: 'Inter', sans-serif; }
    .prices-container { width: 100%; max-width: 1200px; margin-inline: auto; padding-inline: 24px; }
    .prices-intro { padding: 48px 0 32px; border-bottom: 1px solid var(--prices-border); }
    .prices-breadcrumb { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 20px; padding: 0; list-style: none; color: var(--prices-muted); font-size: 12px; }
    .prices-breadcrumb li + li::before { content: '/'; margin-right: 8px; color: var(--prices-border); }
    .prices-breadcrumb a { color: var(--prices-muted); text-decoration: none; }
    .prices-intro-grid { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: 32px; }
    .prices-kicker { margin: 0 0 8px; color: var(--prices-brand); font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .prices-intro h1 { max-width: 760px; margin: 0; color: var(--prices-ink); font-size: clamp(30px, 4vw, 44px); font-weight: 700; line-height: 1.1; letter-spacing: -.035em; }
    .prices-lead { max-width: 720px; margin: 16px 0 0; font-size: 15px; line-height: 1.7; }
    .prices-btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 18px; border: 1px solid var(--prices-brand); border-radius: var(--fira-radius); font-size: 13px; font-weight: 600; text-decoration: none; color: var(--prices-brand); background: #fff; cursor: pointer; }
    .prices-btn:hover { color: var(--prices-brand-dark); background: var(--prices-brand-tint); }
    .prices-main { padding-top: 32px; }
    .prices-section { margin-top: 40px; }
    .prices-section-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 16px; }
    .prices-section-head h2 { margin: 0; color: var(--prices-ink); font-size: 22px; font-weight: 700; letter-spacing: -.02em; }
    .prices-section-head p { max-width: 680px; margin: 6px 0 0; color: var(--prices-muted); font-size: 13px; line-height: 1.6; }
    .prices-top { display: grid; grid-template-columns: minmax(260px, 360px) minmax(0, 1fr); gap: 16px; }
    .prices-card { padding: 20px; border: 1px solid var(--prices-border); border-radius: var(--fira-radius); background: #fff; }
    .prices-hero { color: #fff; border-color: var(--prices-brand-dark); background: var(--prices-brand-dark); }
    .prices-hero .muted { color: rgba(255,255,255,.82); }
    .prices-hero-name { margin: 0 0 10px; font-size: 14px; font-weight: 700; }
    .prices-hero-value { font-size: 36px; font-weight: 800; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .prices-hero-unit { margin-left: 6px; font-size: 13px; font-weight: 600; }
    .prices-status { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 13px; font-weight: 600; }
    .prices-small { margin-top: 4px; font-size: 12px; }
    .prices-ranges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .prices-range { min-width: 44px; min-height: 44px; padding: 0 12px; border: 1px solid var(--prices-border); border-radius: var(--fira-radius); color: var(--prices-body); background: #fff; font-weight: 600; cursor: pointer; }
    .prices-range[aria-pressed="true"] { color: #fff; border-color: var(--prices-brand); background: var(--prices-brand); }
    .prices-chart { position: relative; height: 240px; }
    .prices-chart svg { width: 100%; height: 100%; display: block; }
    .prices-chart-msg { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--prices-muted); font-size: 14px; text-align: center; }
    .prices-axis { display: flex; justify-content: space-between; margin-top: 6px; color: var(--prices-muted); font-size: 11px; }
    .prices-table-wrap { overflow-x: auto; border: 1px solid var(--prices-border); border-radius: var(--fira-radius); background: #fff; }
    .prices-table { width: 100%; min-width: 640px; border-collapse: collapse; font-size: 13px; }
    .prices-table th { padding: 12px 16px; border-bottom: 1px solid var(--prices-border); color: var(--prices-muted); background: var(--prices-subtle); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-align: left; text-transform: uppercase; }
    .prices-table td { padding: 14px 16px; border-bottom: 1px solid var(--prices-border); vertical-align: top; }
    .prices-table tbody tr:last-child td { border-bottom: 0; }
    .prices-table tr[data-selectable="1"] { cursor: pointer; }
    .prices-table tr[aria-selected="true"] { background: var(--prices-brand-tint); }
    .prices-table .text-end { text-align: right; }
    .prices-name { display: block; color: var(--prices-ink); font-weight: 650; }
    .prices-detail { display: block; margin-top: 2px; color: var(--prices-muted); font-size: 12px; }
    .prices-number { color: var(--prices-ink); font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
    .prices-notice { display: none; margin-bottom: 16px; padding: 12px 16px; border: 1px solid var(--prices-brand); border-radius: var(--fira-radius); color: var(--prices-ink); background: var(--prices-brand-tint); font-size: 14px; }
    .prices-notice.is-visible { display: flex; gap: 10px; align-items: flex-start; }
    .prices-note { margin: 12px 0 0; color: var(--prices-muted); font-size: 12px; line-height: 1.55; }
    .prices-grade { display: inline-block; padding: 3px 7px; border: 1px solid var(--prices-border); border-radius: 4px; font-size: 11px; font-weight: 600; }
    .prices-link { color: var(--prices-brand); font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap; }
    .prices-next { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 40px; }
    .prices-next-item { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 24px; border: 1px solid var(--prices-border); border-radius: var(--fira-radius); color: inherit; text-decoration: none; }
    .prices-next-item:hover { color: inherit; border-color: var(--prices-brand); }
    .prices-next-item h3 { margin: 0; color: var(--prices-ink); font-size: 16px; font-weight: 650; }
    .prices-next-item p { margin: 5px 0 0; color: var(--prices-muted); font-size: 12px; line-height: 1.55; }
    .prices-page :is(button, a, tr):focus-visible { outline: 3px solid var(--prices-brand); outline-offset: 2px; }
    @media (max-width: 900px) { .prices-intro-grid, .prices-top, .prices-next { grid-template-columns: 1fr; } }
    @media (max-width: 767px) { .prices-container { padding-inline: 16px; } }
    @media (max-width: 640px) { .prices-intro { padding: 32px 0 24px; } .prices-section-head { flex-direction: column; align-items: start; gap: 8px; } .prices-btn { width: 100%; } }
    @media (prefers-reduced-motion: no-preference) { .prices-range, .prices-btn { transition: background-color .2s ease-out, color .2s ease-out; } }
</style>
@endpush

@php
    $t = fn (string $k, array $r = []) => __('market.web.' . $k, $r);
    $unit = function (array $row) use ($t) {
        $key = $row['currency'] . '/' . $row['unit'];
        $label = __('market.web.units.' . $key);
        return $label === 'market.web.units.' . $key ? $key : $label;
    };
    $fmt = fn ($v) => number_format((float) $v, 2);
    $time = function (?string $iso, bool $daily) {
        if (!$iso) return null;
        $c = \Illuminate\Support\Carbon::parse($iso);
        return $daily ? $c->utc()->format('Y-m-d') : $c->timezone(config('app.timezone'))->format('Y-m-d H:i');
    };
    $isDaily = fn (array $row) => in_array($row['price_type'], ['daily_close', 'weekly_regulatory_floor'], true);
    $hasValue = fn (array $row) => $row['value'] !== null && $row['freshness']['status'] !== 'unavailable';
    $icon = fn (string $status) => match ($status) {
        'live' => 'fa-circle', 'delayed' => 'fa-clock', 'daily_close' => 'fa-calendar-day', 'schedule' => 'fa-file-contract', 'stale' => 'fa-history', default => 'fa-ban',
    };
    $trendIcon = fn (string $trend) => match ($trend) {
        'bullish' => 'fa-arrow-trend-up', 'bearish' => 'fa-arrow-trend-down', 'sideways' => 'fa-arrows-left-right', default => 'fa-hourglass-half',
    };
    $featured = collect([$overview['hero']])->filter()->first(fn ($r) => $hasValue($r))
        ?? collect($overview['benchmarks'])->first(fn ($r) => $hasValue($r));
    $attributions = collect($overview['benchmarks'])->pluck('source.attribution')->filter()->unique()->values();
@endphp

@section('content')
<div class="prices-page" id="market-page">
    <header class="prices-intro">
        <div class="prices-container">
            <ol class="prices-breadcrumb" aria-label="Breadcrumb">
                <li><a href="{{ route('home') }}">{{ $t('breadcrumb_home') }}</a></li>
                <li aria-current="page">{{ $t('breadcrumb') }}</li>
            </ol>
            <div class="prices-intro-grid">
                <div>
                    <p class="prices-kicker">{{ $t('kicker') }}</p>
                    <h1>{{ $t('heading') }}</h1>
                    <p class="prices-lead">{{ $t('lead') }}</p>
                </div>
                <a href="{{ route('marketplace.index') }}" class="prices-btn">{{ $t('browse_lots') }}</a>
            </div>
        </div>
    </header>

    <main class="prices-container prices-main">
        <div class="prices-notice" id="market-notice" role="status" aria-live="polite">
            <i class="fas fa-cloud" aria-hidden="true"></i><span id="market-notice-text"></span>
        </div>

        {{-- Market watch: every row (left) and the selected row's detail (right on wide screens). --}}
        <div class="fira-mw-layout" data-market-page data-panel-url="{{ route('market-prices.panel') }}" data-watch-url="{{ url('/api/market/watch') }}"
            data-signature='@json(collect($watch['rows'])->map(fn ($r) => [$r['key'], $r['price'], $r['status']])->values())' data-text-failed="{{ __('market.watch.detail.failed') }}"
            data-text-refresh-failed="{{ $t('refresh_failed', ['time' => '{time}']) }}">
            <x-market.watch :watch="$watch" :linked="true" :selected="$detail['key'] ?? null" :refresh="true" list-id="market-rows" />
            <div class="fira-mw-layout__detail" id="market-detail-slot">
                <x-market.detail :detail="$detail" />
            </div>
        </div>
        <p class="prices-note" id="market-attribution">{{ $t('attribution', ['names' => $attributions->implode(', ')]) }}</p>
        <p class="prices-note" id="market-updated">{{ $t('updated', ['time' => $time($overview['generated_at'], false)]) }}</p>

        <section class="prices-section" aria-labelledby="floor-prices-heading">
            <div class="prices-section-head">
                <div><h2 id="floor-prices-heading">{{ $t('floor_heading') }}</h2><p>{{ $t('floor_hint') }}</p></div>
                @if($latestPriceDataset)
                    <div class="prices-detail">{{ $t('floor_effective') }}: <strong>{{ $latestPriceDataset->effective_date?->format('Y-m-d') }}</strong> · {{ $t('floor_source') }}: {{ $latestPriceDataset->source }}</div>
                @endif
            </div>
            <div class="prices-table-wrap">
                <table class="prices-table">
                    <thead><tr><th>{{ $t('col_origin') }}</th><th>{{ $t('col_grade') }}</th><th class="text-end">USD/lb</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse($priceRecords as $record)
                            <tr>
                                <td><span class="prices-name">{{ $record->origin }}</span><span class="prices-detail">{{ $record->coffee_type }}</span></td>
                                <td><span class="prices-grade">{{ $record->grade }}</span><span class="prices-detail">{{ $record->processing_method }}</span></td>
                                <td class="text-end"><span class="prices-number">${{ number_format($record->minimum_price, 3) }}</span></td>
                                <td class="text-end"><a href="{{ route('marketplace.index', ['origin' => $record->origin]) }}" class="prices-link">{{ $t('view_lots') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="prices-detail" style="text-align:center;padding:32px">{{ $t('floor_none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="prices-note">{{ $t('floor_note') }}</p>
        </section>

        <nav class="prices-next" aria-label="{{ $t('breadcrumb') }}">
            <a href="{{ route('marketplace.index') }}" class="prices-next-item"><div><h3>{{ $t('next_lots') }}</h3><p>{{ $t('next_lots_hint') }}</p></div><span aria-hidden="true">→</span></a>
            <a href="{{ route('free-tools.index') }}" class="prices-next-item"><div><h3>{{ $t('next_tools') }}</h3><p>{{ $t('next_tools_hint') }}</p></div><span aria-hidden="true">→</span></a>
        </nav>
    </main>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/front/js/market-watch.js') }}?v={{ file_exists(public_path('assets/front/js/market-watch.js')) ? filemtime(public_path('assets/front/js/market-watch.js')) : '1' }}" defer></script>
@endpush
