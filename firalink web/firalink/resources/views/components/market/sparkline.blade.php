@props(['values' => [], 'ref' => null, 'id'])

{{-- Server-drawn sparkline (decorative; the row's text says the same). Colour comes from the row's direction class. --}}
@php($path = \App\Support\Market\SparklinePath::build($values, $ref))
<span class="fira-mw__spark" aria-hidden="true">
    @if($path)
        <svg viewBox="0 0 96 34" width="96" height="34" focusable="false">
            <defs>
                <linearGradient id="mwfill-{{ $id }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="currentColor" stop-opacity="0.28"/>
                    <stop offset="1" stop-color="currentColor" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <path d="{{ $path['area'] }}" fill="url(#mwfill-{{ $id }})" stroke="none"/>
            @if($path['ref_y'] !== null)
                <line class="fira-mw__spark-ref" x1="3" x2="93" y1="{{ $path['ref_y'] }}" y2="{{ $path['ref_y'] }}"/>
            @endif
            <path d="{{ $path['line'] }}" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" stroke-linecap="round"/>
            <circle cx="{{ $path['last_x'] }}" cy="{{ $path['last_y'] }}" r="2" fill="currentColor"/>
        </svg>
    @endif
</span>
