@extends('layouts.app')

@section('title', $pageTitle ?? 'Coffee Market Prices | Fira Link')
@section('meta_description', $metaDescription ?? 'Current Ethiopian coffee benchmarks and international coffee market charts.')

@push('styles')
<style>
    .prices-page {
        --prices-brand: #6d1a95;
        --prices-brand-dark: #51126f;
        --prices-brand-tint: #f7f0fa;
        --prices-ink: #171717;
        --prices-body: #404040;
        --prices-muted: #737373;
        --prices-border: #d4d4d4;
        --prices-surface: #ffffff;
        --prices-subtle: #f5f5f5;
        min-height: 100vh;
        padding-bottom: 64px;
        color: var(--prices-body);
        background: var(--prices-surface);
        font-family: 'Inter', sans-serif;
    }
    .prices-container { width: min(calc(100% - 32px), 1280px); margin-inline: auto; }
    .prices-intro { padding: 48px 0 32px; border-bottom: 1px solid var(--prices-border); background: var(--prices-surface); }
    .prices-breadcrumb { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 20px; padding: 0; list-style: none; color: var(--prices-muted); font-size: 12px; }
    .prices-breadcrumb li + li::before { content: '/'; margin-right: 8px; color: var(--prices-border); }
    .prices-breadcrumb a { color: var(--prices-muted); text-decoration: none; }
    .prices-breadcrumb a:hover { color: var(--prices-brand); }
    .prices-intro-grid { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: 32px; }
    .prices-kicker { margin: 0 0 8px; color: var(--prices-brand); font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .prices-intro h1 { max-width: 760px; margin: 0; color: var(--prices-ink); font-size: clamp(32px, 4vw, 48px); font-weight: 700; line-height: 1.1; letter-spacing: -.035em; }
    .prices-lead { max-width: 720px; margin: 16px 0 0; color: var(--prices-body); font-size: 15px; line-height: 1.7; }
    .prices-actions { display: flex; gap: 8px; }
    .prices-btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 18px; border: 1px solid var(--prices-brand); border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; transition: color .18s ease, background-color .18s ease, border-color .18s ease; }
    .prices-btn-primary { color: #fff; background: var(--prices-brand); }
    .prices-btn-primary:hover { color: #fff; border-color: var(--prices-brand-dark); background: var(--prices-brand-dark); }
    .prices-btn-secondary { color: var(--prices-brand); background: #fff; }
    .prices-btn-secondary:hover { color: var(--prices-brand-dark); border-color: var(--prices-brand-dark); background: var(--prices-brand-tint); }
    .prices-main { padding-top: 32px; }
    .prices-section { margin-top: 40px; }
    .prices-section-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 16px; }
    .prices-section-head h2 { margin: 0; color: var(--prices-ink); font-size: 22px; font-weight: 700; letter-spacing: -.02em; }
    .prices-section-head p { max-width: 680px; margin: 6px 0 0; color: var(--prices-muted); font-size: 13px; line-height: 1.6; }
    .prices-effective { flex: 0 0 auto; color: var(--prices-muted); font-size: 12px; }
    .prices-effective strong { color: var(--prices-ink); }
    .prices-table-wrap { overflow-x: auto; border: 1px solid var(--prices-border); border-radius: 6px; background: #fff; }
    .prices-table { width: 100%; min-width: 760px; border-collapse: collapse; font-size: 13px; }
    .prices-table th { padding: 12px 16px; border-bottom: 1px solid var(--prices-border); color: var(--prices-muted); background: var(--prices-subtle); font-size: 10px; font-weight: 700; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
    .prices-table td { padding: 14px 16px; border-bottom: 1px solid var(--prices-border); color: var(--prices-body); vertical-align: middle; }
    .prices-table tbody tr:last-child td { border-bottom: 0; }
    .prices-table tbody tr:hover { background: var(--prices-brand-tint); }
    .prices-table .text-end { text-align: right; }
    .prices-origin { display: block; color: var(--prices-ink); font-weight: 650; }
    .prices-detail { display: block; margin-top: 2px; color: var(--prices-muted); font-size: 11px; }
    .prices-grade { display: inline-block; padding: 3px 7px; border: 1px solid var(--prices-border); border-radius: 4px; color: var(--prices-body); background: var(--prices-surface); font-size: 11px; font-weight: 600; }
    .prices-number { color: var(--prices-ink); font-variant-numeric: tabular-nums; font-weight: 650; white-space: nowrap; }
    .prices-source { color: var(--prices-brand); font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap; }
    .prices-source:hover { color: var(--prices-brand-dark); text-decoration: underline; }
    .prices-empty { padding: 40px 16px !important; color: var(--prices-muted) !important; text-align: center; }
    .prices-note { margin: 12px 0 0; color: var(--prices-muted); font-size: 11px; line-height: 1.55; }
    .prices-next { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 40px; }
    .prices-next-item { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 24px; border: 1px solid var(--prices-border); border-radius: 6px; color: inherit; background: #fff; text-decoration: none; }
    .prices-next-item:hover { color: inherit; border-color: var(--prices-brand); }
    .prices-next-item h3 { margin: 0; color: var(--prices-ink); font-size: 16px; font-weight: 650; }
    .prices-next-item p { margin: 5px 0 0; color: var(--prices-muted); font-size: 12px; line-height: 1.55; }
    .prices-next-arrow { color: var(--prices-brand); font-size: 18px; }

    /* Simplify the existing functional chart without changing its controls or script hooks. */
    .prices-page .fira-trading-terminal { margin: 0; overflow: hidden; border: 1px solid var(--prices-border); border-radius: 6px; color: var(--prices-body); background: #fff; box-shadow: none; }
    .prices-page :is(.terminal-market-bar, .terminal-origin-strip, .terminal-metrics-panel, .terminal-toolbar, .terminal-footer-strip) { border-color: var(--prices-border); color: var(--prices-body); background: #fff; }
    .prices-page .terminal-market-bar { padding: 12px; }
    .prices-page .market-tabs-group { gap: 6px; }
    .prices-page .market-tab-btn { min-height: 38px; padding: 0 12px; border: 1px solid var(--prices-border); border-radius: 6px; color: var(--prices-body); background: #fff; box-shadow: none; transform: none; }
    .prices-page .market-tab-btn:hover { color: var(--prices-brand); border-color: var(--prices-brand); background: var(--prices-brand-tint); }
    .prices-page .market-tab-btn.active { color: #fff; border-color: var(--prices-brand); background: var(--prices-brand); background-image: none; box-shadow: none; }
    .prices-page .market-source-pill { color: inherit; background: transparent; }
    .prices-page .terminal-connection-badge { color: var(--prices-muted); border: 0; background: transparent; }
    .prices-page .pulse-dot { background: var(--prices-brand); box-shadow: none; animation: none; }
    .prices-page .terminal-origin-strip { padding: 10px 12px; }
    .prices-page .origin-strip-label { color: var(--prices-muted); }
    .prices-page .origin-chip { padding: 6px 9px; border: 1px solid var(--prices-border); border-radius: 4px; color: var(--prices-body); background: #fff; }
    .prices-page .origin-chip:hover { color: var(--prices-brand); border-color: var(--prices-brand); background: var(--prices-brand-tint); }
    .prices-page .origin-chip.active { color: #fff; border-color: var(--prices-brand); background: var(--prices-brand); }
    .prices-page .terminal-metrics-panel { padding: 16px; }
    .prices-page .instrument-name { color: var(--prices-ink); }
    .prices-page :is(.instrument-symbol, .instrument-badge, .cell-label) { color: var(--prices-muted); background: transparent; }
    .prices-page :is(.live-price, .live-price.up, .live-price.down, .cell-value, .text-up, .text-down, .text-vol, .text-accent) { color: var(--prices-brand) !important; }
    .prices-page :is(.price-delta-badge, .price-delta-badge.delta-up, .price-delta-badge.delta-down) { color: var(--prices-brand); background: var(--prices-brand-tint); }
    .prices-page .metric-cell { border-color: var(--prices-border); background: var(--prices-subtle); background-image: none; }
    .prices-page .terminal-toolbar { padding: 10px 12px; }
    .prices-page :is(.tf-btn, .tool-btn) { min-height: 34px; border: 1px solid transparent; border-radius: 4px; color: var(--prices-body); background: transparent; }
    .prices-page :is(.tf-btn, .tool-btn):hover { color: var(--prices-brand); border-color: var(--prices-border); background: var(--prices-brand-tint); }
    .prices-page :is(.tf-btn.active, .tool-btn.active) { color: #fff; border-color: var(--prices-brand); background: var(--prices-brand); box-shadow: none; }
    .prices-page .chart-viewport-wrapper { border-color: var(--prices-border); background: #fff; }
    .prices-page .chart-crosshair-legend { color: var(--prices-body); background: rgba(255,255,255,.92); box-shadow: none; }
    .prices-page .terminal-footer-strip { padding: 10px 12px; }
    .prices-page .footer-source-note { color: var(--prices-muted); }
    .prices-page .terminal-action-link { color: var(--prices-brand); }
    .prices-page :is(.sep, .flag-icon) { color: var(--prices-muted); }
    .prices-page :is(button, a):focus-visible { outline: 3px solid rgba(109,26,149,.3); outline-offset: 3px; }

    @media (max-width: 900px) {
        .prices-intro-grid { grid-template-columns: 1fr; align-items: start; }
        .prices-actions { flex-wrap: wrap; }
        .prices-next { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .prices-container { width: calc(100% - 24px); }
        .prices-intro { padding: 32px 0 24px; }
        .prices-actions, .prices-btn { width: 100%; }
        .prices-section-head { align-items: start; flex-direction: column; gap: 8px; }
        .prices-next-item { padding: 18px; }
        .prices-page .terminal-market-bar { align-items: stretch; }
        .prices-page .market-tabs-group { display: grid; grid-template-columns: 1fr; width: 100%; }
        .prices-page .market-tab-btn { justify-content: flex-start; width: 100%; }
        .prices-page .terminal-connection-badge { align-self: flex-start; }
    }
</style>
@endpush

@section('content')
<div class="prices-page">
    <header class="prices-intro">
        <div class="prices-container">
            <ol class="prices-breadcrumb" aria-label="Breadcrumb">
                <li><a href="{{ route('home') }}">Home</a></li>
                <li aria-current="page">Market prices</li>
            </ol>
            <div class="prices-intro-grid">
                <div>
                    <p class="prices-kicker">Coffee market data</p>
                    <h1>Current coffee prices and benchmarks</h1>
                    <p class="prices-lead">Review Ethiopian export floor prices alongside Arabica and Robusta market charts. Use the controls below to change the market, origin, and timeframe.</p>
                </div>
                <div class="prices-actions">
                    <a href="{{ route('marketplace.index') }}" class="prices-btn prices-btn-secondary">Browse coffee lots</a>
                </div>
            </div>
        </div>
    </header>

    <main class="prices-container prices-main">
        <section aria-labelledby="chart-heading">
            <div class="prices-section-head">
                <div>
                    <h2 id="chart-heading">Market chart</h2>
                    <p>Choose a market and timeframe to inspect the available price history.</p>
                </div>
            </div>
            @include('market-prices.partials.chart-widget', [
                'market' => $market ?? 'ethiopia',
                'origin' => $origin ?? 'composite',
                'timeframe' => $timeframe ?? '1d',
                'chartData' => $chartData ?? null,
            ])
        </section>

        <section class="prices-section" aria-labelledby="floor-prices-heading">
            <div class="prices-section-head">
                <div>
                    <h2 id="floor-prices-heading">Official Ethiopian export floor prices</h2>
                    <p>Minimum export contract benchmarks published by the Ethiopian Coffee and Tea Authority.</p>
                </div>
                <div class="prices-effective">Effective date: <strong>{{ $latestPriceDataset?->effective_date?->format('d M Y') ?? 'Not available' }}</strong></div>
            </div>

            <div class="prices-table-wrap">
                <table class="prices-table">
                    <thead>
                        <tr>
                            <th>Origin</th>
                            <th>Grade and process</th>
                            <th class="text-end">USD/lb</th>
                            <th class="text-end">USD/MT</th>
                            <th class="text-end">Available lots</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priceRecords as $record)
                            <tr>
                                <td>
                                    <span class="prices-origin">{{ $record->origin }}</span>
                                    <span class="prices-detail">{{ $record->coffee_type }}</span>
                                </td>
                                <td>
                                    <span class="prices-grade">{{ $record->grade }}</span>
                                    <span class="prices-detail">{{ $record->processing_method }}</span>
                                </td>
                                <td class="text-end"><span class="prices-number">${{ number_format($record->minimum_price, 3) }}</span></td>
                                <td class="text-end"><span class="prices-number">${{ number_format($record->minimum_price * 2204.62, 0) }}</span></td>
                                <td class="text-end"><a href="{{ route('marketplace.index', ['origin' => $record->origin]) }}" class="prices-source">View lots</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="prices-empty">No published price schedule is currently available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="prices-note">Export contracts registered in Ethiopia must meet the applicable ECTA minimum price in effect when the contract is registered.</p>
        </section>

        <nav class="prices-next" aria-label="Market price next steps">
            <a href="{{ route('marketplace.index') }}" class="prices-next-item">
                <div><h3>Browse available coffee</h3><p>Compare published lots from verified Ethiopian exporters.</p></div>
                <span class="prices-next-arrow" aria-hidden="true">→</span>
            </a>
            <a href="{{ route('free-tools.index') }}" class="prices-next-item">
                <div><h3>Calculate an export margin</h3><p>Estimate contract costs, freight, and potential margin.</p></div>
                <span class="prices-next-arrow" aria-hidden="true">→</span>
            </a>
        </nav>
    </main>
</div>
@endsection
