{{-- Professional Institutional Coffee Trading Chart Widget --}}
{{-- Powered by TradingView Lightweight Charts with Real-Time Candlesticks (OHLC) --}}

<div class="fira-trading-terminal" id="fira-trading-terminal" data-market="{{ $market ?? 'ethiopia' }}" data-origin="{{ $origin ?? 'composite' }}" data-timeframe="{{ $timeframe ?? '1d' }}">
    <!-- 1. Top Institutional Control Bar: Market Selection -->
    <div class="terminal-market-bar">
        <div class="market-tabs-group" role="tablist" aria-label="Commodity Market Selection">
            <button type="button" class="market-tab-btn {{ ($market ?? 'ethiopia') === 'ethiopia' ? 'active' : '' }}" data-market="ethiopia" id="tab-market-ethiopia">
                <i class="fa-regular fa-chart-line flag-icon" aria-hidden="true"></i>
                <span class="tab-label">Ethiopian Local &amp; Export Market</span>
                <span class="market-source-pill">ECTA / ECX</span>
            </button>
            <button type="button" class="market-tab-btn {{ ($market ?? '') === 'arabica' ? 'active' : '' }}" data-market="arabica" id="tab-market-arabica">
                <i class="fa-regular fa-chart-line flag-icon" aria-hidden="true"></i>
                <span class="tab-label">ICE Arabica "C" Futures</span>
                <span class="market-source-pill">NYBOT (KC)</span>
            </button>
            <button type="button" class="market-tab-btn {{ ($market ?? '') === 'robusta' ? 'active' : '' }}" data-market="robusta" id="tab-market-robusta">
                <i class="fa-regular fa-chart-line flag-icon" aria-hidden="true"></i>
                <span class="tab-label">ICE Robusta Futures</span>
                <span class="market-source-pill">London (RC)</span>
            </button>
        </div>

        <div class="terminal-connection-badge">
            <span class="pulse-dot"></span>
            <span class="connection-label" id="connection-status">FEED CONNECTED</span>
        </div>
    </div>

    <!-- 2. Sub-Origin Selector (Active when Ethiopian Market is selected) -->
    <div class="terminal-origin-strip {{ ($market ?? 'ethiopia') === 'ethiopia' ? '' : 'd-none' }}" id="ethiopia-origin-strip">
        <div class="origin-strip-label">
            <i class="fa-solid fa-layer-group"></i>
            <span>Origin Benchmarks:</span>
        </div>
        <div class="origin-pills-scroll">
            @php
                $originList = [
                    'composite'   => ['name' => 'All Origins (Composite Index)', 'badge' => 'ECTA Index'],
                    'sidama'      => ['name' => 'Sidama Washed G1/G2', 'badge' => 'Washed Specialty'],
                    'yirgacheffe' => ['name' => 'Yirgacheffe Washed G1', 'badge' => 'Floral Crown'],
                    'guji'        => ['name' => 'Guji Natural G1', 'badge' => 'Specialty Natural'],
                    'limu'        => ['name' => 'Limu Washed G2', 'badge' => 'Estate Washed'],
                    'harar'       => ['name' => 'Harrar Longberry G1', 'badge' => 'Sun-Dried Natural'],
                    'jimma'       => ['name' => 'Jimma Commercial G5', 'badge' => 'Commercial Floor'],
                    'kaffa'       => ['name' => 'Kaffa Wild Forest G1', 'badge' => 'Biosphere Reserve'],
                ];
                $activeOrigin = $origin ?? 'composite';
            @endphp

            @foreach($originList as $origKey => $origInfo)
                <button type="button" class="origin-chip {{ $activeOrigin === $origKey ? 'active' : '' }}" data-origin="{{ $origKey }}">
                    <span>{{ $origInfo['name'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- 3. Real-Time Price & Instrument Metrics Strip -->
    <div class="terminal-metrics-panel">
        <div class="metrics-left">
            <div class="instrument-title-row">
                <h3 class="instrument-name" id="instrument-name">{{ $chartData['summary']['name'] ?? 'Ethiopia Coffee Export Benchmark' }}</h3>
                <span class="instrument-symbol" id="instrument-symbol">{{ $chartData['summary']['symbol'] ?? 'ET-COF-COMP' }}</span>
                <span class="instrument-badge" id="instrument-badge">{{ $chartData['summary']['market_status'] ?? 'Official ECTA Fixing' }}</span>
            </div>
            
            <div class="price-quote-row">
                <div class="live-price-wrap">
                    <span class="live-price {{ ($chartData['summary']['is_bullish'] ?? true) ? 'up' : 'down' }}" id="live-price">
                        {{ $chartData['summary']['price_formatted'] ?? '$3.485 / lb' }}
                    </span>
                </div>

                <div class="price-delta-badge {{ ($chartData['summary']['is_bullish'] ?? true) ? 'delta-up' : 'delta-down' }}" id="price-delta-badge">
                    <i class="fa-solid {{ ($chartData['summary']['is_bullish'] ?? true) ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}" id="price-trend-icon"></i>
                    <span id="price-change">{{ $chartData['summary']['change_formatted'] ?? '+0.045' }}</span>
                    <span id="price-change-percent">({{ $chartData['summary']['change_percent_formatted'] ?? '+1.31%' }})</span>
                </div>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-cell">
                <span class="cell-label">24h High</span>
                <span class="cell-value" id="val-24h-high">{{ number_format($chartData['summary']['high_24h'] ?? 0, 2) }}</span>
            </div>
            <div class="metric-cell">
                <span class="cell-label">24h Low</span>
                <span class="cell-value" id="val-24h-low">{{ number_format($chartData['summary']['low_24h'] ?? 0, 2) }}</span>
            </div>
            <div class="metric-cell">
                <span class="cell-label">24h Volume</span>
                <span class="cell-value" id="val-24h-volume">{{ number_format($chartData['summary']['volume_24h'] ?? 0) }}</span>
            </div>
            @if(!empty($chartData['summary']['secondary_metric']))
                <div class="metric-cell highlight-cell">
                    <span class="cell-label" id="sec-metric-label">{{ $chartData['summary']['secondary_metric']['label'] }}</span>
                    <span class="cell-value text-accent" id="sec-metric-val">{{ $chartData['summary']['secondary_metric']['value'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- 4. Chart Toolbar: Timeframes, Indicators, Fullscreen -->
    <div class="terminal-toolbar">
        <div class="timeframe-buttons" role="group" aria-label="Chart Timeframes">
            @php $tfs = ['1m' => '1m', '5m' => '5m', '15m' => '15m', '1h' => '1H', '4h' => '4H', '1d' => '1D', '1w' => '1W', '1m_month' => '1M']; @endphp
            @foreach($tfs as $tfKey => $tfLabel)
                <button type="button" class="tf-btn {{ ($timeframe ?? '1d') === $tfKey ? 'active' : '' }}" data-timeframe="{{ $tfKey }}">
                    {{ $tfLabel }}
                </button>
            @endforeach
        </div>

        <div class="toolbar-actions">
            <!-- Chart Style Switcher -->
            <div class="toolbar-action-group">
                <button type="button" class="tool-btn active" id="btn-chart-style-candle" title="Candlestick Chart">
                    <i class="fa-solid fa-chart-simple"></i>
                    <span>Candles</span>
                </button>
                <button type="button" class="tool-btn" id="btn-chart-style-line" title="Line Chart">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Line</span>
                </button>
                <button type="button" class="tool-btn" id="btn-chart-style-area" title="Area Chart">
                    <i class="fa-solid fa-chart-area"></i>
                    <span>Area</span>
                </button>
            </div>

            <!-- Volume Toggle -->
            <button type="button" class="tool-btn active" id="btn-toggle-volume" title="Toggle Volume Histogram">
                <i class="fa-solid fa-chart-column"></i>
                <span>Vol</span>
            </button>

            <!-- Reset View -->
            <button type="button" class="tool-btn" id="btn-chart-fit" title="Reset Zoom / Fit Content">
                <i class="fa-solid fa-expand"></i>
                <span>Fit</span>
            </button>

            <!-- Fullscreen -->
            <button type="button" class="tool-btn" id="btn-chart-fullscreen" title="Toggle Fullscreen">
                <i class="fa-solid fa-maximize"></i>
            </button>
        </div>
    </div>

    <!-- 5. Chart Viewport Container -->
    <div class="chart-viewport-wrapper">
        <!-- Floating Crosshair OHLC Legend -->
        <div class="chart-crosshair-legend" id="chart-legend">
            <span class="legend-symbol" id="legend-symbol">{{ $chartData['summary']['symbol'] ?? 'KC=F' }}</span>
            <span class="legend-item"><span class="legend-key">O:</span> <span class="legend-val" id="legend-o">--</span></span>
            <span class="legend-item"><span class="legend-key">H:</span> <span class="legend-val text-up" id="legend-h">--</span></span>
            <span class="legend-item"><span class="legend-key">L:</span> <span class="legend-val text-down" id="legend-l">--</span></span>
            <span class="legend-item"><span class="legend-key">C:</span> <span class="legend-val" id="legend-c">--</span></span>
            <span class="legend-item"><span class="legend-key">Vol:</span> <span class="legend-val text-vol" id="legend-v">--</span></span>
            <span class="legend-item"><span class="legend-key">Chg:</span> <span class="legend-val" id="legend-chg">--</span></span>
        </div>

        <!-- The Canvas Container -->
        <div class="trading-chart-canvas" id="trading-chart-container">
            <div class="chart-loading-overlay" id="chart-loading" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
                <span class="mt-2 text-muted" style="font-size: 12px;">Querying market candle sequence...</span>
            </div>
        </div>
    </div>

    <!-- 6. Institutional Bottom Footnote & Data Classification -->
    <div class="terminal-footer-strip">
        <div class="footer-source-note">
            <i class="fa-solid fa-shield-halved text-success"></i>
            <span><strong>Verified Data:</strong> <span id="footer-source">{{ $chartData['summary']['data_source'] ?? 'Intercontinental Exchange / ECTA' }}</span></span>
            <span class="sep">•</span>
            <span class="text-muted"><span id="footer-classification">{{ $chartData['summary']['data_classification'] ?? 'Exchange Traded Futures' }}</span></span>
            <span class="sep">•</span>
            <span class="text-muted"><span id="footer-session">{{ $chartData['summary']['session'] ?? 'Daily Regular Session' }}</span></span>
        </div>

        <div class="footer-actions">
            <a href="{{ route('marketplace.index') }}" class="terminal-action-link" id="action-marketplace-link">
                <i class="fa-solid fa-store"></i>
                <span>Trade in B2B Marketplace</span>
            </a>
            <a href="{{ route('free-tools.index') }}" class="terminal-action-link">
                <i class="fa-solid fa-calculator"></i>
                <span>Calculate Export Margin</span>
            </a>
        </div>
    </div>
</div>

{{-- Pass Initial Server Data directly into JavaScript to guarantee Instant First Paint --}}
<script>
    window.INITIAL_TRADING_PAYLOAD = @json($chartData ?? null);
</script>

<style>
/* ==========================================================================
   Fira Link Institutional Coffee Trading Terminal Styles
   Brand Identity: Purple #6D1A95, Dark #14101E, Canvas #0E0C15, Green #10B981, Red #EF4444
   ========================================================================== */
.fira-trading-terminal {
    background: #0E0C15;
    border: 1px solid #231C34;
    border-radius: 20px;
    box-shadow: 0 16px 40px -8px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(109, 26, 149, 0.15);
    color: #F3F4F6;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    overflow: hidden;
    margin-bottom: 24px;
    position: relative;
}

/* 1. Market Selection Bar */
.terminal-market-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #151121;
    border-bottom: 1px solid #231C34;
    padding: 10px 16px;
    gap: 12px;
    flex-wrap: wrap;
}

.market-tabs-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.market-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #1E172E;
    border: 1px solid #2E2346;
    color: #9CA3AF;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.market-tab-btn:hover {
    background: #271E3C;
    color: #FFFFFF;
    border-color: #4A1066;
}

.market-tab-btn.active {
    background: linear-gradient(135deg, #4A1066 0%, #6D1A95 100%);
    border-color: #8B2FC9;
    color: #FFFFFF;
    box-shadow: 0 4px 14px rgba(109, 26, 149, 0.35);
}

.market-source-pill {
    background: rgba(255, 255, 255, 0.12);
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 10px;
    font-family: 'JetBrains Mono', 'Roboto Mono', monospace;
    letter-spacing: 0.3px;
}

.terminal-connection-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.25);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10.5px;
    font-family: 'JetBrains Mono', monospace;
    color: #10B981;
    font-weight: 600;
}

.pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 8px #10B981;
    animation: pulseGlow 1.8s infinite;
}

@keyframes pulseGlow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
}

/* 2. Sub-Origin Selector Strip */
.terminal-origin-strip {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #120E1C;
    border-bottom: 1px solid #231C34;
    padding: 8px 16px;
    overflow-x: auto;
}

.origin-strip-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #9CA3AF;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.origin-pills-scroll {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.origin-chip {
    background: #1A1429;
    border: 1px solid #281F3E;
    color: #D1D5DB;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.origin-chip:hover {
    background: #251B3B;
    border-color: #6D1A95;
    color: #FFFFFF;
}

