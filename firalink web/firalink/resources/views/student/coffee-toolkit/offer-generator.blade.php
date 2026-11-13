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
                            <span class="text-dark small fw-semibold">Offer Generator</span>
                        </div>
                        <h3 class="fw-bold mb-0" style="color: #1a0524;">Ethiopian Coffee Export Offer Generator</h3>
                        <p class="text-muted small mb-0">Generate professional, international buyer-ready coffee specification sheets with verified PDF export.</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row g-4">
                    <!-- Offer Creation Form -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                            <form id="offer-form" method="POST" action="{{ route('student.coffee-toolkit.offer-generator.store') }}">
                                @csrf

                                <!-- Exporter & Origin Details -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-building me-1"></i> 1. Exporter & Origin Profile
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Exporter Company Name</label>
                                            <input type="text" name="exporter_name" id="exporter_name" class="form-control form-control-sm" value="{{ $user->name }} Trading PLC" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Exporter Contact / Email</label>
                                            <input type="text" name="exporter_contact" id="exporter_contact" class="form-control form-control-sm" value="{{ $user->email }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Origin Region</label>
                                            <select name="origin_region" id="origin_region" class="form-select form-select-sm" required>
                                                <option value="Sidama" selected>Sidama</option>
                                                <option value="Yirgacheffe">Yirgacheffe</option>
                                                <option value="Guji">Guji</option>
                                                <option value="Limu">Limu</option>
                                                <option value="Jimma">Jimma</option>
                                                <option value="Harrar">Harrar</option>
                                                <option value="Kaffa">Kaffa</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Sub-Region / Woreda</label>
                                            <input type="text" name="sub_region" id="sub_region" class="form-control form-control-sm" value="Bensa / Shantawene">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Washing Station / Mill</label>
                                            <input type="text" name="washing_station" id="washing_station" class="form-control form-control-sm" value="Bombe Washing Station">
                                        </div>
                                    </div>
                                </div>

                                <!-- Quality & Physical Specifications -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-award me-1"></i> 2. Quality & Physical Specifications
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Processing Method</label>
                                            <select name="processing_method" id="processing_method" class="form-select form-select-sm" required>
                                                <option value="Fully Washed" selected>Fully Washed</option>
                                                <option value="Natural / Sun-Dried">Natural / Sun-Dried</option>
                                                <option value="Anaerobic Fermentation">Anaerobic Fermentation</option>
                                                <option value="Honey Processed">Honey Processed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Export Grade</label>
                                            <select name="grade" id="grade" class="form-select form-select-sm" required>
                                                <option value="Grade 1 Specialty">Grade 1 Specialty</option>
                                                <option value="Grade 2 Specialty" selected>Grade 2 Specialty</option>
                                                <option value="Grade 3 Commercial">Grade 3 Commercial</option>
                                                <option value="Grade 4 Commercial">Grade 4 Commercial</option>
                                                <option value="Grade 5 Commercial">Grade 5 Commercial</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Variety</label>
                                            <input type="text" name="variety" id="variety" class="form-control form-control-sm" value="74158 / Indigenous Heirloom">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Cupping Score (SCAA)</label>
                                            <input type="number" name="cupping_score" id="cupping_score" class="form-control form-control-sm" value="85.5" step="0.5" min="70" max="100">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Moisture Content</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="moisture_content" id="moisture_content" class="form-control" value="11.2" step="0.1" min="8" max="15">
                                                <span class="input-group-text bg-light text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Screen Size</label>
                                            <input type="text" name="screen_size" id="screen_size" class="form-control form-control-sm" value="Screen 14+ (85% min)">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Crop Harvest Year</label>
                                            <input type="text" name="crop_year" id="crop_year" class="form-control form-control-sm" value="2025/2026 Current Crop" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Sensory Cupping Notes</label>
                                            <input type="text" name="tasting_notes" id="tasting_notes" class="form-control form-control-sm" value="Floral bergamot, lemon zest, sweet peach, clean jasmine finish">
                                        </div>
                                    </div>
                                </div>

                                <!-- Commercial & Shipping Terms -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-ship me-1"></i> 3. Commercial & Delivery Terms
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Quantity of Bags</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="quantity_bags" id="quantity_bags" class="form-control" value="320" min="1" required>
                                                <span class="input-group-text bg-light text-muted">Bags (60kg)</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Offer Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-muted">$</span>
                                                <input type="number" name="price_usd_per_lb" id="price_usd_per_lb" class="form-control" value="3.25" step="0.01" min="0.01" required>
                                                <span class="input-group-text bg-light text-muted">/ lb</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Incoterms 2020</label>
                                            <select name="incoterm" id="incoterm" class="form-select form-select-sm" required>
                                                <option value="FOB Djibouti" selected>FOB Port of Djibouti</option>
                                                <option value="CIF Hamburg">CIF Hamburg</option>
                                                <option value="CIF Rotterdam">CIF Rotterdam</option>
                                                <option value="CIF Genoa">CIF Genoa</option>
                                                <option value="CIF Kobe / Yokohama">CIF Kobe / Yokohama</option>
                                                <option value="CIF New York">CIF New York</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Packaging Specification</label>
                                            <input type="text" name="bag_type" id="bag_type" class="form-control form-control-sm" value="60kg Export Jute Bags with inner GrainPro liner">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Payment Terms</label>
                                            <input type="text" name="payment_terms" id="payment_terms" class="form-control form-control-sm" value="100% Irrevocable Letter of Credit (LC) at Sight / CAD">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Offer Validity Date</label>
                                            <input type="date" name="validity_date" id="validity_date" class="form-control form-control-sm" value="{{ now()->addDays(14)->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Port of Loading</label>
                                            <input type="text" name="port_of_loading" id="port_of_loading" class="form-control form-control-sm" value="Port of Djibouti (Transit Corridor)">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                    <button type="submit" class="btn text-white px-4 py-2 rounded-pill fw-semibold" style="background: #6D1A95;">
                                        <i class="fa-solid fa-file-pdf me-1"></i> Generate & Save Official Offer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Live Offer Sheet Preview Card -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white position-sticky" style="top: 20px;">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <span class="badge px-3 py-2 rounded-pill" style="background: rgba(109,26,149,0.1); color: #6D1A95; font-weight: 700;">
                                    LIVE OFFER PREVIEW
                                </span>
                                <span class="text-muted small">Standard ECC Format</span>
                            </div>

                            <!-- Document Style Container -->
                            <div class="p-3 border rounded-3 bg-light" style="font-family: inherit;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0" id="prev_exporter">Fira Link Exporter PLC</h6>
                                        <small class="text-muted" id="prev_contact">exports@firalink.com</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-dark text-white font-monospace" style="font-size: 10px;">FL-EXP-SAMPLE</span>
                                        <div class="text-muted" style="font-size: 10px;">Validity: <span id="prev_validity">{{ now()->addDays(14)->format('M d, Y') }}</span></div>
                                    </div>
                                </div>

                                <div class="p-2 mb-3 rounded-2 text-white text-center" style="background: #6D1A95;">
                                    <h6 class="mb-0 fw-bold fs-6" id="prev_title">ETHIOPIAN COFFEE EXPORT OFFER</h6>
                                </div>

                                <div class="bg-white p-3 rounded-2 border mb-3">
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted py-1">Origin & Region:</td>
                                            <td class="fw-bold py-1 text-end" id="prev_origin">Sidama (Bensa)</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1">Grade & Process:</td>
                                            <td class="fw-bold py-1 text-end" id="prev_grade_process">Grade 2 (Fully Washed)</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1">Cupping Score:</td>
                                            <td class="fw-bold py-1 text-end text-success" id="prev_score">85.5 SCAA</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1">Quantity Lot:</td>
                                            <td class="fw-bold py-1 text-end" id="prev_lot">320 Bags (19,200 kg)</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1">Delivery Term:</td>
                                            <td class="fw-bold py-1 text-end" id="prev_incoterm">FOB Djibouti</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="text-dark fw-bold py-2">Offer Price:</td>
                                            <td class="fw-bold fs-6 py-2 text-end text-primary" id="prev_price">$3.25 / lb</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="p-2 rounded-2 bg-white border small text-muted mb-2">
                                    <strong>Sensory Notes:</strong> <span id="prev_notes">Floral bergamot, lemon zest, sweet peach, clean jasmine finish.</span>
                                </div>
                                <div class="text-muted" style="font-size: 10px; line-height: 1.4;">
                                    * Official commercial offer governed by standard European Contract for Coffee (ECC). Subject to ECTA export clearance and final sample approval.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Generated Offers History -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4">
                    <h5 class="fw-bold mb-3">Generated Offer Sheets Archive</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>REF NUMBER</th>
                                    <th>EXPORTER</th>
                                    <th>ORIGIN / GRADE</th>
                                    <th>PROCESS</th>
                                    <th>VOLUME</th>
                                    <th>PRICE</th>
                                    <th>VALID UNTIL</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($offers as $offer)
                                    <tr>
                                        <td class="fw-bold font-monospace">{{ $offer->reference_no }}</td>
                                        <td>{{ $offer->exporter_name }}</td>
                                        <td class="fw-semibold">{{ $offer->origin_region }} ({{ $offer->grade }})</td>
                                        <td>{{ $offer->processing_method }}</td>
                                        <td>{{ $offer->quantity_bags }} bags</td>
                                        <td class="fw-bold text-dark">${{ number_format($offer->price_usd_per_lb, 2) }}</td>
                                        <td>{{ $offer->validity_date->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('student.coffee-toolkit.offer-generator.pdf', $offer->id) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" target="_blank">
                                                <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No offers generated yet. Fill out the form above to generate your first professional offer sheet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function updatePreview() {
        const exporter = document.getElementById('exporter_name').value || 'Exporter PLC';
        const contact = document.getElementById('exporter_contact').value || '';
        const region = document.getElementById('origin_region').value;
        const subRegion = document.getElementById('sub_region').value;
        const process = document.getElementById('processing_method').value;
        const grade = document.getElementById('grade').value;
        const score = document.getElementById('cupping_score').value;
        const bags = document.getElementById('quantity_bags').value || 320;
        const price = document.getElementById('price_usd_per_lb').value || 3.25;
        const incoterm = document.getElementById('incoterm').value;
        const validity = document.getElementById('validity_date').value;
        const notes = document.getElementById('tasting_notes').value;

        document.getElementById('prev_exporter').innerText = exporter;
        document.getElementById('prev_contact').innerText = contact;
        document.getElementById('prev_origin').innerText = region + (subRegion ? ' (' + subRegion + ')' : '');
        document.getElementById('prev_grade_process').innerText = grade + ' (' + process + ')';
        document.getElementById('prev_score').innerText = score + ' SCAA';
        document.getElementById('prev_lot').innerText = bags + ' Bags (' + (bags * 60).toLocaleString() + ' kg)';
        document.getElementById('prev_incoterm').innerText = incoterm;
        document.getElementById('prev_price').innerText = '$' + parseFloat(price).toFixed(2) + ' / lb';
        document.getElementById('prev_validity').innerText = validity;
        document.getElementById('prev_notes').innerText = notes;
    }

    document.querySelectorAll('#offer-form input, #offer-form select').forEach(el => {
        el.addEventListener('input', updatePreview);
        el.addEventListener('change', updatePreview);
    });

    document.addEventListener('DOMContentLoaded', () => {
        updatePreview();
    });
</script>
@endpush
@endsection
