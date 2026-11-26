@props([
    'label1',
    'label2' => '',
    'value',
    'unit' => null,
    'status' => null,
    'chip' => null,
    'state' => 'available',
    'source' => null,
    'asOf' => null,
    'href' => null,
    'textValue' => false,
    'cardKey' => null,
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
<li class="fira-hero-tile {{ $stateClass }} {{ $textValue ? 'fira-hero-tile--text' : '' }}"
    @if($cardKey) data-hero-card="{{ $cardKey }}" @endif
    @if($metaTitle) title="{{ $metaTitle }}" @endif>
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
            @if($chip)
                <span class="fira-hero-tile__chip">{{ $chip }}</span>
            @endif
        </div>

        <div class="fira-hero-tile__metric">
            <span class="fira-hero-tile__value" data-hero-value>{{ $value }}</span>
            @if($unit)
                <span class="fira-hero-tile__unit" data-hero-unit>{{ $unit }}</span>
            @endif
        </div>

        @if($status)
            <div class="fira-hero-tile__status" data-hero-status>{{ $status }}</div>
        @endif

        @isset($graph)
            <div class="fira-hero-tile__graph" aria-hidden="true">
                {{ $graph }}
            </div>
        @endisset

        @if($source || $asOf)
            <div class="fira-hero-tile__source">
                <span data-hero-source>{{ $source }}</span>
                @if($asOf)
                    <time datetime="{{ $asOf }}" data-hero-time>{{ \Illuminate\Support\Str::limit($asOf, 10, '') }}</time>
                @endif
            </div>
        @endif
    @if($href)
        </a>
    @else
        </div>
    @endif
</li>
