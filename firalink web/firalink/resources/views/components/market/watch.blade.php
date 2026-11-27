@props(['watch', 'keys' => null, 'selectable' => false, 'selected' => null, 'seeAll' => false, 'listId' => null])

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
    </header>

    <ul class="fira-mw__rows"
        @if($listId) id="{{ $listId }}" @endif
        @if($selectable) role="listbox" aria-labelledby="{{ $titleId }}" @endif>
        @foreach($rows as $row)
            <x-market.row :row="$row" :selectable="$selectable" :selected="$selected === $row['key']" />
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
