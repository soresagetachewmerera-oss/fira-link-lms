@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Programs',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<div class="fira-programs-page section-padding" style="padding: 60px 0 90px; background-color: #FAFAFC;">
    <div class="container">
        <!-- Main Heading Area -->
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-9 col-md-11">
                <span class="badge px-3 py-2 rounded-pill mb-3 text-uppercase fw-semibold" style="background-color: rgba(109, 26, 149, 0.1); color: #6D1A95; font-size: 13px; letter-spacing: 0.5px;">
                    Practical Trade Education
                </span>
                <h2 class="fw-bold mb-3" style="color: #0F172A; font-size: 36px; line-height: 1.25;">
                    Four Professional Import-Export Tracks
                </h2>
                <p class="text-secondary" style="font-size: 17px; line-height: 1.7; max-width: 780px; margin: 0 auto;">
                    Fira Link Platform delivers industry-backed training tracks built specifically for entrepreneurs, commercial traders, and logistics professionals operating across Ethiopia and the greater East African economic corridors.
                </p>
            </div>
        </div>

        <!-- 4 Core Pillars Grid -->
        <div class="row g-4 mb-5">
            <!-- Pillar 1 -->
            <div class="col-lg-6 col-md-12">
                <div class="card h-100 border-0 shadow-sm p-4 p-md-5" style="border-radius: 18px; background: #FFFFFF;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 me-3" style="width: 56px; height: 56px; background-color: #6D1A95; color: #FFFFFF; font-size: 24px;">
                            <i class="fa-solid fa-mug-hot"></i>
                        </div>
                        <div>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 1px;">Track 01 &bull; Specialty Export</span>
                            <h2 class="h5 fw-bold mb-0 text-dark">Coffee Sourcing &amp; Export Excellence</h2>
                        </div>
                    </div>
                    <p class="text-secondary" style="font-size: 15px; line-height: 1.65;">
                        Ethiopia is the birthplace of Arabica coffee and a global powerhouse for specialty washed and natural lots. This comprehensive program guides exporters through origin mapping across Yirgacheffe, Sidama, Guji, and Limu, ECX warehouse receipt protocols, SCA cupping grading standards, export licensing requirements, and foreign buyer contracts.
                    </p>
                    <ul class="list-unstyled text-secondary small mb-4" style="line-height: 2;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> ECX &amp; Vertical Integration direct sourcing agreements</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Quality sampling, moisture grading, and cupping scores</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> ICO documentation, Phytosanitary, and origin certifications</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top">
                        <a href="{{ route('courses') }}" class="fw-semibold text-decoration-none" style="color: #6D1A95;">
                            View Available Coffee Courses <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pillar 2 -->
            <div class="col-lg-6 col-md-12">
                <div class="card h-100 border-0 shadow-sm p-4 p-md-5" style="border-radius: 18px; background: #FFFFFF;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 me-3" style="width: 56px; height: 56px; background-color: #6D1A95; color: #FFFFFF; font-size: 24px;">
                            <i class="fa-solid fa-file-shield"></i>
                        </div>
                        <div>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 1px;">Track 02 &bull; Compliance</span>
                            <h2 class="h5 fw-bold mb-0 text-dark">Customs Clearance &amp; Single Window</h2>
                        </div>
                    </div>
                    <p class="text-secondary" style="font-size: 15px; line-height: 1.65;">
                        Avoid costly demurrage, customs seizure, and regulatory penalties. This curriculum deconstructs the Ethiopian Customs Commission operational workflows, ESW (Electronic Single Window) declaration procedures, Harmonized System (HS) code classification, duty-free incentive regimes, and commercial invoice compliance.
                    </p>
                    <ul class="list-unstyled text-secondary small mb-4" style="line-height: 2;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> Step-by-step ESW portal document uploads and approvals</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Tariff schedule interpretation and valuation methods</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Pre-shipment inspection (PSI) and conformity compliance</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top">
                        <a href="{{ route('free-tools.index') }}" class="fw-semibold text-decoration-none" style="color: #6D1A95;">
                            Use Free Export Cost Calculator <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pillar 3 -->
            <div class="col-lg-6 col-md-12">
                <div class="card h-100 border-0 shadow-sm p-4 p-md-5" style="border-radius: 18px; background: #FFFFFF;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 me-3" style="width: 56px; height: 56px; background-color: #6D1A95; color: #FFFFFF; font-size: 24px;">
                            <i class="fa-solid fa-ship"></i>
                        </div>
                        <div>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 1px;">Track 03 &bull; Logistics</span>
                            <h2 class="h5 fw-bold mb-0 text-dark">Multi-Modal Freight &amp; Port Corridors</h2>
                        </div>
                    </div>
                    <p class="text-secondary" style="font-size: 15px; line-height: 1.65;">
                        Seamlessly move cargo between inland dry ports (Modjo, Kality, Semera) and deepwater maritime hubs (Djibouti, Berbera, Mombasa). Understand multimodal Bills of Lading, container stuffing, cold chain maintenance, Incoterms 2020 obligations (FOB, CIF, CFR), and marine insurance risk underwriting.
                    </p>
                    <ul class="list-unstyled text-secondary small mb-4" style="line-height: 2;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> ESLSE multimodal contract terms and shipping line bookings</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Inland trucking transit documentation and seal tracking</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Cross-border logistics risk mitigation and claim handling</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top">
                        <a href="{{ route('tours.index') }}" class="fw-semibold text-decoration-none" style="color: #6D1A95;">
                            Explore Practical Port Tours <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pillar 4 -->
            <div class="col-lg-6 col-md-12">
                <div class="card h-100 border-0 shadow-sm p-4 p-md-5" style="border-radius: 18px; background: #FFFFFF;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 me-3" style="width: 56px; height: 56px; background-color: #6D1A95; color: #FFFFFF; font-size: 24px;">
                            <i class="fa-solid fa-handshake"></i>
                        </div>
                        <div>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 1px;">Track 04 &bull; Global Markets</span>
                            <h2 class="h5 fw-bold mb-0 text-dark">International Buyers &amp; Forex Settlement</h2>
                        </div>
                    </div>
                    <p class="text-secondary" style="font-size: 15px; line-height: 1.65;">
                        Securing verified buyers in North America, Europe, the Middle East, and Asia requires structured outreach and robust payment security. Learn how to draft sales contracts under international trade law, navigate Letters of Credit (L/C), Cash Against Documents (CAD), and repatriate foreign exchange under National Bank of Ethiopia (NBE) rules.
                    </p>
                    <ul class="list-unstyled text-secondary small mb-4" style="line-height: 2;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> Vetting prospective international importers and trade agencies</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> L/C documentary credit verification and discrepancy prevention</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> NBE retention account rules and currency allocation</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top">
                        <a href="{{ route('pricing') }}" class="fw-semibold text-decoration-none" style="color: #6D1A95;">
                            Review Tuition &amp; Program Options <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Practical Methodology Section -->
        <div class="p-4 p-md-5 rounded-4 mb-5" style="background: linear-gradient(135deg, #2A0845 0%, #6D1A95 100%); color: #FFFFFF;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="text-uppercase fw-bold text-warning small">Why Fira Link Platform</span>
                    <h3 class="fw-bold mt-2 text-white" style="font-size: 28px;">Grounded in Real Trade, Not Just Theory</h3>
                    <p class="text-white-50 mt-3" style="font-size: 16px; line-height: 1.7;">
                        Unlike generic academic business courses, every program at Fira Link Platform is authored by experienced import-export practitioners and coffee exporters. Learners participate in case studies based on authentic commercial documents, interactive cargo cost calculators, and field expeditions to origin washing stations and dry ports.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <a href="{{ route('courses') }}" class="btn btn-light btn-lg px-4 py-3 fw-bold shadow" style="color: #6D1A95; border-radius: 12px;">
                        Explore Course Catalog
                    </a>
                </div>
            </div>
        </div>

        <!-- Published Courses Carousel / List Preview -->
        @if(isset($courses) && $courses->count() > 0)
        <div class="mt-5">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <span class="text-uppercase fw-bold text-muted small">Curriculum Preview</span>
                    <h2 class="h4 fw-bold text-dark mt-1">Featured Practical Courses</h2>
                </div>
                <a href="{{ route('courses') }}" class="fw-semibold text-decoration-none" style="color: #6D1A95;">
                    Browse All Courses ({{ $courses->count() }}) <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="row g-4">
                @foreach($courses as $c)
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 14px; overflow: hidden; background: #FFFFFF;">
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="badge mb-2 align-self-start" style="background-color: rgba(109, 26, 149, 0.1); color: #6D1A95; font-size: 11px;">
                                {{ $c->difficulty ? ucfirst($c->difficulty) : 'All Levels' }}
                            </span>
                            <h3 class="h6 fw-bold mb-2">
                                <a href="{{ route('courses.show', $c->id) }}" class="text-dark text-decoration-none">
                                    {{ $c->title }}
                                </a>
                            </h3>
                            <p class="text-muted small mb-3" style="line-height: 1.6;">
                                {{ Str::limit(strip_tags((string)$c->description), 110) }}
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <span class="fw-bold" style="color: #6D1A95;">
                                    @if($c->pricing_model === 'free')
                                        Free
                                    @elseif($c->sale_price)
                                        {{ number_format($c->sale_price) }} ETB
                                    @elseif($c->regular_price)
                                        {{ number_format($c->regular_price) }} ETB
                                    @else
                                        Contact Academy
                                    @endif
                                </span>
                                <a href="{{ route('courses.show', $c->id) }}" class="btn btn-sm btn-outline-primary" style="color: #6D1A95; border-color: #6D1A95; border-radius: 8px;">
                                    View Syllabus
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
