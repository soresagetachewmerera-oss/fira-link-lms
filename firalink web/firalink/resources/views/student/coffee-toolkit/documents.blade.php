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
                            <span class="text-dark small fw-semibold">Export Document Assistant</span>
                        </div>
                        <h3 class="fw-bold mb-0" style="color: #1a0524;">Export Document Assistant</h3>
                        <p class="text-muted small mb-0">Standardized, editable document templates compliant with Ethiopian Coffee &amp; Tea Authority and international trade rules.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Template 1: Pro Forma Invoice -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="icon-box rounded-3 d-flex align-items-center justify-content-center" 
                                     style="width: 44px; height: 44px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                    <i class="fa-solid fa-file-invoice-dollar fs-5"></i>
                                </div>
                                <span class="badge bg-light text-dark border small">Standard Template</span>
                            </div>
                            <h5 class="fw-bold mb-1">Export Pro Forma Invoice</h5>
                            <p class="text-muted small mb-3">Essential for international buyers to establish Documentary Letters of Credit (LC) with issuing banks.</p>
                            <div class="bg-light p-3 rounded-3 small text-muted mb-3 font-monospace" style="font-size: 11px; max-height: 140px; overflow-y: auto;">
                                PRO FORMA INVOICE<br>
                                Ref: PI-FL-{{ date('Y') }}-001<br>
                                Consignee: [International Coffee Importer]<br>
                                Commodity: Ethiopian Washed Arabica Grade 2<br>
                                Incoterm: FOB Port of Djibouti<br>
                                Payment: 100% Irrevocable LC at Sight<br>
                                Quantity: 320 Bags (19,200 kg net)
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 rounded-pill" onclick="copyTemplate('proforma')">
                                <i class="fa-regular fa-copy me-1"></i> Copy Pro Forma Template
                            </button>
                        </div>
                    </div>

                    <!-- Template 2: Packing List -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="icon-box rounded-3 d-flex align-items-center justify-content-center" 
                                     style="width: 44px; height: 44px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                    <i class="fa-solid fa-boxes-packing fs-5"></i>
                                </div>
                                <span class="badge bg-light text-dark border small">Standard Template</span>
                            </div>
                            <h5 class="fw-bold mb-1">Export Packing List (FCL)</h5>
                            <p class="text-muted small mb-3">Required by Ethiopian Customs Commission and Port of Djibouti container terminal operators.</p>
                            <div class="bg-light p-3 rounded-3 small text-muted mb-3 font-monospace" style="font-size: 11px; max-height: 140px; overflow-y: auto;">
                                EXPORT PACKING LIST<br>
                                Container No: [20ft Dry Cargo FCL]<br>
                                Seal No: [Customs Seal]<br>
                                Packaging: 60kg New Jute Bags with inner GrainPro<br>
                                Gross Weight: 19,456.00 kg<br>
                                Net Weight: 19,200.00 kg (42,328.7 lbs)<br>
                                Shipping Marks: [Exporter Mark / Lot ID / Sidama G2]
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 rounded-pill" onclick="copyTemplate('packing')">
                                <i class="fa-regular fa-copy me-1"></i> Copy Packing List Template
                            </button>
                        </div>
                    </div>

                    <!-- Template 3: Buyer Introduction Letter -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="icon-box rounded-3 d-flex align-items-center justify-content-center" 
                                     style="width: 44px; height: 44px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                    <i class="fa-solid fa-envelope-open-text fs-5"></i>
                                </div>
                                <span class="badge bg-light text-dark border small">Outreach</span>
                            </div>
                            <h5 class="fw-bold mb-1">Buyer Sample Introduction Letter</h5>
                            <p class="text-muted small mb-3">Professional correspondence template for introducing fresh harvest offer samples to international green buyers.</p>
                            <div class="bg-light p-3 rounded-3 small text-muted mb-3" style="font-size: 11px; max-height: 140px; overflow-y: auto;">
                                Dear [Buyer Name / Green Coffee Buyer],<br><br>
                                We are pleased to present our fresh 2025/2026 crop specialty coffee lots directly from [Washing Station / Region]. Enclosed are pre-shipment green samples scored 85.5+ SCAA by our certified Q-grader. Our lots are fully traceable and packed with GrainPro liners for FOB Djibouti or CIF delivery.
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 rounded-pill" onclick="copyTemplate('intro')">
                                <i class="fa-regular fa-copy me-1"></i> Copy Outreach Template
                            </button>
                        </div>
                    </div>

                    <!-- Template 4: ECTA Pre-Export Checklist -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="icon-box rounded-3 d-flex align-items-center justify-content-center" 
                                     style="width: 44px; height: 44px; background: rgba(109,26,149,0.1); color: #6D1A95;">
                                    <i class="fa-solid fa-clipboard-check fs-5"></i>
                                </div>
                                <span class="badge bg-light text-dark border small">Regulatory</span>
                            </div>
                            <h5 class="fw-bold mb-1">ECTA Export Clearance Checklist</h5>
                            <p class="text-muted small mb-3">Mandatory documents required before coffee lot release from Addis Ababa dry port to Djibouti.</p>
                            <ul class="list-unstyled small text-muted mb-3" style="font-size: 11px;">
                                <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> 1. Coffee Export Contract Registration at ECTA</li>
                                <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> 2. NBE Foreign Exchange Permit Approval</li>
                                <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> 3. ECTA Laboratory Cupping &amp; Grade Certificate</li>
                                <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> 4. Ministry of Agriculture Phytosanitary Certificate</li>
                                <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> 5. Ethiopian Chamber of Commerce Certificate of Origin</li>
                            </ul>
                            <a href="{{ route('student.coffee-toolkit.readiness') }}" class="btn btn-outline-primary btn-sm w-100 rounded-pill">
                                <i class="fa-solid fa-shield-halved me-1"></i> Run Readiness Diagnostic
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    const templates = {
        proforma: `PRO FORMA INVOICE\nDate: ${new Date().toLocaleDateString()}\nRef: PI-FL-${new Date().getFullYear()}-001\nExporter: [Company Name] PLC, Addis Ababa, Ethiopia\nBuyer: [Buyer Company Name], [Destination Country]\n\nCOMMODITY: Ethiopian Arabica Green Coffee\nORIGIN / GRADE: Sidama Washed Grade 2\nCROP YEAR: 2025/2026\nQUANTITY: 320 Bags x 60kg Net (19,200 kg)\nPACKAGING: 60kg Jute Bags with inner GrainPro liner\nPRICE: USD $3.20 per lb FOB Port of Djibouti\nTOTAL VALUE: USD $135,452.00\nPAYMENT TERMS: 100% Irrevocable Letter of Credit (LC) at Sight\nVALIDITY: 14 Days from issuance date`,
        packing: `EXPORT PACKING LIST\nInvoice No: PI-FL-${new Date().getFullYear()}-001\nContainer No: [20ft Dry Cargo FCL]\nSeal No: [ECTA Customs Seal]\n\nItem: Ethiopian Green Arabica Coffee (Sidama Washed G2)\nNumber of Packages: 320 Bags\nNet Weight: 19,200.00 kg\nGross Weight: 19,456.00 kg\nTare Weight: 256.00 kg\nMeasurement: 33.2 CBM\nMarks & Numbers: [SIDAMA / G2 / LOT 01 / CROP 25-26]`,
        intro: `Subject: Fresh Harvest Offer & Pre-Shipment Samples - Ethiopian Specialty Coffee\n\nDear [Buyer Name],\n\nWe are pleased to introduce our fresh 2025/2026 crop specialty coffee lots directly from our registered washing stations in Sidama and Yirgacheffe.\n\nOur current offer features:\n- Grade 1 & Grade 2 Washed (Cupping score 85+ SCAA, floral bergamot, peach notes)\n- Moisture: strictly 10.5% - 11.2%\n- Packaging: 60kg Jute with GrainPro liners\n- Full farm and washing station traceability compliant with EUDR regulations.\n\nWe would be honored to dispatch pre-shipment offer samples to your cupping lab. Please confirm your delivery address and preferred carrier account.\n\nWarm regards,\n[Your Name]\n[Your Company] PLC\nAddis Ababa, Ethiopia`
    };

    function copyTemplate(type) {
        if (templates[type]) {
            navigator.clipboard.writeText(templates[type]).then(() => {
                alert('Template copied to clipboard!');
            });
        }
    }
</script>
@endpush
@endsection
