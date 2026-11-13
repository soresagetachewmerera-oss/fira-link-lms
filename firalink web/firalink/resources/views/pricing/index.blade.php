@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Pricing',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<div class="fira-pricing-page section-padding" style="padding: 60px 0 90px; background-color: #FAFAFC;">
    <div class="container">
        <!-- Main Heading Area -->
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-9 col-md-11">
                <span class="badge px-3 py-2 rounded-pill mb-3 text-uppercase fw-semibold" style="background-color: rgba(109, 26, 149, 0.1); color: #6D1A95; font-size: 13px; letter-spacing: 0.5px;">
                    Investment &amp; Value
                </span>
                <h2 class="fw-bold mb-3" style="color: #0F172A; font-size: 36px; line-height: 1.25;">
                    Transparent Tuition &amp; Learning Tiers
                </h2>
                <p class="text-secondary" style="font-size: 17px; line-height: 1.7; max-width: 780px; margin: 0 auto;">
                    Transparent, straightforward enrollment fees with zero hidden charges. Choose self-paced trade masterclasses, live practitioner cohorts, or free access to our export calculation suite.
                </p>
            </div>
        </div>

        <!-- 3 Tier Pricing Cards -->
        <div class="row g-4 mb-5 align-items-stretch">
            <!-- Tier 1: Free Tools & Market Terminal -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm p-4 p-md-5 d-flex flex-column" style="border-radius: 18px; background: #FFFFFF;">
                    <div class="mb-4">
                        <span class="badge mb-2 px-3 py-1 rounded-pill" style="background-color: #E2E8F0; color: #475569; font-size: 12px;">Public Access</span>
                        <h2 class="h4 fw-bold text-dark mt-2 mb-1">Free Trade Tools</h2>
                        <p class="text-muted small">Essential calculations for every exporter</p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-baseline">
                            <span class="display-5 fw-bold" style="color: #0F172A;">0</span>
                            <span class="ms-2 text-muted fw-semibold">ETB / Free Forever</span>
                        </div>
                        <p class="text-muted small mt-1">No credit card or payment required.</p>
                    </div>

                    <ul class="list-unstyled text-secondary small mb-5" style="line-height: 2.1;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> Real-time Coffee Export Cost Calculator</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Live FOB/CIF Breakeven &amp; Margin Estimator</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Ethiopian Customs Tariff &amp; Duty Guides</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Incoterms 2020 Matrix &amp; Risk Checklist</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Community Discussion Boards</li>
                    </ul>

                    <div class="mt-auto">
                        <a href="{{ route('free-tools.index') }}" class="btn btn-outline-secondary w-100 py-3 fw-bold" style="border-radius: 12px;">
                            Launch Free Tools
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tier 2: Specialized Online Courses (Popular) -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-2 shadow p-4 p-md-5 d-flex flex-column position-relative" style="border-radius: 18px; background: #FFFFFF; border-color: #6D1A95;">
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge px-3 py-2 rounded-pill text-uppercase fw-bold" style="background-color: #6D1A95; color: #FFFFFF; font-size: 11px; letter-spacing: 0.8px;">
                            Most Popular
                        </span>
                    </div>

                    <div class="mb-4 mt-2">
                        <span class="badge mb-2 px-3 py-1 rounded-pill" style="background-color: rgba(109, 26, 149, 0.1); color: #6D1A95; font-size: 12px;">Self-Paced Training</span>
                        <h2 class="h4 fw-bold text-dark mt-2 mb-1">Course Enrollment</h2>
                        <p class="text-muted small">Deep-dive video modules with verified certificate</p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-baseline">
                            <span class="display-6 fw-bold" style="color: #6D1A95;">Per Course</span>
                        </div>
                        <p class="text-muted small mt-1">Starting from ~2,500 to 12,000 ETB</p>
                    </div>

                    <ul class="list-unstyled text-secondary small mb-5" style="line-height: 2.1;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> Lifetime access to high-definition video lessons</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Authentic downloadable trade templates (BL, LC, Invoice)</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Direct Q&amp;A support with course instructors</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Industry-recognized Certificate of Completion</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Local payment via Telebirr, CBE &amp; Chapa</li>
                    </ul>

                    <div class="mt-auto">
                        <a href="{{ route('courses') }}" class="btn w-100 py-3 fw-bold text-white shadow-sm" style="background-color: #6D1A95; border-radius: 12px;">
                            Browse Course Catalog
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tier 3: Field Expeditions & Corporate -->
            <div class="col-lg-4 col-md-12">
                <div class="card h-100 border-0 shadow-sm p-4 p-md-5 d-flex flex-column" style="border-radius: 18px; background: #FFFFFF;">
                    <div class="mb-4">
                        <span class="badge mb-2 px-3 py-1 rounded-pill" style="background-color: #E2E8F0; color: #475569; font-size: 12px;">In-Person &amp; Advisory</span>
                        <h2 class="h4 fw-bold text-dark mt-2 mb-1">Field Expeditions &amp; Tours</h2>
                        <p class="text-muted small">Hands-on origin cupping &amp; port transit</p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-baseline">
                            <span class="display-6 fw-bold" style="color: #0F172A;">Per Event</span>
                        </div>
                        <p class="text-muted small mt-1">Scheduled guided cohorts throughout the harvest year</p>
                    </div>

                    <ul class="list-unstyled text-secondary small mb-5" style="line-height: 2.1;">
                        <li><i class="fa-solid fa-check text-success me-2"></i> On-site visits to coffee washing stations in Yirgacheffe &amp; Sidama</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Guided inspections of Modjo Dry Port operations</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Cupping protocol sessions with licensed Q-Graders</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Networking dinners with registered international buyers</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Ground transport, accommodation, and entry fees included</li>
                    </ul>

                    <div class="mt-auto">
                        <a href="{{ route('tours.index') }}" class="btn btn-outline-secondary w-100 py-3 fw-bold" style="border-radius: 12px;">
                            View Upcoming Tours
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods & Security -->
        <div class="card border-0 shadow-sm p-4 p-md-5 mb-5" style="border-radius: 18px; background: #FFFFFF;">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="text-uppercase fw-bold text-muted small">Flexible Payment Methods</span>
                    <h3 class="h4 fw-bold text-dark mt-1">Pay Locally or Internationally With Ease</h3>
                    <p class="text-secondary mb-0" style="font-size: 15px; line-height: 1.7;">
                        Fira Link Academy supports instant electronic settlement via Ethiopian commercial banks and modern fintech gateways. Whether you are paying from Addis Ababa or across the diaspora, enrollment is confirmed immediately upon transaction verification.
                    </p>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <span class="badge bg-light text-dark p-3 border"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i>Telebirr</span>
                        <span class="badge bg-light text-dark p-3 border"><i class="fa-solid fa-building-columns me-2 text-primary"></i>Commercial Bank of Ethiopia (CBE)</span>
                        <span class="badge bg-light text-dark p-3 border"><i class="fa-solid fa-bolt me-2 text-success"></i>Chapa Payment Gateway</span>
                        <span class="badge bg-light text-dark p-3 border"><i class="fa-solid fa-credit-card me-2 text-warning"></i>Bank Wire Transfer</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing FAQ Section -->
        <div class="mt-5">
            <div class="text-center mb-5">
                <span class="text-uppercase fw-bold text-muted small">Common Questions</span>
                <h2 class="h3 fw-bold text-dark mt-1">Frequently Asked Questions About Tuition</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion" id="pricingFaqAccordion">
                        <div class="accordion-item border-0 shadow-sm mb-3" style="border-radius: 12px; overflow: hidden;">
                            <h3 class="accordion-header" id="headingOne">
                                <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="background: #FFFFFF;">
                                    Are there any recurring membership or hidden subscription fees?
                                </button>
                            </h3>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#pricingFaqAccordion">
                                <div class="accordion-body text-secondary" style="line-height: 1.7;">
                                    No. When you enroll in an online masterclass, you pay a one-time tuition fee that grants you lifetime access to the curriculum, downloadable legal trade templates, and any future course updates without recurring charges.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3" style="border-radius: 12px; overflow: hidden;">
                            <h3 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="background: #FFFFFF;">
                                    Do you offer refunds if a course does not meet my expectations?
                                </button>
                            </h3>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#pricingFaqAccordion">
                                <div class="accordion-body text-secondary" style="line-height: 1.7;">
                                    Yes. We provide a 7-day money-back satisfaction guarantee on digital course purchases, provided less than 25% of the video material has been viewed and certificate issuance has not been triggered.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3" style="border-radius: 12px; overflow: hidden;">
                            <h3 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="background: #FFFFFF;">
                                    Can companies receive official tax invoices and business receipts?
                                </button>
                            </h3>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#pricingFaqAccordion">
                                <div class="accordion-body text-secondary" style="line-height: 1.7;">
                                    Absolutely. Fira Link Business Solution provides formal corporate receipts and tax invoices with Ministry of Trade and Ethiopian Customs compliant registration details for corporate training write-offs.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
