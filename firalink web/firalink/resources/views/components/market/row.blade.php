@props(['row', 'selectable' => false, 'selected' => false])

@php
    $hasPrice = $row['price'] !== null;
    $direction = $row['direction'] ?? 'none';
    $number = fn ($v, $d) => number_format(abs((float) $v), $d, '.', ',');
    $sign = fn ($v) => $v > 0 ? '+' : ($v < 0 ? '−' : '');
    // Short source name for the detail line: "International Coffee Organization (ICO)" -> "ICO".
    $source = preg_match('/\(([^)]+)\)\s*$/', (string) $row['source']['attribution'], $m) ? $m[1] : $row['source']['name'];
    $classes = 'fira-mw__row is-' . ($hasPrice ? $direction : 'none') . ' is-' . $row['status'];
    // Escaped first; the only markup added keeps a date like 2026-11-27 on one line.
    $detail = preg_replace('/\d{4}-\d{2}-\d{2}/', '<span class="fira-mw__nowrap">$0</span>', e($row['status_label'] . ' · ' . $source));
@endphp

<li {{ $attributes->merge(['class' => $classes]) }}
    data-symbol="{{ $row['key'] }}"
    @if($selectable)
        data-selectable="{{ $hasPrice ? 1 : 0 }}"
        role="option"
        aria-selected="{{ $selected ? 'true' : 'false' }}"
        @if($hasPrice) tabindex="0" @endif
    @endif>
    <span class="fira-mw__sr">{{ $row['a11y_label'] }}</span>

    <span class="fira-mw__id" aria-hidden="true">
        <span class="fira-mw__name">
            <span class="fira-mw__glyph">
                @if(!$hasPrice || $direction === 'none')
                    <svg viewBox="0 0 12 12" width="12" height="12" focusable="false"><circle cx="6" cy="6" r="2.5" fill="currentColor"/></svg>
                @elseif($direction === 'up')
                    <svg viewBox="0 0 12 12" width="12" height="12" focusable="false"><path d="M6 1.5 11 10H1z" fill="currentColor"/></svg>
                @elseif($direction === 'down')
                    <svg viewBox="0 0 12 12" width="12" height="12" focusable="false"><path d="M6 10.5 1 2h10z" fill="currentColor"/></svg>
                @else
                    <svg viewBox="0 0 12 12" width="12" height="12" focusable="false"><rect x="1.5" y="5" width="9" height="2" rx="1" fill="currentColor"/></svg>
                @endif
            </span>
            <span class="fira-mw__short">{{ $row['short_name'] }}</span>
        </span>
        <span class="fira-mw__detail">{!! $detail !!}</span>
    </span>

    <x-market.sparkline :values="$row['sparkline']" :ref="$row['spark_ref']" :id="\Illuminate\Support\Str::lower($row['key'])" />

    <span class="fira-mw__quote" aria-hidden="true">
        @if($hasPrice)
            <span class="fira-mw__price">{{ $number($row['price'], $row['decimals']) }}</span>
            <span class="fira-mw__unit">{{ $row['unit_label'] }}</span>
            @if($row['change'] !== null)
                <span class="fira-mw__change">{{ $sign($row['change']) }}{{ $number($row['change'], $row['decimals']) }} ({{ $sign($row['change']) }}{{ $number($row['change_pct'] ?? 0, 2) }}%)</span>
            @endif
        @else
            <span class="fira-mw__price fira-mw__price--none">{{ __('market.watch.unavailable') }}</span>
        @endif
    </span>
</li>
