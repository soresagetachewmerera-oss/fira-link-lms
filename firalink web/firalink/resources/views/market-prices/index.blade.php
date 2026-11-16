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
<style>
    .prices-page { --prices-brand: var(--fira-primary); --prices-brand-dark: var(--fira-primary-dark); --prices-brand-tint: var(--fira-primary-tint);
        --prices-ink: var(--fira-neutral-900); --prices-body: var(--fira-neutral-700); --prices-muted: var(--fira-neutral-500);
        --prices-border: var(--fira-neutral-300); --prices-subtle: var(--fira-neutral-100);
        min-height: 100vh; padding-bottom: 64px; color: var(--prices-body); background: #fff; font-family: 'Inter', sans-serif; }
    .prices-container { width: min(calc(100% - 32px), 1280px); margin-inline: auto; }
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
    @media (max-width: 640px) { .prices-container { width: calc(100% - 32px); } .prices-intro { padding: 32px 0 24px; } .prices-section-head { flex-direction: column; align-items: start; gap: 8px; } .prices-btn { width: 100%; } }
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
        'live' => 'fa-circle', 'delayed' => 'fa-clock', 'daily_close' => 'fa-calendar-day', 'stale' => 'fa-history', default => 'fa-ban',
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

        <div class="prices-top">
            <section class="prices-card prices-hero" aria-labelledby="hero-name" id="market-hero">
                @if($featured)
                    <p class="prices-hero-name" id="hero-name">{{ $featured['name'] }}</p>
                    <div><span class="prices-hero-value">{{ $fmt($featured['value']) }}</span><span class="prices-hero-unit muted">{{ $unit($featured) }}</span></div>
                    <div class="prices-status"><i class="fas {{ $trendIcon($featured['trend']['label']) }}" aria-hidden="true"></i>{{ $t('trend.' . $featured['trend']['label']) }}</div>
                    <div class="prices-status muted"><i class="fas {{ $icon($featured['freshness']['status']) }}" aria-hidden="true"></i>{{ $featured['freshness']['label'] }}</div>
                    @if($featured['source_time'])
                        <div class="prices-small muted">{{ $t('source_time', ['time' => $time($featured['source_time'], $isDaily($featured))]) }}</div>
                    @endif
                    <div class="prices-small muted">{{ $t('source', ['name' => $featured['source']['attribution']]) }}</div>
                @else
                    <p class="prices-hero-name" id="hero-name">{{ $t('card_title') }}</p>
                    <div class="prices-status"><i class="fas fa-ban" aria-hidden="true"></i>{{ $t('unavailable') }}</div>
                    <p class="prices-small muted">{{ $t('unavailable_hint') }}</p>
                    <button type="button" class="prices-btn" style="margin-top:12px" data-market-retry>{{ $t('try_again') }}</button>
                @endif
            </section>

            <section class="prices-card" aria-labelledby="chart-heading">
                <div class="prices-section-head" style="margin-bottom:8px">
                    <div><h2 id="chart-heading" style="font-size:18px">{{ $t('chart_heading') }}</h2><p>{{ $t('chart_hint') }}</p></div>
                </div>
                <div class="prices-ranges" role="group" aria-label="{{ $t('chart_heading') }}">
                    @foreach(['1D', '1W', '1M', '3M', '1Y'] as $r)
                        <button type="button" class="prices-range" data-range="{{ $r }}" aria-pressed="{{ $r === '1M' ? 'true' : 'false' }}">{{ $t('ranges.' . $r) }}</button>
                    @endforeach
                </div>
                <div class="prices-chart" id="market-chart"><div class="prices-chart-msg">{{ $featured ? '…' : $t('chart_empty') }}</div></div>
                <div class="prices-axis" id="market-axis" aria-hidden="true"></div>
            </section>
        </div>

        <section class="prices-section" aria-labelledby="benchmarks-heading">
            <div class="prices-section-head"><div><h2 id="benchmarks-heading">{{ $t('benchmarks') }}</h2></div></div>
            <div class="prices-table-wrap">
                <table class="prices-table">
                    <thead><tr><th>{{ $t('col_benchmark') }}</th><th class="text-end">{{ $t('col_price') }}</th><th>{{ $t('col_status') }}</th><th>{{ $t('col_source') }}</th></tr></thead>
                    <tbody id="market-rows">
                        @foreach($overview['benchmarks'] as $row)
                            <tr data-symbol="{{ $row['symbol'] }}" data-selectable="{{ $hasValue($row) ? 1 : 0 }}" @if($hasValue($row)) tabindex="0" @endif aria-selected="{{ $featured && $featured['symbol'] === $row['symbol'] ? 'true' : 'false' }}">
                                <td><span class="prices-name">{{ $row['name'] }}</span></td>
                                <td class="text-end">
                                    @if($hasValue($row))<span class="prices-number">{{ $fmt($row['value']) }}</span><span class="prices-detail">{{ $unit($row) }}</span>
                                    @else<span class="prices-detail">{{ $t('unavailable') }}</span>@endif
                                </td>
                                <td><span class="prices-status" style="margin:0"><i class="fas {{ $icon($row['freshness']['status']) }}" aria-hidden="true"></i>{{ $row['freshness']['label'] }}</span></td>
                                <td><span class="prices-detail" style="margin:0">{{ $row['source']['attribution'] }}</span>
                                    @if($hasValue($row) && $row['source_time'])<span class="prices-detail">{{ $time($row['source_time'], $isDaily($row)) }}</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="prices-card" style="margin-top:16px">
                <h3 style="margin:0 0 8px;font-size:16px;color:var(--prices-ink)">{{ $t('summary') }}</h3>
                <p style="margin:0" id="market-summary">{{ $overview['summary']['text'] }}</p>
                <p class="prices-note">{{ $t('summary_note') }}</p>
            </div>
            <p class="prices-note" id="market-attribution">{{ $t('attribution', ['names' => $attributions->implode(', ')]) }}</p>
            <p class="prices-note" id="market-updated">{{ $t('updated', ['time' => $time($overview['generated_at'], false)]) }}</p>
        </section>

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
<script>
(() => {
    // Reads the same API as the app. Never draws or shows a number the server did not send.
    const api = @js(url('/api/market'));
    const lang = @js(app()->getLocale());
    const text = @js(['chart_empty' => $t('chart_empty'), 'chart_failed' => $t('chart_failed'), 'refresh_failed' => $t('refresh_failed', ['time' => '{time}']), 'updated' => $t('updated', ['time' => '{time}'])]);
    let symbol = @js($featured['symbol'] ?? null);
    let range = '1M';
    let lastGood = @js($overview['generated_at']);
    let failures = 0, timer = null;
    const initial = JSON.stringify(@js(collect($overview['benchmarks'])->map(fn ($b) => [$b['symbol'], $b['value'], $b['freshness']['status']])->values()));
    const daily = new Set(@js(collect($overview['benchmarks'])->filter(fn ($r) => $isDaily($r))->pluck('symbol')->values()));
    const chart = document.getElementById('market-chart');
    const axis = document.getElementById('market-axis');
    const notice = document.getElementById('market-notice');
    const fmtTime = (iso, dateOnly) => { const d = new Date(iso); if (isNaN(d)) return ''; const p = n => String(n).padStart(2, '0');
        return dateOnly ? `${d.getUTCFullYear()}-${p(d.getUTCMonth() + 1)}-${p(d.getUTCDate())}` : `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`; };
    const msg = t => { chart.innerHTML = ''; const m = document.createElement('div'); m.className = 'prices-chart-msg'; m.textContent = t; chart.appendChild(m); axis.textContent = ''; };
    const get = url => fetch(url, { headers: { Accept: 'application/json', 'Accept-Language': lang } }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

    async function drawSeries() {
        if (!symbol) return msg(text.chart_empty);
        try {
            const body = await get(`${api}/series?symbol=${encodeURIComponent(symbol)}&range=${range}`);
            const pts = (body.data.points || []).map(p => ({ v: parseFloat(p.value), t: new Date(p.source_time) })).filter(p => !isNaN(p.v) && !isNaN(p.t));
            if (pts.length < 2) return msg(text.chart_empty);
            const W = 600, H = 220, lo = Math.min(...pts.map(p => p.v)), hi = Math.max(...pts.map(p => p.v));
            const span = (hi - lo) || 1, t0 = pts[0].t.getTime(), tw = (pts[pts.length - 1].t.getTime() - t0) || 1;
            const d = pts.map((p, i) => `${i ? 'L' : 'M'}${((p.t.getTime() - t0) / tw * W).toFixed(1)},${(H - (p.v - lo) / span * H).toFixed(1)}`).join(' ');
            chart.innerHTML = `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" role="img" aria-label="${lo.toFixed(2)} – ${hi.toFixed(2)}"><path d="${d}" fill="none" stroke="var(--fira-primary)" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg>`;
            const dateOnly = daily.has(symbol);
            axis.innerHTML = '';
            [fmtTime(pts[0].t, dateOnly), `${lo.toFixed(2)} – ${hi.toFixed(2)}`, fmtTime(pts[pts.length - 1].t, dateOnly)].forEach(s => { const e = document.createElement('span'); e.textContent = s; axis.appendChild(e); });
        } catch (e) { msg(text.chart_failed); }
    }

    async function refresh() {
        try {
            const body = await get(`${api}/overview`);
            lastGood = body.data.generated_at;
            failures = 0;
            notice.classList.remove('is-visible');
            document.getElementById('market-summary').textContent = body.data.summary.text;
            document.getElementById('market-updated').textContent = text.updated.replace('{time}', fmtTime(lastGood, false));
            // A value or freshness changed on the server: reload so every row is rendered by the server again.
            const latest = JSON.stringify((body.data.benchmarks || []).map(b => [b.symbol, b.value, b.freshness.status]));
            if (latest !== initial) { location.reload(); return; }
            drawSeries();
        } catch (e) {
            failures++;
            document.getElementById('market-notice-text').textContent = text.refresh_failed.replace('{time}', fmtTime(lastGood, false));
            notice.classList.add('is-visible');
        }
    }

    function schedule() {
        clearTimeout(timer);
        const wait = 60000 * Math.min(10, 2 ** Math.min(failures, 4));
        timer = setTimeout(async () => { if (document.visibilityState === 'visible') await refresh(); schedule(); }, wait);
    }
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') schedule(); else clearTimeout(timer); });

    document.querySelectorAll('.prices-range').forEach(b => b.addEventListener('click', () => {
        range = b.dataset.range;
        document.querySelectorAll('.prices-range').forEach(x => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
        drawSeries();
    }));
    document.querySelectorAll('#market-rows tr[data-selectable="1"]').forEach(row => {
        const pick = () => { symbol = row.dataset.symbol; document.querySelectorAll('#market-rows tr').forEach(r => r.setAttribute('aria-selected', r === row ? 'true' : 'false')); drawSeries(); };
        row.addEventListener('click', pick);
        row.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
    });
    document.querySelectorAll('[data-market-retry]').forEach(b => b.addEventListener('click', () => location.reload()));
    drawSeries();
    schedule();
})();
</script>
@endpush
