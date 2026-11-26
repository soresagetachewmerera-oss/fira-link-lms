@props([
    'label1',
    'label2' => '',
    'value',
    'unit' => null,
    'status' => null,
    'state' => 'available',
    'source' => null,
    'asOf' => null,
    'href' => null,
])
@php
    $stateClass = match ($state) {
        'stale' => 'is-stale',
        'unavailable' => 'is-unavailable',
        'pre_launch' => 'is-fallback',
        default => 'is-available',
    };
    $metaTitle = collect([$source, $asOf])->filter()->implode(' · ');
@endphp
<li class="fira-hero-tile {{ $stateClass }}" @if($metaTitle) title="{{ $metaTitle }}" @endif>
    @if($href)
        <a href="{{ $href }}" class="fira-hero-tile__link">
    @else
        <div class="fira-hero-tile__inner">
    @endif
        <div class="fira-hero-tile__header">
            <span class="fira-hero-tile__label">
                <span class="fira-hero-tile__label-1">{{ $label1 }}</span>
                @if($label2 !== '')
                    <span class="fira-hero-tile__label-2">{{ $label2 }}</span>
                @endif
            </span>
        </div>

        <div class="fira-hero-tile__metric">
            <span class="fira-hero-tile__value">{{ $value }}</span>
            @if($unit)
                <span class="fira-hero-tile__unit">{{ $unit }}</span>
            @endif
        </div>

        @if($status)
            <div class="fira-hero-tile__status">{{ $status }}</div>
        @endif

        @isset($graph)
            <div class="fira-hero-tile__graph" aria-hidden="true">
                {{ $graph }}
            </div>
        @endisset

        @if($source || $asOf)
            <div class="fira-hero-tile__source">
                <span>{{ $source }}</span>
                @if($asOf)
                    <time datetime="{{ $asOf }}">{{ \Illuminate\Support\Str::limit($asOf, 10, '') }}</time>
                @endif
            </div>
        @endif
    @if($href)
        </a>
    @else
        </div>
    @endif
</li>
