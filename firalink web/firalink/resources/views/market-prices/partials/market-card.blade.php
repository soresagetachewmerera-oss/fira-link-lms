{{--
    Coffee market card for web pages. $overview comes from MarketReadService::overview() (same data as
    GET /api/market/overview). Shows the featured sourced price with trend, freshness, source and time,
    or "Price unavailable". Never a default or estimated number. Links to the market prices page.
--}}
@php
    $mcHas = fn ($r) => is_array($r) && $r['value'] !== null && ($r['freshness']['status'] ?? 'unavailable') !== 'unavailable';
    $mcRow = collect([$overview['hero'] ?? null])->filter()->first(fn ($r) => $mcHas($r))
        ?? collect($overview['benchmarks'] ?? [])->first(fn ($r) => $mcHas($r));
    $mcUnitKey = $mcRow ? $mcRow['currency'] . '/' . $mcRow['unit'] : null;
    $mcUnit = $mcUnitKey ? (__('market.web.units.' . $mcUnitKey) === 'market.web.units.' . $mcUnitKey ? $mcUnitKey : __('market.web.units.' . $mcUnitKey)) : null;
    $mcTrendIcon = $mcRow ? match ($mcRow['trend']['label']) { 'bullish' => 'fa-arrow-trend-up', 'bearish' => 'fa-arrow-trend-down', 'sideways' => 'fa-arrows-left-right', default => 'fa-hourglass-half' } : null;
    $mcFreshIcon = $mcRow ? match ($mcRow['freshness']['status']) { 'live' => 'fa-circle', 'delayed' => 'fa-clock', 'daily_close' => 'fa-calendar-day', 'schedule' => 'fa-file-contract', 'stale' => 'fa-history', default => 'fa-ban' } : 'fa-ban';
@endphp
<a href="{{ route('market-prices.index') }}" class="fira-market-card" style="display:block;padding:20px;border-radius:var(--fira-radius);background:var(--fira-primary-dark);color:#fff;text-decoration:none;max-width:420px">
    <div style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:14px"><i class="fas fa-chart-line" aria-hidden="true"></i>{{ __('market.web.card_title') }}</div>
    @if($mcRow)
        <div style="margin-top:6px;font-size:13px;opacity:.85">{{ $mcRow['name'] }}</div>
        <div style="margin-top:8px"><span style="font-size:28px;font-weight:800;font-variant-numeric:tabular-nums">{{ number_format((float) $mcRow['value'], 2) }}</span>
            <span style="margin-left:6px;font-size:12px;opacity:.85">{{ $mcUnit }}</span></div>
        <div style="margin-top:6px;font-size:13px;font-weight:600"><i class="fas {{ $mcTrendIcon }}" aria-hidden="true"></i> {{ __('market.web.trend.' . $mcRow['trend']['label']) }}</div>
        <div style="margin-top:4px;font-size:12px;opacity:.85"><i class="fas {{ $mcFreshIcon }}" aria-hidden="true"></i> {{ $mcRow['freshness']['label'] }}</div>
        <div style="margin-top:2px;font-size:12px;opacity:.85">{{ __('market.web.source', ['name' => $mcRow['source']['attribution']]) }}</div>
    @else
        <div style="margin-top:10px;font-size:14px;font-weight:600"><i class="fas fa-ban" aria-hidden="true"></i> {{ __('market.web.unavailable') }}</div>
        <div style="margin-top:4px;font-size:12px;opacity:.85">{{ __('market.web.unavailable_hint') }}</div>
    @endif
</a>
