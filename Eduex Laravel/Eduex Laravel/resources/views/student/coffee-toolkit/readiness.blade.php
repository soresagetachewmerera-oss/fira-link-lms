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
                            <span class="text-dark small fw-semibold">Export Readiness</span>
                        </div>
                        <h3 class="fw-bold mb-0" style="color: #1a0524;">Ethiopian Coffee Export Readiness Assessment</h3>
                        <p class="text-muted small mb-0">Evaluate your enterprise across licensing, sourcing traceability, quality protocols, logistics, and trade finance.</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row g-4">
                    <!-- Assessment Questionnaire Form -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                            <form method="POST" action="{{ route('student.coffee-toolkit.readiness.store') }}">
                                @csrf

                                <!-- Category 1: Licensing -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-stamp me-1"></i> 1. Licensing &amp; Legal Compliance
                                    </h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_commercial_reg" value="1" id="q1" checked>
                                        <label class="form-check-label small" for="q1">
                                            <strong>Ministry of Trade Commercial Registration</strong>
                                            <div class="text-muted" style="font-size: 11px;">Valid commercial registration with coffee export business scope.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_ecta_export_cert" value="1" id="q2">
                                        <label class="form-check-label small" for="q2">
                                            <strong>ECTA Coffee Export Competence Certificate</strong>
                                            <div class="text-muted" style="font-size: 11px;">Annual exporter qualification certificate issued by Ethiopian Coffee &amp; Tea Authority.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_nbe_code" value="1" id="q3">
                                        <label class="form-check-label small" for="q3">
                                            <strong>NBE Foreign Exchange Exporter Code</strong>
                                            <div class="text-muted" style="font-size: 11px;">Active registration with National Bank of Ethiopia for forex retention account operations.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_tax_clearance" value="1" id="q4" checked>
                                        <label class="form-check-label small" for="q4">
                                            <strong>Federal Tax Clearance Certificate</strong>
                                            <div class="text-muted" style="font-size: 11px;">Current quarterly clearance from Ministry of Revenues.</div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Category 2: Sourcing -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-tree me-1"></i> 2. Sourcing &amp; Traceability
                                    </h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_direct_vertical_contracts" value="1" id="q5">
                                        <label class="form-check-label small" for="q5">
                                            <strong>Direct Vertical Integration Contracts</strong>
                                            <div class="text-muted" style="font-size: 11px;">Registered agreements with primary washing stations or outgrower cooperative unions.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_traceability_system" value="1" id="q6">
                                        <label class="form-check-label small" for="q6">
                                            <strong>EUDR-Compliant Polygon &amp; GPS Farm Traceability</strong>
                                            <div class="text-muted" style="font-size: 11px;">Geolocation polygon mapping for farms to satisfy European Union Deforestation Regulation.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_washing_station_access" value="1" id="q7" checked>
                                        <label class="form-check-label small" for="q7">
                                            <strong>Washing Station Infrastructure Access</strong>
                                            <div class="text-muted" style="font-size: 11px;">Access to clean fermentation channels and raised African drying beds.</div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Category 3: Quality -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-mug-saucer me-1"></i> 3. Quality Grading &amp; Cupping Protocols
                                    </h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_q_grader_access" value="1" id="q8">
                                        <label class="form-check-label small" for="q8">
                                            <strong>Certified Q-Arabica Grader Partnership</strong>
                                            <div class="text-muted" style="font-size: 11px;">Access to certified cupping lab for scoring pre-shipment lots (84+ SCAA points).</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_moisture_meter" value="1" id="q9" checked>
                                        <label class="form-check-label small" for="q9">
                                            <strong>Calibrated Moisture Meter Equipment</strong>
                                            <div class="text-muted" style="font-size: 11px;">Standard testing equipment to guarantee moisture strictly between 10.0% and 11.5%.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_screen_sizing_equipment" value="1" id="q10">
                                        <label class="form-check-label small" for="q10">
                                            <strong>Dry Mill Screen Sizing Equipment</strong>
                                            <div class="text-muted" style="font-size: 11px;">Sorting machinery for Screen 14+, Screen 15+, or Screen 17/18 specifications.</div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Category 4: Logistics -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-truck-fast me-1"></i> 4. Djibouti Corridor Logistics
                                    </h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_freight_forwarder_partner" value="1" id="q11" checked>
                                        <label class="form-check-label small" for="q11">
                                            <strong>Addis-Djibouti Freight Forwarding Agreement</strong>
                                            <div class="text-muted" style="font-size: 11px;">Contracted logistics agent for Ethio-Djibouti Railway / truck transit booking.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="knows_container_stuffing_protocols" value="1" id="q12">
                                        <label class="form-check-label small" for="q12">
                                            <strong>Container Stuffing &amp; Desiccant Moisture Protection</strong>
                                            <div class="text-muted" style="font-size: 11px;">Kraft paper lining and silica gel hangbags inside 20ft ocean containers.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="knows_phytosanitary_procedures" value="1" id="q13" checked>
                                        <label class="form-check-label small" for="q13">
                                            <strong>Ministry of Agriculture Phytosanitary Certificate Protocol</strong>
                                            <div class="text-muted" style="font-size: 11px;">Inspection and fumigation protocols completed before dry port clearance.</div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Category 5: Finance -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-coins me-1"></i> 5. Trade Finance &amp; Payment Security
                                    </h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="understands_nbe_forex_retention" value="1" id="q14" checked>
                                        <label class="form-check-label small" for="q14">
                                            <strong>NBE Forex Retention &amp; Surrender Rules Mastery</strong>
                                            <div class="text-muted" style="font-size: 11px;">Compliance with commercial bank foreign currency accounts and NBE regulations.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="understands_lc_cad_terms" value="1" id="q15">
                                        <label class="form-check-label small" for="q15">
                                            <strong>Documentary Letter of Credit (UCP 600) / CAD Experience</strong>
                                            <div class="text-muted" style="font-size: 11px;">Handling shipping document presentation without discrepancies.</div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="has_buyer_contract_templates" value="1" id="q16">
                                        <label class="form-check-label small" for="q16">
                                            <strong>Standard International Coffee Contracts (ECC / GCA)</strong>
                                            <div class="text-muted" style="font-size: 11px;">Standardized terms for quality claims, arbitration, and delivery tolerances.</div>
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                    <button type="submit" class="btn text-white px-4 py-2 rounded-pill fw-semibold" style="background: #6D1A95;">
                                        <i class="fa-solid fa-chart-line me-1"></i> Evaluate Readiness Score
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Results & Action Plan -->
                    <div class="col-lg-5">
                        @if($latestAssessment)
                            <!-- Score Card -->
                            <div class="card border-0 shadow-sm rounded-4 p-4 text-white mb-4" 
                                 style="background: linear-gradient(135deg, #4A0E66 0%, #6D1A95 100%);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white-50 text-uppercase small fw-bold" style="letter-spacing: 0.5px;">OVERALL READINESS SCORE</span>
                                    <span class="badge {{ $latestAssessment->overall_score >= 80 ? 'bg-success' : ($latestAssessment->overall_score >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ $latestAssessment->readiness_tier }}
                                    </span>
                                </div>
                                <div class="display-4 fw-bold text-white my-2">{{ $latestAssessment->overall_score }}%</div>
                                <p class="small text-white-50 mb-0">Assessed on {{ $latestAssessment->created_at->format('M d, Y') }}</p>
                            </div>

                            <!-- Category Scores -->
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                                <h6 class="fw-bold mb-3 pb-2 border-bottom">Pillar Breakdown</h6>
                                @if(is_array($latestAssessment->category_scores_json))
                                    @foreach($latestAssessment->category_scores_json as $catKey => $cat)
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="fw-semibold">{{ $cat['name'] }}</span>
                                                <span class="fw-bold" style="color: #6D1A95;">{{ $cat['score'] }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $cat['score'] }}%; background-color: #6D1A95;"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Personalized Action Plan -->
                            @if(is_array($latestAssessment->action_plan_json) && count($latestAssessment->action_plan_json) > 0)
                                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                    <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                                        <i class="fa-solid fa-list-check text-warning me-1"></i> Recommended Action Plan
                                    </h6>
                                    <div class="d-flex flex-column gap-3">
                                        @foreach($latestAssessment->action_plan_json as $action)
                                            <div class="p-3 rounded-3 bg-light border-start border-4 border-primary">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong class="small text-dark">{{ $action['title'] ?? 'Milestone' }}</strong>
                                                    <span class="badge {{ ($action['priority'] ?? '') === 'High' ? 'bg-danger' : 'bg-secondary' }}" style="font-size: 10px;">
                                                        {{ $action['priority'] ?? 'Action' }}
                                                    </span>
                                                </div>
                                                <p class="mb-0 text-muted" style="font-size: 11px; line-height: 1.4;">
                                                    {{ $action['description'] ?? '' }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center">
                                <i class="fa-solid fa-clipboard-check mb-3" style="font-size: 48px; color: #cbd5e1;"></i>
                                <h5 class="fw-bold">No Assessment Run Yet</h5>
                                <p class="text-muted small">Complete the checklist on the left to generate your verified export readiness diagnostic and personalized action plan.</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
