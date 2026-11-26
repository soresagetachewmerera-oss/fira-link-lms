@php
    $cards = $heroOverview['cards'] ?? [];
    $coffeeMarket = $cards['coffee_market'] ?? [
        'status' => 'unavailable',
        'display_value' => 'Unavailable',
        'unit_short' => null,
        'status_text' => 'Unavailable',
        'source' => 'International Coffee Organization (ICO)',
        'as_of' => null,
        'as_of_date' => null,
        'sparkline' => null,
    ];
    $coffeeValue = $coffeeMarket['status'] === 'unavailable'
        ? 'Unavailable'
        : $coffeeMarket['display_value'];
    $coffeeUnit = $coffeeMarket['status'] === 'unavailable'
        ? null
        : ($coffeeMarket['unit_short'] ?? null);
    $coffeeStatus = match ($coffeeMarket['status']) {
        'unavailable' => 'No current daily close',
        'stale' => trim(($coffeeMarket['trend'] ? $coffeeMarket['trend'] . ' · ' : '') . 'Stale · ' . ($coffeeMarket['as_of_date'] ?? '')),
        default => trim(($coffeeMarket['trend'] ? $coffeeMarket['trend'] . ' · ' : '') . 'Daily close · ' . ($coffeeMarket['as_of_date'] ?? '')),
    };
@endphp
<section class="fira-hero" aria-labelledby="homepage-hero-title">
    <div class="fira-hero__aura fira-hero__aura--left" aria-hidden="true"></div>
    <div class="fira-hero__aura fira-hero__aura--right" aria-hidden="true"></div>

    <div class="fira-hero__container">
        <div class="fira-hero__head">
            <h1 id="homepage-hero-title" class="fira-hero__title">The trade platform built for Ethiopian coffee</h1>
            <p class="fira-hero__subtitle">
                Official ECTA minimum prices, daily market benchmarks, landed-cost modeling, and practical export training in one workspace.
            </p>
            <div class="fira-hero__actions">
                <a href="{{ route('market-prices.index') }}" class="fira-hero__cta">Explore market prices</a>
            </div>
        </div>

        <div class="fira-hero__tray-wrap">
            <div class="fira-hero__tray">
                <ul class="fira-hero__tray-grid" aria-label="Market snapshot">
                    <x-market-card
                        label1="Coffee"
                        label2="market"
                        :value="$coffeeValue"
                        :unit="$coffeeUnit"
                        :status="$coffeeStatus"
                        :state="$coffeeMarket['status']"
                        :source="$coffeeMarket['source'] ?? 'International Coffee Organization (ICO)'"
                        :asOf="$coffeeMarket['as_of_date'] ?? $coffeeMarket['as_of']"
                        :href="route('market-prices.index')"
                    >
                        <x-slot:graph>
                            @if(!empty($coffeeMarket['sparkline']['line']))
                                <svg class="fira-hero__sparkline" viewBox="0 0 200 52" preserveAspectRatio="none" focusable="false">
                                    <path class="fira-hero__sparkline-area" d="{{ $coffeeMarket['sparkline']['area'] }}"></path>
                                    <path class="fira-hero__sparkline-line" d="{{ $coffeeMarket['sparkline']['line'] }}"></path>
                                </svg>
                            @else
                                <span class="fira-hero-tile__no-series">Daily close series pending</span>
                            @endif
                        </x-slot:graph>
                    </x-market-card>
                </ul>
            </div>
        </div>
    </div>
</section>
