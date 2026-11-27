{{-- Home: market watch band below the hero (market watch slice 2). Included only when $marketWatch['home']['visible']. --}}
<section class="fira-home-band fira-mw-band" aria-labelledby="fira-mw-home-heading">
    <div class="fira-container fira-mw-band__grid">
        <div class="fira-mw-band__intro">
            <p class="fira-mw-band__eyebrow">{{ __('market.watch.home.eyebrow') }}</p>
            <h2 class="fira-mw-band__heading" id="fira-mw-home-heading">{{ __('market.watch.home.heading') }}</h2>
            <p class="fira-mw-band__lead">{{ __('market.watch.home.lead') }}</p>
        </div>
        <x-market.watch :watch="$marketWatch" :keys="$marketWatch['home']['keys']" :see-all="true" :linked="true" list-id="home" />
    </div>
</section>
