@extends('layouts.student')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('student.partials.sidebar')

            <div class="dashboard-main">
                <!-- Page Header -->
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <a href="{{ route('student.coffee-toolkit.index') }}" class="text-muted text-decoration-none small">
                                <i class="fa-solid fa-arrow-left me-1"></i> Toolkit Hub
                            </a>
                            <span class="text-muted small">/</span>
                            <span class="text-dark small fw-semibold">Market Intelligence</span>
                        </div>
                        <h3 class="fw-bold mb-0" style="color: #1a0524;">Ethiopian Coffee Export Market Intelligence</h3>
                        <p class="text-muted small mb-0">Verified ICE Arabica C-market differentials, indicative FOB Djibouti prices, and ECX floor benchmarks.</p>
                    </div>
                </div>

                <!-- Macro Benchmark Banner -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-white" 
                     style="background: linear-gradient(135deg, #4A0E66 0%, #6D1A95 100%);">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3 mb-md-0 border-end border-white-50 pe-md-4">
                            <span class="text-white-50 text-uppercase small fw-bold">ICE ARABICA 'C' FUTURES</span>
                            <div class="d-flex align-items-baseline gap-2 my-1">
                                <h2 class="text-white fw-bold mb-0">248.50¢</h2>
                                <span class="badge bg-success small">+4.20¢ (Firm)</span>
                            </div>
                            <small class="text-white-50">Intercontinental Exchange (ICE NY) Active Month</small>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0 border-end border-white-50 px-md-4">
                            <span class="text-white-50 text-uppercase small fw-bold">ETHIOPIA SPECIALTY DIFFERENTIAL</span>
                            <div class="d-flex align-items-baseline gap-2 my-1">
                                <h2 class="text-white fw-bold mb-0">+65¢ to +110¢</h2>
                                <span class="badge bg-light text-dark small">Premium</span>
                            </div>
                            <small class="text-white-50">Grade 1 &amp; Grade 2 Specialty premium over NY 'C'</small>
                        </div>
                        <div class="col-md-4 ps-md-4">
                            <span class="text-white-50 text-uppercase small fw-bold">EXPORT SHIPPING CYCLE</span>
                            <div class="my-1">
                                <h5 class="text-white fw-bold mb-0">Main Shipping Season</h5>
                            </div>
                            <small class="text-white-50">Peak container dispatches: February through August</small>
                        </div>
                    </div>
                </div>

                <!-- Benchmarks Table Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Active Export Differential Benchmarks</h5>
                        <span class="badge bg-light text-dark border px-3 py-2 small">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Current Crop 2025/2026
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>ORIGIN BENCHMARK</th>
                                    <th>CUP / SENSORY PROFILE</th>
                                    <th>NY 'C' BASE</th>
                                    <th>DIFFERENTIAL</th>
                                    <th>INDICATIVE FOB</th>
                                    <th>ECX REF (ETB/KG)</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($benchmarks as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item->benchmark_name }}</div>
                                            <div class="text-muted" style="font-size: 11px;">Source: {{ $item->source }}</div>
                                        </td>
                                        <td style="max-width: 250px;">
                                            <div class="text-muted" style="font-size: 11px; line-height: 1.4;">{{ $item->grade_description }}</div>
                                        </td>
                                        <td>{{ number_format($item->arabica_c_cents_lb, 2) }}¢</td>
                                        <td>
                                            <span class="badge {{ $item->differential_cents_lb >= 0 ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $item->differential_cents_lb >= 0 ? '+' : '' }}{{ $item->differential_cents_lb }}¢ / lb
                                            </span>
                                        </td>
                                        <td class="fw-bold fs-6 text-dark">${{ number_format($item->indicative_fob_usd_lb, 2) }} / lb</td>
                                        <td class="fw-semibold text-muted">{{ number_format($item->ecx_reference_etb_per_kg, 0) }} ETB</td>
                                        <td>
                                            <a href="{{ route('student.coffee-toolkit.calculator') }}?price={{ $item->indicative_fob_usd_lb }}" class="btn btn-sm text-white rounded-pill px-3 py-1" style="background: #6D1A95; font-size: 11px;">
                                                <i class="fa-solid fa-calculator me-1"></i> Model Deal
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No benchmark records active.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Export Calendar & Seasonal Insights -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                                <i class="fa-solid fa-calendar-days text-primary me-1"></i> Annual Ethiopian Coffee Export Cycle
                            </h6>
                            <div class="d-flex flex-column gap-3 small">
                                <div class="d-flex gap-3">
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-2 align-self-start" style="width: 100px;">Oct – Dec</span>
                                    <div>
                                        <strong class="text-dark">Primary Cherry Harvest &amp; Wet Processing</strong>
                                        <div class="text-muted">Peak cherry ripening across Sidama, Yirgacheffe, Guji, and Limu. Washing stations operate 24/7 wet fermentation.</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="badge bg-info text-dark rounded-pill px-3 py-2 align-self-start" style="width: 100px;">Jan – Mar</span>
                                    <div>
                                        <strong class="text-dark">Dry Milling, Cupping &amp; Sample Dispatch</strong>
                                        <div class="text-muted">Parchment resting, hulling, optical sorting, and dispatch of offer samples to European, Asian, and American importers.</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="badge bg-success text-white rounded-pill px-3 py-2 align-self-start" style="width: 100px;">Feb – Aug</span>
                                    <div>
                                        <strong class="text-dark">Peak Corridor Logistics &amp; Port Stuffing</strong>
                                        <div class="text-muted">ECTA inspection, Addis-Djibouti train and convoy transits, container stuffing in GrainPro liners, and vessel loadings.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                                <i class="fa-solid fa-book-bookmark text-primary me-1"></i> Official Ethiopian Export Classifications
                            </h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="p-2 rounded bg-light border">
                                    <strong class="text-dark">Grade 1 Washed (Q1) / Grade 1 Natural</strong>
                                    <div class="text-muted" style="font-size: 11px;">Max 3 defect count, 85+ SCAA cupping score. Commands top international specialty differential premiums.</div>
                                </div>
                                <div class="p-2 rounded bg-light border">
                                    <strong class="text-dark">Grade 2 Washed (Q2)</strong>
                                    <div class="text-muted" style="font-size: 11px;">Max 4-12 defects, 80-84 SCAA score. The commercial benchmark volume for Ethiopian specialty washed coffee.</div>
                                </div>
                                <div class="p-2 rounded bg-light border">
                                    <strong class="text-dark">Grade 3 to Grade 5 Commercial Unwashed</strong>
                                    <div class="text-muted" style="font-size: 11px;">Contracted for large industrial roasters and espresso blends. Priced near or at discount to NY 'C'.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
