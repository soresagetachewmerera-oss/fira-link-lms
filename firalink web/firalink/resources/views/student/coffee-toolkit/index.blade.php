@extends('layouts.student')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('student.partials.sidebar')

            <div class="dashboard-main">
                <!-- Welcome Banner -->
                <div class="p-4 p-md-5 mb-4 text-white rounded-4 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, #4A0E66 0%, #6D1A95 60%, #1a0524 100%); box-shadow: 0 10px 30px rgba(109,26,149,0.18);">
                    <div class="position-relative" style="z-index: 2;">
                        <span class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(255,255,255,0.2); font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-mug-hot me-1"></i> FIRA LINK TRADE SUITE
                        </span>
                        <h2 class="text-white mb-2" style="font-size: 28px; font-weight: 800;">Coffee Export Toolkit</h2>
                        <p class="text-white-50 mb-4" style="max-width: 650px; font-size: 15px; line-height: 1.6;">
                            Professional commercial tools and trade intelligence for Ethiopian coffee exporters, outgrowers, and trade professionals — powered by validated business calculations and Fira AI.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('student.coffee-toolkit.calculator') }}" class="btn px-4 py-2 text-white fw-semibold rounded-pill" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fa-solid fa-calculator me-1"></i> Profit Calculator
                            </a>
                            <a href="{{ route('student.coffee-toolkit.offer-generator') }}" class="btn px-4 py-2 text-white fw-semibold rounded-pill" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fa-solid fa-file-contract me-1"></i> Offer Generator
                            </a>
                            <a href="{{ route('student.coffee-toolkit.readiness') }}" class="btn px-4 py-2 text-white fw-semibold rounded-pill" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fa-solid fa-shield-halved me-1"></i> Readiness Check
                            </a>
                            <a href="{{ route('student.ai-chat.index') }}" class="btn px-4 py-2 fw-semibold rounded-pill" style="background: #ffffff; color: #6D1A95;">
                                <i class="fa-solid fa-robot me-1"></i> Ask Fira AI
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 4 Core Tool Cards -->
                <div class="row g-4 mb-4">
                    <!-- Calculator Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-decoration-none" style="background: #ffffff; transition: transform 0.2s;">
                            <div class="icon-box mb-3 rounded-3 d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                <i class="fa-solid fa-calculator" style="font-size: 22px;"></i>
                            </div>
                            <h5 class="fw-bold mb-2" style="font-size: 17px;">Profit Calculator</h5>
                            <p class="text-muted small mb-3">Model full container lots (320 bags), corridor logistics, ETB/USD margins, and break-even FOB prices.</p>
                            <a href="{{ route('student.coffee-toolkit.calculator') }}" class="mt-auto fw-bold text-decoration-none small" style="color: #6D1A95;">
                                Launch Calculator <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Offer Sheet Generator -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-decoration-none" style="background: #ffffff; transition: transform 0.2s;">
                            <div class="icon-box mb-3 rounded-3 d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                <i class="fa-solid fa-file-signature" style="font-size: 22px;"></i>
                            </div>
                            <h5 class="fw-bold mb-2" style="font-size: 17px;">Offer Generator</h5>
                            <p class="text-muted small mb-3">Generate official Ethiopian coffee offer sheets with region specs, cupping scores, and verified PDF export.</p>
                            <a href="{{ route('student.coffee-toolkit.offer-generator') }}" class="mt-auto fw-bold text-decoration-none small" style="color: #6D1A95;">
                                Create Offer <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Readiness Assessment -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-decoration-none" style="background: #ffffff; transition: transform 0.2s;">
                            <div class="icon-box mb-3 rounded-3 d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                <i class="fa-solid fa-chart-pie" style="font-size: 22px;"></i>
                            </div>
                            <h5 class="fw-bold mb-2" style="font-size: 17px;">Export Readiness</h5>
                            <p class="text-muted small mb-3">Audit your readiness across licensing (ECTA/NBE), sourcing, cupping quality, and logistics.</p>
                            <a href="{{ route('student.coffee-toolkit.readiness') }}" class="mt-auto fw-bold text-decoration-none small" style="color: #6D1A95;">
                                Take Assessment <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Market Intelligence -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-decoration-none" style="background: #ffffff; transition: transform 0.2s;">
                            <div class="icon-box mb-3 rounded-3 d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                <i class="fa-solid fa-arrow-trend-up" style="font-size: 22px;"></i>
                            </div>
                            <h5 class="fw-bold mb-2" style="font-size: 17px;">Market Intelligence</h5>
                            <p class="text-muted small mb-3">Track ICE Arabica C-market differentials, indicative FOB prices, and ECX reference floor rates.</p>
                            <a href="{{ route('student.coffee-toolkit.market-intelligence') }}" class="mt-auto fw-bold text-decoration-none small" style="color: #6D1A95;">
                                View Benchmarks <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Two Column Section: Market Benchmarks & Fira AI Quick Guidance -->
                <div class="row g-4 mb-4">
                    <!-- Left: Verified Market Benchmarks -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1" style="font-size: 18px;">Ethiopian Coffee Differentials & Benchmark</h5>
                                    <p class="text-muted small mb-0">ECTA & ICE Arabica C Index reference differentials</p>
                                </div>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                                    <i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> Live Benchmark
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-muted">
                                        <tr>
                                            <th>ORIGIN / GRADE</th>
                                            <th>DIFF. (C/LB)</th>
                                            <th>EST. FOB ($/LB)</th>
                                            <th>TREND</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @forelse($marketBenchmarks as $item)
                                            <tr>
                                                <td class="fw-semibold">{{ $item->benchmark_name }}</td>
                                                <td><span class="badge bg-light text-dark border">+{{ $item->differential_cents_lb }}¢</span></td>
                                                <td class="fw-bold text-dark">${{ number_format($item->indicative_fob_usd_lb, 2) }}</td>
                                                <td>
                                                    @if($item->trend === 'bullish')
                                                        <span class="text-success fw-bold"><i class="fa-solid fa-arrow-trend-up"></i> Firm</span>
                                                    @elseif($item->trend === 'bearish')
                                                        <span class="text-danger fw-bold"><i class="fa-solid fa-arrow-trend-down"></i> Soft</span>
                                                    @else
                                                        <span class="text-muted"><i class="fa-solid fa-minus"></i> Steady</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No active benchmark records available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 pt-3 border-top text-end">
                                <a href="{{ route('student.coffee-toolkit.market-intelligence') }}" class="small fw-bold text-decoration-none" style="color: #6D1A95;">
                                    View Full Market Report <i class="fa-solid fa-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Fira AI Advisory Quick Action Card -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: linear-gradient(180deg, #FAF5FF 0%, #FFFFFF 100%); border: 1px solid rgba(109,26,149,0.1) !important;">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                                     style="width: 44px; height: 44px; background: #6D1A95;">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0" style="color: #1a0524;">Fira AI Export Advisor</h6>
                                    <small class="text-muted">Interactive trade intelligence assistant</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-3" style="line-height: 1.5;">
                                Ask questions regarding Ethiopian export licensing, NBE foreign exchange retention rules, standard European coffee contracts, or Incoterms risk transfer.
                            </p>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <a href="{{ route('student.ai-chat.index') }}?prompt=What%20are%20the%20steps%20to%20obtain%20an%20ECTA%20coffee%20export%20license%3F" 
                                   class="btn btn-sm btn-light border text-start py-2 px-3 rounded-3 text-dark text-decoration-none">
                                    <i class="fa-regular fa-comment-dots me-2 text-muted"></i> ECTA Export licensing steps
                                </a>
                                <a href="{{ route('student.ai-chat.index') }}?prompt=Explain%20the%20Addis%20to%20Djibouti%20corridor%20container%20logistics%20costs" 
                                   class="btn btn-sm btn-light border text-start py-2 px-3 rounded-3 text-dark text-decoration-none">
                                    <i class="fa-regular fa-comment-dots me-2 text-muted"></i> Addis-Djibouti corridor logistics
                                </a>
                                <a href="{{ route('student.ai-chat.index') }}?prompt=How%20does%20EUDR%20traceability%20affect%20Ethiopian%20coffee%20exporters%3F" 
                                   class="btn btn-sm btn-light border text-start py-2 px-3 rounded-3 text-dark text-decoration-none">
                                    <i class="fa-regular fa-comment-dots me-2 text-muted"></i> EUDR farm traceability requirements
                                </a>
                            </div>
                            <a href="{{ route('student.ai-chat.index') }}" class="btn text-white w-100 py-2 rounded-3 mt-auto fw-bold" style="background: #6D1A95;">
                                <i class="fa-solid fa-paper-plane me-1"></i> Open Fira AI Chat
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Saved Activity -->
                <div class="row g-4">
                    <!-- Recent Calculations -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0">Recent Saved Calculations</h6>
                                <a href="{{ route('student.coffee-toolkit.calculator') }}" class="small fw-semibold text-decoration-none" style="color: #6D1A95;">View All</a>
                            </div>
                            @if($recentCalculations->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($recentCalculations as $calc)
                                        <div class="list-group-item px-0 py-2 border-0 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold small text-dark">{{ $calc->deal_title }}</div>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    {{ $calc->quantity_bags }} bags • ${{ number_format($calc->selling_price_usd_per_lb, 2) }}/lb • Margin: {{ $calc->margin_percentage }}%
                                                </div>
                                            </div>
                                            <span class="badge {{ $calc->net_profit_etb >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $calc->net_profit_etb >= 0 ? '+' : '' }}{{ number_format($calc->net_profit_etb, 0) }} ETB
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted small">
                                    <i class="fa-solid fa-calculator mb-2 d-block" style="font-size: 24px; color: #cbd5e1;"></i>
                                    No saved calculations yet. <a href="{{ route('student.coffee-toolkit.calculator') }}" style="color: #6D1A95;">Run your first deal calculation</a>.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Offers -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0">Recent Export Offers</h6>
                                <a href="{{ route('student.coffee-toolkit.offer-generator') }}" class="small fw-semibold text-decoration-none" style="color: #6D1A95;">View All</a>
                            </div>
                            @if($recentOffers->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($recentOffers as $offer)
                                        <div class="list-group-item px-0 py-2 border-0 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold small text-dark">{{ $offer->reference_no }}</div>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    {{ $offer->origin_region }} {{ $offer->grade }} ({{ $offer->processing_method }}) • ${{ number_format($offer->price_usd_per_lb, 2) }}/lb
                                                </div>
                                            </div>
                                            <a href="{{ route('student.coffee-toolkit.offer-generator.pdf', $offer->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" style="font-size: 11px;" target="_blank">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> PDF
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted small">
                                    <i class="fa-solid fa-file-contract mb-2 d-block" style="font-size: 24px; color: #cbd5e1;"></i>
                                    No generated offers yet. <a href="{{ route('student.coffee-toolkit.offer-generator') }}" style="color: #6D1A95;">Create an official coffee offer</a>.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
