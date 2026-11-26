@props([
    'value',
    'unit' => null,
    'label',
    'sublabel' => null,
    'source' => null,
    'asOf' => null,
    'highlight' => false,
])
@php
    $metaTitle = collect([$source, $asOf])->filter()->implode(' · ');
@endphp
<div class="fira-hero-stat {{ $highlight ? 'fira-hero-stat--highlight' : '' }}" @if($metaTitle) title="{{ $metaTitle }}" @endif>
    @if($highlight)
        <svg class="fira-hero-stat__laurel fira-hero-stat__laurel--left" viewBox="0 0 28 54" aria-hidden="true" focusable="false">
            <path d="M22 48C10 40 6 24 14 6M14 14c-3 2-5 5-6 9M11 25c-4 1-6 4-7 8M13 36c-4 0-7 2-8 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
        </svg>
    @endif

    <div class="fira-hero-stat__body">
        <div class="fira-hero-stat__figure">
            <span class="fira-hero-stat__value">{{ $value }}</span>
            @if($unit)
                <span class="fira-hero-stat__unit">{{ $unit }}</span>
            @endif
        </div>
        <div class="fira-hero-stat__label">{{ $label }}</div>
        @if($sublabel)
            <div class="fira-hero-stat__sublabel">{{ $sublabel }}</div>
        @endif
    </div>

    @if($highlight)
        <svg class="fira-hero-stat__laurel fira-hero-stat__laurel--right" viewBox="0 0 28 54" aria-hidden="true" focusable="false">
            <path d="M6 48C18 40 22 24 14 6M14 14c3 2 5 5 6 9M17 25c4 1 6 4 7 8M15 36c4 0 7 2 8 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
        </svg>
    @endif
</div>
