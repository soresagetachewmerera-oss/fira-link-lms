@props(['watch', 'keys' => null, 'linked' => false, 'selected' => null, 'seeAll' => false, 'listId' => null, 'refresh' => false])

{{-- Market watch card (schema 1 from MarketWatchService). Server-rendered, works without JavaScript. --}}
@php
    $rows = collect($watch['rows']);
    if ($keys !== null) {
        $rows = collect($keys)->map(fn ($key) => $rows->firstWhere('key', $key))->filter()->values();
    }
    $sources = collect($watch['sources'])->map(fn ($a) => preg_match('/\(([^)]+)\)\s*$/', $a, $m) ? $m[1] : $a)->implode(', ');
    $titleId = 'fira-mw-title-' . ($listId ?? 'card');
@endphp

<section class="fira-mw" aria-labelledby="{{ $titleId }}">
    <header class="fira-mw__head">
        <h3 class="fira-mw__title" id="{{ $titleId }}">{{ __('market.watch.title') }}</h3>
        <p class="fira-mw__chip is-{{ $watch['status']['status'] }}">
            <span class="fira-mw__chip-dot" aria-hidden="true"></span>{{ $watch['status']['label'] }}
        </p>
        @if($refresh)
            <a class="fira-mw__refresh" href="{{ request()->fullUrl() }}" data-market-refresh aria-label="{{ __('market.watch.detail.refresh') }}" title="{{ __('market.watch.detail.refresh') }}">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20 11a8 8 0 1 0-2.34 5.66M20 4v7h-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        @endif
    </header>

    <ul class="fira-mw__rows" @if($listId) id="{{ $listId }}" @endif>
        @foreach($rows as $row)
            <x-market.row :row="$row" :href="$linked ? route('market-prices.index', ['market' => $row['key']]) : null" :current="$selected === $row['key']" />
        @endforeach
    </ul>

    <footer class="fira-mw__foot">
        @if($sources !== '')
            <p class="fira-mw__sources">{{ __('market.watch.sources', ['names' => $sources]) }}</p>
        @endif
        <p class="fira-mw__disclaimer">{{ $watch['disclaimer'] }}</p>
        @if($seeAll)
            <a class="fira-mw__more" href="{{ route('market-prices.index') }}">{{ __('market.watch.see_all') }}</a>
        @endif
    </footer>
</section>
