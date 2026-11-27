@props(['detail' => null, 'empty' => false, 'account' => null])

{{-- Market watch detail panel (schema 1 from MarketSeriesService). Server-rendered; market-watch.js swaps it
     in place (GET /market-prices/panel) and adds the crosshair. Range and unit are plain links without JS. --}}
@php
    $fmt = fn ($v, $d) => $v === null ? '—' : number_format((float) $v, $d, '.', ',');
    $sign = fn ($v) => $v > 0 ? '+' : ($v < 0 ? '−' : '');
    $link = fn (array $q) => route('market-prices.index', array_filter($q + ['market' => $detail['key'] ?? null, 'range' => $detail['range'] ?? null, 'unit' => $detail['unit']['key'] ?? null]));
    $intraday = ($detail['range'] ?? null) === '1D';
    $when = fn (string $iso) => $intraday
        ? \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format('Y-m-d H:i')
        : \Illuminate\Support\Carbon::parse($iso)->utc()->format('Y-m-d');
    $points = $detail['points'] ?? [];
    $values = array_column($points, 'v');
    $path = \App\Support\Market\SparklinePath::build($values, $detail['stats']['prev_close'] ?? null, 600, 220, 8);
    $dec = $detail['decimals'] ?? 2;
@endphp

<section class="fira-mwd" id="market-detail" aria-labelledby="fira-mwd-title"
    @if($detail) data-key="{{ $detail['key'] }}" data-range="{{ $detail['range'] }}" data-unit="{{ $detail['unit']['key'] }}" @endif>
    @if(!$detail || $detail['price'] === null)
        <h2 class="fira-mwd__title" id="fira-mwd-title">{{ $detail['name'] ?? __('market.web.card_title') }}</h2>
        <p class="fira-mwd__none">{{ __('market.web.unavailable') }}</p>
        <p class="fira-mwd__note">{{ __('market.web.unavailable_hint') }}</p>
        <a class="fira-mwd__retry" href="{{ route('market-prices.index') }}">{{ __('market.web.try_again') }}</a>
    @else
        <header class="fira-mwd__head">
            <div>
                <h2 class="fira-mwd__title" id="fira-mwd-title">{{ $detail['name'] }}</h2>
                <p class="fira-mwd__sub">
                    {{ __('market.web.source', ['name' => $detail['source']['attribution']]) }}
                    @if($detail['as_of'])
                        · <span class="fira-mw__nowrap">{{ __('market.web.source_time', ['time' => $when($detail['as_of'])]) }}</span>
                    @endif
                </p>
            </div>
            <p class="fira-mw__chip is-{{ $detail['status'] }}"><span class="fira-mw__chip-dot" aria-hidden="true"></span>{{ $detail['status_label'] }}</p>
        </header>

        <p class="fira-mwd__quote is-{{ $detail['direction'] ?? 'none' }}">
            <span class="fira-mwd__price">{{ $fmt($detail['price'], $dec) }}</span>
            <span class="fira-mwd__unit">{{ $detail['unit']['label'] }}</span>
            @if($detail['change'] !== null)
                <span class="fira-mwd__change">{{ $sign($detail['change']) }}{{ $fmt(abs($detail['change']), $dec) }} ({{ $sign($detail['change']) }}{{ $fmt(abs((float) $detail['change_pct']), 2) }}%)</span>
            @endif
        </p>

        <div class="fira-mwd__controls">
            <nav class="fira-mwd__chips" aria-label="{{ __('market.watch.detail.ranges') }}">
                @foreach($detail['ranges'] as $r)
                    <a class="fira-mwd__chipbtn" href="{{ $link(['range' => $r]) }}" data-range="{{ $r }}" @if($r === $detail['range']) aria-current="true" @endif>{{ __('market.web.ranges.' . $r) }}</a>
                @endforeach
            </nav>
            @if(count($detail['units']) > 1)
                <nav class="fira-mwd__chips" aria-label="{{ __('market.watch.detail.unit') }}">
                    @foreach($detail['units'] as $u)
                        <a class="fira-mwd__chipbtn" href="{{ $link(['unit' => $u['key']]) }}" data-unit="{{ $u['key'] }}" @if($u['key'] === $detail['unit']['key']) aria-current="true" @endif>{{ $u['label'] }}</a>
                    @endforeach
                </nav>
            @endif
        </div>

        @if($path)
            @php
                $low = min($values); $high = max($values);
                $summary = __('market.watch.detail.chart', [
                    'name' => $detail['short_name'], 'range' => __('market.web.ranges.' . $detail['range']),
                    'from' => $when($points[0]['t']), 'to' => $when(end($points)['t']),
                    'low' => $fmt($low, $dec), 'high' => $fmt($high, $dec),
                ]);
            @endphp
            <figure class="fira-mwd__chart is-{{ $detail['direction'] ?? 'none' }}" tabindex="0" aria-describedby="fira-mwd-summary"
                data-points='@json(array_map(fn ($p) => [$when($p['t']), $p['v']], $points))' data-ref="{{ $detail['stats']['prev_close'] }}" data-decimals="{{ $dec }}">
                <svg viewBox="0 0 600 220" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                    <defs>
                        <linearGradient id="mwd-fill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="currentColor" stop-opacity="0.26"/>
                            <stop offset="1" stop-color="currentColor" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="{{ $path['area'] }}" fill="url(#mwd-fill)" stroke="none"/>
                    @if($path['ref_y'] !== null)
                        <line class="fira-mw__spark-ref" x1="8" x2="592" y1="{{ $path['ref_y'] }}" y2="{{ $path['ref_y'] }}" vector-effect="non-scaling-stroke"/>
                    @endif
                    <path d="{{ $path['line'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                    <g class="fira-mwd__cross" hidden>
                        <line class="fira-mwd__cross-line" x1="0" x2="0" y1="0" y2="220" vector-effect="non-scaling-stroke"/>
                    </g>
                </svg>
                <span class="fira-mwd__dot" hidden></span>
                <span class="fira-mwd__tip" hidden></span>
                <figcaption class="fira-mw__sr" id="fira-mwd-summary">{{ $summary }}</figcaption>
            </figure>
            <p class="fira-mwd__axis" aria-hidden="true">
                <span>{{ $when($points[0]['t']) }}</span><span>{{ $fmt($low, $dec) }} – {{ $fmt($high, $dec) }}</span><span>{{ $when(end($points)['t']) }}</span>
            </p>
            <p class="fira-mw__sr" aria-live="polite" data-readout></p>
        @else
            <p class="fira-mwd__note">{{ __('market.watch.detail.no_points') }}</p>
        @endif

        @if($detail['fx'])
            <p class="fira-mwd__note">{{ __('market.watch.detail.fx', ['rate' => $fmt($detail['fx']['rate'], 4), 'source' => $detail['fx']['source'], 'date' => \Illuminate\Support\Carbon::parse($detail['fx']['as_of'])->utc()->format('Y-m-d')]) }}</p>
        @endif

        <dl class="fira-mwd__stats" aria-label="{{ __('market.watch.detail.stats') }}">
            @foreach(['prev_close', 'high_52w', 'low_52w', 'avg_20d'] as $stat)
                <div><dt>{{ __('market.watch.detail.' . $stat) }}</dt><dd>{{ $fmt($detail['stats'][$stat], $dec) }}</dd></div>
            @endforeach
        </dl>

        {{-- Actions: watchlist, alerts and CSV for signed-in people; guests sign in and come back here. --}}
        @php
            $exportRange = in_array($detail['range'], ['1M', '3M', '1Y', '5Y'], true) ? $detail['range'] : '1Y';
            $nativeUnit = $detail['units'][0]['label'] ?? $detail['unit']['label'];
        @endphp
        @if(session('success'))
            <p class="fira-mwd__flash" role="status">{{ session('success') }}</p>
        @endif
        <div class="fira-mwd__actions">
            @if($account)
                <form method="POST" action="{{ route('market-prices.watchlist', $detail['key']) }}">
                    @csrf
                    <button type="submit" class="fira-mwd__btn" aria-pressed="{{ $account['watching'] ? 'true' : 'false' }}">
                        {{ $account['watching'] ? __('market.watch.account.watch_remove') : __('market.watch.account.watch_add') }}
                    </button>
                </form>
                <a class="fira-mwd__btn" href="{{ route('market-prices.export', [$detail['key'], 'range' => $exportRange]) }}" download>{{ __('market.watch.account.export') }}</a>
            @else
                <a class="fira-mwd__btn" href="{{ route('market-prices.sign-in', ['market' => $detail['key']]) }}">{{ __('market.watch.account.sign_in') }}</a>
            @endif
            <button type="button" class="fira-mwd__btn" data-fira-ai-prompt="{{ __('market.watch.account.ask_ai_prompt', ['name' => $detail['short_name']]) }}">{{ __('market.watch.account.ask_ai') }}</button>
        </div>

        @if($account)
            <section class="fira-mwd__alerts" aria-labelledby="fira-mwd-alerts-title">
                <h3 class="fira-mwd__subtitle" id="fira-mwd-alerts-title">{{ __('market.watch.account.alert_title') }}</h3>
                @forelse($account['alerts'] as $alert)
                    <div class="fira-mwd__alert">
                        <span>{{ __('market.watch.account.alert_row', ['direction' => __('market.watch.account.' . $alert['direction']), 'threshold' => $fmt($alert['threshold'], $dec), 'unit' => $alert['unit_label']]) }}</span>
                        <form method="POST" action="{{ route('market-prices.alerts.destroy', $alert['id']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="fira-mwd__btn fira-mwd__btn--small">{{ __('market.watch.account.alert_delete') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="fira-mwd__note">{{ __('market.watch.account.alerts_none') }}</p>
                @endforelse
                <form method="POST" action="{{ route('market-prices.alerts.store', $detail['key']) }}" class="fira-mwd__alert-form">
                    @csrf
                    <fieldset>
                        <legend>{{ __('market.watch.account.alert_when') }}</legend>
                        <label><input type="radio" name="direction" value="above" checked> {{ __('market.watch.account.above') }}</label>
                        <label><input type="radio" name="direction" value="below"> {{ __('market.watch.account.below') }}</label>
                    </fieldset>
                    <label class="fira-mwd__field">
                        <span>{{ __('market.watch.account.alert_threshold', ['unit' => $nativeUnit]) }}</span>
                        <input type="number" name="threshold" step="any" min="0" inputmode="decimal" required value="{{ old('threshold') }}">
                    </label>
                    @error('threshold')<p class="fira-mwd__error" role="alert">{{ $message }}</p>@enderror
                    <fieldset>
                        <label><input type="checkbox" name="channels[]" value="push" checked> {{ __('market.watch.account.alert_push') }}</label>
                        <label><input type="checkbox" name="channels[]" value="mail"> {{ __('market.watch.account.alert_mail') }}</label>
                    </fieldset>
                    <button type="submit" class="fira-mwd__btn">{{ __('market.watch.account.alert_save') }}</button>
                </form>
            </section>
        @endif

        @if($points)
            <details class="fira-mwd__table">
                <summary>{{ __('market.watch.detail.table') }}</summary>
                <table>
                    <thead><tr><th scope="col">{{ __('market.watch.detail.date') }}</th><th scope="col">{{ __('market.watch.detail.value') }} ({{ $detail['unit']['label'] }})</th></tr></thead>
                    <tbody>
                        @foreach(array_reverse(array_slice($points, -90)) as $p)
                            <tr><td>{{ $when($p['t']) }}</td><td>{{ $fmt($p['v'], $dec) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @endif
    @endif
    <p class="fira-mwd__disclaimer">{{ __('market.watch.disclaimer') }}</p>
</section>