.origin-chip.active {
    background: #6D1A95;
    border-color: #9333EA;
    color: #FFFFFF;
    font-weight: 600;
}

/* 3. Metrics Panel */
.terminal-metrics-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #100C19;
    border-bottom: 1px solid #231C34;
    gap: 20px;
    flex-wrap: wrap;
}

.instrument-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}

.instrument-name {
    font-size: 18px;
    font-weight: 800;
    margin: 0;
    color: #FFFFFF;
    letter-spacing: -0.3px;
}

.instrument-symbol {
    font-family: 'JetBrains Mono', 'Roboto Mono', monospace;
    font-size: 12px;
    font-weight: 700;
    color: #A855F7;
    background: rgba(168, 85, 247, 0.12);
    border: 1px solid rgba(168, 85, 247, 0.25);
    padding: 2px 7px;
    border-radius: 6px;
}

.instrument-badge {
    font-size: 10.5px;
    font-weight: 600;
    color: #10B981;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.25);
    padding: 2px 7px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.price-quote-row {
    display: flex;
    align-items: baseline;
    gap: 12px;
}

.live-price {
    font-size: 30px;
    font-weight: 900;
    font-family: 'JetBrains Mono', 'Roboto Mono', monospace;
    letter-spacing: -0.5px;
    transition: color 0.3s ease;
}

.live-price.up { color: #10B981; }
.live-price.down { color: #EF4444; }

.price-delta-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
    padding: 3px 8px;
    border-radius: 8px;
}

.delta-up {
    color: #10B981;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.delta-down {
    color: #EF4444;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.25);
}

.metrics-grid {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.metric-cell {
    background: #171224;
    border: 1px solid #281F3C;
    padding: 8px 14px;
    border-radius: 10px;
    min-width: 90px;
}

.highlight-cell {
    border-color: rgba(109, 26, 149, 0.4);
    background: linear-gradient(135deg, rgba(109, 26, 149, 0.15) 0%, rgba(20, 16, 30, 0.6) 100%);
}

.cell-label {
    display: block;
    font-size: 10.5px;
    font-weight: 600;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 2px;
}

.cell-value {
    font-size: 13.5px;
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
    color: #FFFFFF;
}

.text-accent {
    color: #D8B4FE !important;
}

/* 4. Toolbar */
.terminal-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #120E1C;
    border-bottom: 1px solid #231C34;
    padding: 8px 16px;
    gap: 12px;
    flex-wrap: wrap;
}

.timeframe-buttons {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #191427;
    padding: 3px;
    border-radius: 10px;
    border: 1px solid #2A2140;
}

.tf-btn {
    background: transparent;
    border: none;
    color: #9CA3AF;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 7px;
    cursor: pointer;
    font-family: 'JetBrains Mono', monospace;
    transition: all 0.15s ease;
}

.tf-btn:hover {
    color: #FFFFFF;
    background: #231C34;
}

.tf-btn.active {
    background: #6D1A95;
    color: #FFFFFF;
    font-weight: 700;
}

.toolbar-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.toolbar-action-group {
    display: flex;
    align-items: center;
    gap: 2px;
    background: #191427;
    padding: 3px;
    border-radius: 8px;
    border: 1px solid #2A2140;
}

.tool-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: transparent;
    border: none;
    color: #9CA3AF;
    font-size: 11.5px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.tool-btn:hover {
    color: #FFFFFF;
    background: #231C34;
}

.tool-btn.active {
    background: rgba(109, 26, 149, 0.4);
    color: #D8B4FE;
    font-weight: 600;
}

/* 5. Chart Viewport */
.chart-viewport-wrapper {
    position: relative;
    width: 100%;
    height: 480px;
    background: #0E0C15;
}

.trading-chart-canvas {
    width: 100%;
    height: 100%;
    position: relative;
}

.chart-crosshair-legend {
    position: absolute;
    top: 12px;
    left: 16px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    font-family: 'JetBrains Mono', monospace;
    background: rgba(14, 12, 21, 0.85);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 5px 12px;
    border-radius: 8px;
    pointer-events: none;
}

.legend-symbol {
    font-weight: 800;
    color: #A855F7;
}

.legend-key {
    color: #6B7280;
    margin-right: 2px;
}

.legend-val {
    color: #F3F4F6;
    font-weight: 600;
}

.text-up { color: #10B981 !important; }
.text-down { color: #EF4444 !important; }
.text-vol { color: #60A5FA !important; }

.chart-loading-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(14, 12, 21, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 20;
}

/* 6. Footer Strip */
.terminal-footer-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 18px;
    background: #120E1C;
    border-top: 1px solid #231C34;
    font-size: 11.5px;
    color: #9CA3AF;
    gap: 12px;
    flex-wrap: wrap;
}

.footer-source-note {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.footer-source-note .sep {
    color: #4B5563;
}

.footer-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.terminal-action-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #C084FC;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.15s ease;
}

.terminal-action-link:hover {
    color: #FFFFFF;
    text-decoration: underline;
}

/* Mobile Adjustments */
@media (max-width: 768px) {
    .chart-viewport-wrapper {
        height: 380px;
    }
    .live-price {
        font-size: 24px;
    }
    .metrics-grid {
        width: 100%;
        justify-content: space-between;
    }
    .metric-cell {
        flex: 1 1 calc(50% - 8px);
        min-width: unset;
    }
    .terminal-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    .toolbar-actions {
        justify-content: space-between;
        overflow-x: auto;
    }
}
</style>

{{-- TradingView Lightweight Charts Library Script --}}
<script src="{{ asset('assets/front/js/lightweight-charts.standalone.production.js') }}"></script>

<script>
(function() {
    'use strict';

    // Terminal State
    let currentMarket = '{{ $market ?? "ethiopia" }}';
    let currentOrigin = '{{ $origin ?? "composite" }}';
    let currentTimeframe = '{{ $timeframe ?? "1d" }}';
    let currentChartStyle = 'candle'; // 'candle', 'line', 'area'
    let showVolume = true;

    let chart = null;
    let mainSeries = null;
    let volumeSeries = null;
    let currentCandles = [];
    let currentVolumeData = [];
    let tickTimer = null;
    let isUpdating = false;

    const chartContainer = document.getElementById('trading-chart-container');
    const loadingOverlay = document.getElementById('chart-loading');

    // DOM Elements
    const elLivePrice = document.getElementById('live-price');
    const elPriceDeltaBadge = document.getElementById('price-delta-badge');
    const elPriceChange = document.getElementById('price-change');
    const elPriceChangePct = document.getElementById('price-change-percent');
    const elTrendIcon = document.getElementById('price-trend-icon');
    const elInstrumentName = document.getElementById('instrument-name');
    const elInstrumentSymbol = document.getElementById('instrument-symbol');
    const elInstrumentBadge = document.getElementById('instrument-badge');
    const elHigh24h = document.getElementById('val-24h-high');
    const elLow24h = document.getElementById('val-24h-low');
    const elVol24h = document.getElementById('val-24h-volume');
    const elFooterSource = document.getElementById('footer-source');
    const elFooterClass = document.getElementById('footer-classification');
    const elFooterSession = document.getElementById('footer-session');
    const elLegendO = document.getElementById('legend-o');
    const elLegendH = document.getElementById('legend-h');
    const elLegendL = document.getElementById('legend-l');
    const elLegendC = document.getElementById('legend-c');
    const elLegendV = document.getElementById('legend-v');
    const elLegendChg = document.getElementById('legend-chg');
    const elLegendSym = document.getElementById('legend-symbol');

    // Initialize Chart
    function initChart() {
        if (!chartContainer || typeof LightweightCharts === 'undefined') return;

        // Dispose previous if any
        if (chart) {
            chart.remove();
            chart = null;
        }

        const width = chartContainer.clientWidth || 800;
        const height = chartContainer.clientHeight || 480;

        chart = LightweightCharts.createChart(chartContainer, {
            width: width,
            height: height,
            layout: {
                background: { type: 'solid', color: '#FFFFFF' },
                textColor: '#737373',
                fontSize: 11,
                fontFamily: "'Inter', sans-serif",
            },
            grid: {
                vertLines: { color: '#F5F5F5' },
                horzLines: { color: '#F5F5F5' },
            },
            crosshair: {
                mode: 1, // CrosshairMode.Normal
                vertLine: {
                    color: '#6D1A95',
                    width: 1,
                    style: 3, // LineStyle.Dashed
                    labelBackgroundColor: '#6D1A95',
                },
                horzLine: {
                    color: '#6D1A95',
                    width: 1,
                    style: 3,
                    labelBackgroundColor: '#6D1A95',
                },
            },
            rightPriceScale: {
                borderColor: '#D4D4D4',
                scaleMargins: {
                    top: 0.1,
                    bottom: 0.22,
                },
            },
            timeScale: {
                borderColor: '#D4D4D4',
                timeVisible: true,
                secondsVisible: currentTimeframe === '1m' || currentTimeframe === '5m',
            },
            handleScroll: {
                mouseWheel: true,
                pressedMouseMove: true,
                horzTouchDrag: true,
                vertTouchDrag: true,
            },
            handleScale: {
                axisPressedMouseMove: true,
                mouseWheel: true,
                pinch: true,
            },
        });

        // Add Volume Histogram Series first (so candles sit on top)
        if (typeof chart.addHistogramSeries === 'function') {
            volumeSeries = chart.addHistogramSeries({
                priceFormat: { type: 'volume' },
                priceScaleId: '', // Overlay scale
            });
        } else if (typeof chart.addSeries === 'function' && LightweightCharts.HistogramSeries) {
            volumeSeries = chart.addSeries(LightweightCharts.HistogramSeries, {
                priceFormat: { type: 'volume' },
                priceScaleId: '',
            });
        }

        if (volumeSeries && typeof volumeSeries.priceScale === 'function') {
            volumeSeries.priceScale().applyOptions({
                scaleMargins: {
                    top: 0.78, // Volume sits at bottom 22%
                    bottom: 0,
                },
            });
        }

        // Add Main Price Series based on current style
        createMainSeries();

        // Crosshair move subscription for floating legend
        chart.subscribeCrosshairMove(param => {
            if (!param || !param.time) {
                updateLegendWithLatest();
                return;
            }

            let candle = null;
            if (param.seriesData && mainSeries) {
                candle = param.seriesData.get(mainSeries);
            }

            let vol = null;
            if (param.seriesData && volumeSeries) {
                vol = param.seriesData.get(volumeSeries);
            }

            if (candle) {
                const open = candle.open !== undefined ? candle.open : candle.value;
                const close = candle.close !== undefined ? candle.close : candle.value;
                const high = candle.high !== undefined ? candle.high : candle.value;
                const low = candle.low !== undefined ? candle.low : candle.value;
                const chg = close - open;
                const chgPct = open > 0 ? (chg / open) * 100 : 0;

                elLegendO.textContent = open.toFixed(2);
                elLegendH.textContent = high.toFixed(2);
                elLegendL.textContent = low.toFixed(2);
                elLegendC.textContent = close.toFixed(2);
                elLegendC.className = 'legend-val ' + (close >= open ? 'text-up' : 'text-down');

                if (vol) {
                    elLegendV.textContent = vol.value ? vol.value.toLocaleString() : '--';
                }

                elLegendChg.textContent = (chg >= 0 ? '+' : '') + chg.toFixed(2) + ' (' + (chgPct >= 0 ? '+' : '') + chgPct.toFixed(2) + '%)';
                elLegendChg.className = 'legend-val ' + (chg >= 0 ? 'text-up' : 'text-down');
            }
        });

        // Resize observer
        const resizeObserver = new ResizeObserver(entries => {
            if (!chart || entries.length === 0 || !entries[0].contentRect) return;
            const newRect = entries[0].contentRect;
            chart.applyOptions({ width: newRect.width, height: newRect.height });
        });
        resizeObserver.observe(chartContainer);
    }

    // Create Main Series (Candle / Line / Area)
    function createMainSeries() {
        if (!chart) return;

        if (mainSeries) {
            chart.removeSeries(mainSeries);
            mainSeries = null;
        }

        if (currentChartStyle === 'candle') {
            const candleOpts = {
                upColor: '#6D1A95',
                downColor: '#51126F',
                borderVisible: true,
                borderUpColor: '#6D1A95',
                borderDownColor: '#51126F',
                wickVisible: true,
                wickUpColor: '#6D1A95',
                wickDownColor: '#51126F',
            };
            if (typeof chart.addCandlestickSeries === 'function') {
                mainSeries = chart.addCandlestickSeries(candleOpts);
            } else if (typeof chart.addSeries === 'function' && LightweightCharts.CandlestickSeries) {
                mainSeries = chart.addSeries(LightweightCharts.CandlestickSeries, candleOpts);
            }
        } else if (currentChartStyle === 'line') {
            const lineOpts = {
                color: '#6D1A95',
                lineWidth: 2,
                crosshairMarkerVisible: true,
            };
            if (typeof chart.addLineSeries === 'function') {
                mainSeries = chart.addLineSeries(lineOpts);
            } else if (typeof chart.addSeries === 'function' && LightweightCharts.LineSeries) {
                mainSeries = chart.addSeries(LightweightCharts.LineSeries, lineOpts);
            }
        } else if (currentChartStyle === 'area') {
            const areaOpts = {
                topColor: 'rgba(109, 26, 149, 0.45)',
                bottomColor: 'rgba(109, 26, 149, 0.02)',
                lineColor: '#6D1A95',
                lineWidth: 2,
            };
            if (typeof chart.addAreaSeries === 'function') {
                mainSeries = chart.addAreaSeries(areaOpts);
            } else if (typeof chart.addSeries === 'function' && LightweightCharts.AreaSeries) {
                mainSeries = chart.addSeries(LightweightCharts.AreaSeries, areaOpts);
            }
        }

        applyDataToSeries();
    }

    // Apply data to series
    function applyDataToSeries() {
        if (!mainSeries || !currentCandles || currentCandles.length === 0) return;

        if (currentChartStyle === 'candle') {
            mainSeries.setData(currentCandles);
        } else {
            // Map candles to line/area points { time, value: close }
            const linePoints = currentCandles.map(c => ({
                time: c.time,
                value: c.close,
            }));
            mainSeries.setData(linePoints);
        }

        if (volumeSeries && currentVolumeData && showVolume) {
            volumeSeries.setData(currentVolumeData);
        } else if (volumeSeries) {
            volumeSeries.setData([]);
        }

        chart.timeScale().fitContent();
        updateLegendWithLatest();
    }

    // Update legend with latest candle
    function updateLegendWithLatest() {
        if (!currentCandles || currentCandles.length === 0) return;
        const last = currentCandles[currentCandles.length - 1];
        const lastVol = currentVolumeData && currentVolumeData.length > 0 ? currentVolumeData[currentVolumeData.length - 1] : null;

        const open = last.open;
        const close = last.close;
        const high = last.high;
        const low = last.low;
        const chg = close - open;
        const chgPct = open > 0 ? (chg / open) * 100 : 0;

        elLegendO.textContent = open.toFixed(2);
        elLegendH.textContent = high.toFixed(2);
        elLegendL.textContent = low.toFixed(2);
        elLegendC.textContent = close.toFixed(2);
        elLegendC.className = 'legend-val ' + (close >= open ? 'text-up' : 'text-down');

        if (lastVol) {
            elLegendV.textContent = lastVol.value ? lastVol.value.toLocaleString() : '--';
        }

        elLegendChg.textContent = (chg >= 0 ? '+' : '') + chg.toFixed(2) + ' (' + (chgPct >= 0 ? '+' : '') + chgPct.toFixed(2) + '%)';
        elLegendChg.className = 'legend-val ' + (chg >= 0 ? 'text-up' : 'text-down');
    }

    // Fetch Full Chart Data via AJAX
    async function loadChartData(showSpinner = true) {
        if (isUpdating) return;
        isUpdating = true;
        if (showSpinner && loadingOverlay) loadingOverlay.style.display = 'flex';

        try {
            const url = `{{ route('market-prices.chart-data') }}?market=${encodeURIComponent(currentMarket)}&timeframe=${encodeURIComponent(currentTimeframe)}&origin=${encodeURIComponent(currentOrigin)}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                renderPayload(data);
            }
        } catch (err) {
            console.error('Failed to load chart data:', err);
        } finally {
            isUpdating = false;
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        }
    }

    // Render payload received from server or initial data
    function renderPayload(data) {
        currentCandles = data.candles || [];
        currentVolumeData = data.volume || [];

        // Update Summary DOM
        const sum = data.summary || {};
        elInstrumentName.textContent = sum.name || 'Coffee Benchmark';
        elInstrumentSymbol.textContent = sum.symbol || 'KC=F';
        elInstrumentBadge.textContent = sum.market_status || 'Market Active';
        elLegendSym.textContent = sum.symbol || 'KC=F';

        elLivePrice.textContent = sum.price_formatted || '$0.00';
        elLivePrice.className = 'live-price ' + (sum.is_bullish ? 'up' : 'down');

        elPriceDeltaBadge.className = 'price-delta-badge ' + (sum.is_bullish ? 'delta-up' : 'delta-down');
        elPriceChange.textContent = sum.change_formatted || '0.00';
        elPriceChangePct.textContent = `(${sum.change_percent_formatted || '0.00%'})`;

        elTrendIcon.className = 'fa-solid ' + (sum.is_bullish ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down');

        if (elHigh24h) elHigh24h.textContent = Number(sum.high_24h || 0).toLocaleString();
        if (elLow24h) elLow24h.textContent = Number(sum.low_24h || 0).toLocaleString();
        if (elVol24h) elVol24h.textContent = Number(sum.volume_24h || 0).toLocaleString();

        if (elFooterSource) elFooterSource.textContent = sum.data_source || '';
        if (elFooterClass) elFooterClass.textContent = sum.data_classification || '';
        if (elFooterSession) elFooterSession.textContent = sum.session || '';

        // Update B2B trade link origin if applicable
        const tradeLink = document.getElementById('action-marketplace-link');
        if (tradeLink) {
            if (currentMarket === 'ethiopia' && currentOrigin !== 'composite') {
                tradeLink.href = `{{ route('marketplace.index') }}?origin=${encodeURIComponent(currentOrigin)}`;
            } else {
                tradeLink.href = `{{ route('marketplace.index') }}`;
            }
        }

        applyDataToSeries();
    }

    // Live Tick Streaming update (every 2 seconds)
    async function startLiveTickStreaming() {
        if (tickTimer) clearInterval(tickTimer);

        tickTimer = setInterval(async () => {
            // Only poll if document is visible
            if (document.hidden) return;

            try {
                const url = `{{ route('market-prices.live-tick') }}?market=${encodeURIComponent(currentMarket)}&origin=${encodeURIComponent(currentOrigin)}`;
                const res = await fetch(url);
                const tick = await res.json();

                if (tick.success && mainSeries && currentCandles.length > 0) {
                    const lastIdx = currentCandles.length - 1;
                    const lastCandle = currentCandles[lastIdx];
                    const prevPrice = lastCandle.close;
                    const newPrice = tick.price;

                    // Update in-memory candle
                    lastCandle.close = newPrice;
                    lastCandle.high = Math.max(lastCandle.high, newPrice);
                    lastCandle.low = Math.min(lastCandle.low, newPrice);

                    // Push update directly to active series
                    if (currentChartStyle === 'candle') {
                        mainSeries.update(lastCandle);
                    } else {
                        mainSeries.update({
                            time: lastCandle.time,
                            value: newPrice,
                        });
                    }

                    // Price Flash animation on quote header
                    elLivePrice.textContent = tick.price_formatted;
                    if (newPrice > prevPrice) {
                        elLivePrice.style.color = '#6D1A95';
                        elLivePrice.style.textShadow = '0 0 12px rgba(16, 185, 129, 0.6)';
                    } else if (newPrice < prevPrice) {
                        elLivePrice.style.color = '#51126F';
                        elLivePrice.style.textShadow = '0 0 12px rgba(239, 68, 68, 0.6)';
                    }
                    setTimeout(() => {
                        elLivePrice.style.textShadow = 'none';
                    }, 400);

                    updateLegendWithLatest();
                }
            } catch (_) {
                // Silently skip if offline
            }
        }, 2000);
    }

    // Bind UI Event Listeners
    function setupEventListeners() {
        // Market Switcher Tabs
        document.querySelectorAll('.market-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetMarket = this.dataset.market;
                if (targetMarket === currentMarket) return;

                document.querySelectorAll('.market-tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                currentMarket = targetMarket;

                // Show/hide Ethiopian Origin Strip
                const originStrip = document.getElementById('ethiopia-origin-strip');
                if (originStrip) {
                    if (currentMarket === 'ethiopia') {
                        originStrip.classList.remove('d-none');
                    } else {
                        originStrip.classList.add('d-none');
                    }
                }

                loadChartData(true);
            });
        });

        // Origin Chips (when in Ethiopian Market)
        document.querySelectorAll('.origin-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                const targetOrigin = this.dataset.origin;
                if (targetOrigin === currentOrigin) return;

                document.querySelectorAll('.origin-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                currentOrigin = targetOrigin;
                loadChartData(true);
            });
        });

        // Timeframe Buttons
        document.querySelectorAll('.tf-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTf = this.dataset.timeframe;
                if (targetTf === currentTimeframe) return;

                document.querySelectorAll('.tf-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                currentTimeframe = targetTf;

                // Update timeScale options (seconds visible for 1m / 5m)
                if (chart) {
                    chart.applyOptions({
                        timeScale: {
                            secondsVisible: currentTimeframe === '1m' || currentTimeframe === '5m',
                        },
                    });
                }

                loadChartData(true);
            });
        });

        // Chart Style Buttons
        const btnCandle = document.getElementById('btn-chart-style-candle');
        const btnLine = document.getElementById('btn-chart-style-line');
        const btnArea = document.getElementById('btn-chart-style-area');

        [btnCandle, btnLine, btnArea].forEach(btn => {
            if (!btn) return;
            btn.addEventListener('click', function() {
                [btnCandle, btnLine, btnArea].forEach(b => b?.classList.remove('active'));
                this.classList.add('active');

                if (this === btnCandle) currentChartStyle = 'candle';
                else if (this === btnLine) currentChartStyle = 'line';
                else if (this === btnArea) currentChartStyle = 'area';

                createMainSeries();
            });
        });

        // Toggle Volume
        const btnVol = document.getElementById('btn-toggle-volume');
        if (btnVol) {
            btnVol.addEventListener('click', function() {
                showVolume = !showVolume;
                this.classList.toggle('active', showVolume);
                if (volumeSeries && currentVolumeData) {
                    volumeSeries.setData(showVolume ? currentVolumeData : []);
                }
            });
        }

        // Fit / Reset Zoom
        const btnFit = document.getElementById('btn-chart-fit');
        if (btnFit) {
            btnFit.addEventListener('click', function() {
                if (chart) chart.timeScale().fitContent();
            });
        }

        // Fullscreen Toggle
        const btnFs = document.getElementById('btn-chart-fullscreen');
        const terminalEl = document.getElementById('fira-trading-terminal');
        if (btnFs && terminalEl) {
            btnFs.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    terminalEl.requestFullscreen().catch(err => console.error(err));
                    btnFs.innerHTML = '<i class="fa-solid fa-compress"></i>';
                } else {
                    document.exitFullscreen().catch(err => console.error(err));
                    btnFs.innerHTML = '<i class="fa-solid fa-maximize"></i>';
                }
            });

            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement) {
                    btnFs.innerHTML = '<i class="fa-solid fa-maximize"></i>';
                }
            });
        }
    }

    // Kickoff on DOM Ready
    function init() {
        initChart();
        setupEventListeners();

        // Render Initial Payload if passed from server for zero latency
        if (window.INITIAL_TRADING_PAYLOAD && window.INITIAL_TRADING_PAYLOAD.success) {
            renderPayload(window.INITIAL_TRADING_PAYLOAD);
        } else {
            loadChartData(true);
        }

        // Start 2s Live Tick updates
        startLiveTickStreaming();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
