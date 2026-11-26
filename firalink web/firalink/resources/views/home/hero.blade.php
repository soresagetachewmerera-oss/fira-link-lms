@php
    $cards = $heroOverview['cards'] ?? [];

    $coffeeMarket = $cards['coffee_market'] ?? [
        'status' => 'unavailable',
        'display_value' => 'Unavailable',
        'unit_short' => null,
        'status_text' => 'No current daily close',
        'source' => 'International Coffee Organization (ICO)',
        'as_of' => null,
        'as_of_date' => null,
        'sparkline' => null,
        'trend' => null,
    ];
    $coffeeValue = $coffeeMarket['status'] === 'unavailable' ? 'Unavailable' : $coffeeMarket['display_value'];
    $coffeeUnit = $coffeeMarket['status'] === 'unavailable' ? null : ($coffeeMarket['unit_short'] ?? null);
    $coffeeStatus = match ($coffeeMarket['status']) {
        'unavailable' => 'No current daily close',
        'stale' => trim(($coffeeMarket['trend'] ? $coffeeMarket['trend'] . ' · ' : '') . 'Stale · ' . ($coffeeMarket['as_of_date'] ?? '')),
        default => trim(($coffeeMarket['trend'] ? $coffeeMarket['trend'] . ' · ' : '') . 'Daily close · ' . ($coffeeMarket['as_of_date'] ?? '')),
    };

    $verifiedLots = $cards['verified_lots'] ?? [
        'status' => 'pre_launch',
        'mode' => 'browse_fallback',
        'display_value' => 'Coffee B2B',
        'subtitle' => 'Browse verified lots',
        'bars' => [],
        'source' => 'Fira Link Marketplace',
        'as_of' => null,
    ];
    $lotsFallback = ($verifiedLots['mode'] ?? 'browse_fallback') !== 'count';
    $lotBars = $verifiedLots['bars'] ?? [];

    $landedCost = $cards['landed_cost'] ?? [
        'status' => 'unavailable',
        'display_value' => 'Unavailable',
        'unit' => null,
        'status_text' => 'Example shipment',
        'cost_shares' => [],
        'source' => 'Fira Link Export Profit Calculator',
        'as_of' => null,
    ];
    $costShares = $landedCost['cost_shares'] ?? [];

    $firaAi = $cards['fira_ai'] ?? [
        'status' => 'available',
        'feature_line' => 'Ask about prices and export steps',
        'chip' => 'Sources named',
        'source' => 'Fira AI Market Snapshot',
        'as_of' => null,
    ];

    $floatingEcta = $heroOverview['floating_ecta'] ?? null;
    $proofBand = $heroOverview['proof_band'] ?? [];
    $proofCount = count($proofBand);
    $centerStatIndex = $proofCount >= 3 ? intdiv($proofCount, 2) : -1;
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
            <div class="fira-hero__tray" data-hero-tray data-overview-url="{{ url('/api/market/overview') }}">
                <ul class="fira-hero__tray-grid" aria-label="Market snapshot">
                    {{-- 1. Coffee Market --}}
                    <x-market-card
                        cardKey="coffee_market"
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

                    {{-- 2. Verified Lots (or Coffee B2B browse fallback when 0 / pre-launch) --}}
                    @if($lotsFallback)
                        <x-market-card
                            cardKey="verified_lots"
                            label1="Coffee"
                            label2="B2B"
                            value="Browse verified lots"
                            status="Verified exporter directory"
                            state="pre_launch"
                            :textValue="true"
                            :source="$verifiedLots['source'] ?? 'Fira Link Marketplace'"
                            :asOf="$verifiedLots['as_of'] ?? null"
                            :href="route('marketplace.index')"
                        >
                            <x-slot:graph>
                                <svg class="fira-hero__bars" viewBox="0 0 180 44" preserveAspectRatio="none" focusable="false">
                                    <rect class="fira-hero__bar" x="6" y="22" width="22" height="20" rx="5"></rect>
                                    <rect class="fira-hero__bar" x="38" y="14" width="22" height="28" rx="5"></rect>
                                    <rect class="fira-hero__bar fira-hero__bar--strong" x="70" y="6" width="22" height="36" rx="5"></rect>
                                    <rect class="fira-hero__bar" x="102" y="18" width="22" height="24" rx="5"></rect>
                                    <rect class="fira-hero__bar" x="134" y="24" width="22" height="18" rx="5"></rect>
                                </svg>
                            </x-slot:graph>
                        </x-market-card>
                    @else
                        <x-market-card
                            cardKey="verified_lots"
                            label1="Verified"
                            label2="lots"
                            :value="$verifiedLots['display_value']"
                            :status="$verifiedLots['status_text']"
                            :state="$verifiedLots['status']"
                            :source="$verifiedLots['source'] ?? 'Fira Link Marketplace'"
                            :asOf="$verifiedLots['as_of'] ?? null"
                            :href="route('marketplace.index')"
                        >
                            <x-slot:graph>
                                <svg class="fira-hero__bars" viewBox="0 0 180 44" preserveAspectRatio="none" focusable="false">
                                    @foreach(array_slice($lotBars, 0, 5) as $idx => $ratio)
                                        @php
                                            $barHeight = max(8, (int) round($ratio * 36));
                                            $barY = 42 - $barHeight;
                                            $barX = 6 + ($idx * 32);
                                        @endphp
                                        <rect class="fira-hero__bar {{ $idx === 0 ? 'fira-hero__bar--strong' : '' }}" x="{{ $barX }}" y="{{ $barY }}" width="22" height="{{ $barHeight }}" rx="5"></rect>
                                    @endforeach
                                </svg>
                            </x-slot:graph>
                        </x-market-card>
                    @endif

                    {{-- 3. Landed Cost (from CoffeeExportService::calculateProfit) --}}
                    <x-market-card
                        cardKey="landed_cost"
                        label1="Landed"
                        label2="cost"
                        :value="$landedCost['display_value']"
                        :unit="$landedCost['unit'] ?? null"
                        :status="$landedCost['status_text']"
                        :state="$landedCost['status']"
                        :source="$landedCost['source'] ?? 'Fira Link Export Profit Calculator'"
                        :asOf="$landedCost['as_of'] ?? null"
                        :href="route('free-tools.index') . '#calculator'"
                    >
                        <x-slot:graph>
                            @if(!empty($costShares))
                                <svg class="fira-hero__arcs" viewBox="0 0 200 44" preserveAspectRatio="none" focusable="false">
                                    <path class="fira-hero__arc fira-hero__arc--soft" d="M6 38 Q 100 6 194 38"></path>
                                    <path class="fira-hero__arc fira-hero__arc--mid" d="M6 38 Q 100 16 194 38"></path>
                                    <path class="fira-hero__arc fira-hero__arc--primary" d="M6 38 Q {{ 60 + min(80, (int) round($costShares[0] ?? 50)) }} 24 194 38"></path>
                                </svg>
                            @else
                                <span class="fira-hero-tile__no-series">Calculator scenario ready</span>
                            @endif
                        </x-slot:graph>
                    </x-market-card>

                    {{-- 4. Fira AI --}}
                    <x-market-card
                        cardKey="fira_ai"
                        label1="Fira"
                        label2="AI"
                        :value="$firaAi['feature_line'] ?? 'Ask about prices and export steps'"
                        :chip="$firaAi['chip'] ?? 'Sources named'"
                        :textValue="true"
                        status="Trade & market guidance"
                        :state="$firaAi['status'] ?? 'available'"
                        :source="$firaAi['source'] ?? 'Fira AI Market Snapshot'"
                        :asOf="$firaAi['as_of'] ?? null"
                        :href="route('ai-chat.public')"
                    >
                        <x-slot:graph>
                            <svg class="fira-hero__wave" viewBox="0 0 200 48" preserveAspectRatio="none" focusable="false">
                                <path class="fira-hero__wave-aura" d="M0 28 C 36 10, 68 44, 104 26 C 140 8, 170 36, 200 20"></path>
                                <path class="fira-hero__wave-line" d="M0 28 C 36 10, 68 44, 104 26 C 140 8, 170 36, 200 20"></path>
                            </svg>
                        </x-slot:graph>
                    </x-market-card>
                </ul>
            </div>

            @if($floatingEcta && ($floatingEcta['status'] ?? null) === 'available')
                <aside class="fira-hero__float"
                       aria-label="{{ $floatingEcta['title'] }}"
                       title="{{ $floatingEcta['source'] ?? 'ECTA' }} · {{ $floatingEcta['effective_date'] ?? '' }}">
                    <span class="fira-hero__float-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <div class="fira-hero__float-copy">
                        <strong class="fira-hero__float-title">{{ $floatingEcta['title'] }}</strong>
                        <span class="fira-hero__float-meta">
                            Effective {{ $floatingEcta['effective_date'] }} · {{ number_format((int) $floatingEcta['spec_count']) }} specs
                        </span>
                    </div>
                </aside>
            @endif
        </div>

        @if($proofCount > 0)
            <div class="fira-hero__proof" aria-label="Platform and market proof points">
                <div class="fira-hero__diamonds" aria-hidden="true">
                    <svg width="100%" height="12" preserveAspectRatio="none" focusable="false">
                        <defs>
                            <pattern id="fira-diamond-strip" width="16" height="12" patternUnits="userSpaceOnUse">
                                <polygon points="8,1 13,6 8,11 3,6" fill="currentColor"></polygon>
                            </pattern>
                        </defs>
                        <rect width="100%" height="12" fill="url(#fira-diamond-strip)"></rect>
                    </svg>
                </div>

                <div class="fira-hero__proof-grid fira-hero__proof-grid--{{ min(5, $proofCount) }}">
                    @foreach($proofBand as $index => $stat)
                        <x-hero-stat
                            :value="$stat['value']"
                            :unit="$stat['unit'] ?? null"
                            :label="$stat['label']"
                            :sublabel="$stat['sublabel'] ?? null"
                            :source="$stat['source'] ?? null"
                            :asOf="$stat['as_of'] ?? null"
                            :highlight="$index === $centerStatIndex"
                        />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

@push('scripts')
    <script src="{{ asset('assets/front/js/hero-overview.js') }}?v={{ file_exists(public_path('assets/front/js/hero-overview.js')) ? filemtime(public_path('assets/front/js/hero-overview.js')) : '1.0.0' }}" defer></script>
@endpush
