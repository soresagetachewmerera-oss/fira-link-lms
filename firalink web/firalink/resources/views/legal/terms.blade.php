@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Terms of Service',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<div class="fira-legal-page section-padding" style="padding: 60px 0 90px; background-color: #FAFAFC;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 18px; background: #FFFFFF;">
                    <span class="badge px-3 py-2 rounded-pill mb-3 text-uppercase fw-semibold" style="background-color: rgba(109, 26, 149, 0.1); color: #6D1A95; font-size: 12px; width: fit-content;">
                        Agreement &amp; Governance
                    </span>
                    <h2 class="fw-bold mb-3" style="color: #0F172A; font-size: 32px;">Platform Terms &amp; Enrollment Agreement</h2>
                    <p class="text-muted small mb-4">Last Updated: October 2026 &bull; Effective Date: January 1, 2026</p>

                    <div class="legal-content text-secondary" style="font-size: 15px; line-height: 1.8;">
                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">1. Agreement to Terms</h2>
                        <p>
                            By creating an account, enrolling in a training course, reserving a tour, or accessing free export calculators on Fira Link Academy (operated by Fira Link Business Solution), you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you must discontinue using our services immediately.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">2. Educational Services &amp; Account Responsibility</h2>
                        <p>
                            Fira Link Academy provides digital learning modules, commercial trade documentation guides, export calculators, and organized origin tours. You agree to provide accurate registration details and maintain the security of your credentials. Sharing account access with third parties is strictly prohibited and may result in immediate license termination without refund.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">3. Intellectual Property Rights</h2>
                        <p>
                            All curriculum materials, video lectures, proprietary cost formulas, trade checklists, slide decks, and visual graphics are the exclusive intellectual property of Fira Link Business Solution. Enrolled students are granted a limited, personal, non-exclusive, non-transferable license to access the materials for personal educational use. You may not record, redistribute, republish, or commercialize Academy course materials.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">4. Payment &amp; Refund Policy</h2>
                        <p>
                            Course tuition must be settled in full prior to curriculum access via approved payment gateways (Telebirr, Chapa, CBE, or direct bank transfer). We provide a 7-day refund guarantee for online courses if less than 25% of the course has been accessed and no completion certificate has been generated. Field tour reservations are subject to individual cancellation policies due to pre-committed transit and lodging expenses.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">5. Disclaimer of Trade Warranties</h2>
                        <p>
                            While our courses are authored by active trade practitioners and designed to reflect accurate regulatory protocols, international trade conditions fluctuate. Fira Link Academy does not guarantee specific commercial revenue, foreign exchange allocations, or customs clearance approvals for individual private enterprises. Learners remain responsible for their independent commercial contracts.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">6. Governing Law &amp; Jurisdiction</h2>
                        <p>
                            These terms shall be governed by and construed in accordance with the laws of the Federal Democratic Republic of Ethiopia. Any disputes arising out of or in connection with these terms shall be subject to the exclusive jurisdiction of the competent courts in Addis Ababa, Ethiopia.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
