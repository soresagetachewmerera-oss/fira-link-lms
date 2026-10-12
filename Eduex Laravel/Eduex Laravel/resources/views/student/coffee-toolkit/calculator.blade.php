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
                            <span class="text-dark small fw-semibold">Profit Calculator</span>
                        </div>
                        <h3 class="fw-bold mb-0" style="color: #1a0524;">Ethiopian Coffee Export Profit Calculator</h3>
                        <p class="text-muted small mb-0">Deterministic financial modeling for full container loads (FCL), corridor logistics, and FOB/CIF margins.</p>
                    </div>
                    <div class="d-flex gap-2 mt-3 mt-md-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="resetDefaults()">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset Standard 1 FCL
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row g-4">
                    <!-- Calculator Inputs Form -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                            <form id="calc-form" method="POST" action="{{ route('student.coffee-toolkit.calculator.store') }}">
                                @csrf

                                <!-- Section 1: Deal Profile & Volume -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-boxes-stacked me-1"></i> 1. Deal Profile & Volume
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label small fw-semibold">Deal Title / Reference</label>
                                            <input type="text" name="deal_title" id="deal_title" class="form-control form-control-sm" value="Sidama Washed G2 1-FCL Export" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">Coffee Origin & Grade</label>
                                            <select name="coffee_type" id="coffee_type" class="form-select form-select-sm">
                                                <option value="Sidama Washed Grade 2" selected>Sidama Washed Grade 2</option>
                                                <option value="Yirgacheffe Washed Grade 1">Yirgacheffe Washed Grade 1</option>
                                                <option value="Guji Natural Grade 1">Guji Natural Grade 1</option>
                                                <option value="Limu Washed Grade 2">Limu Washed Grade 2</option>
                                                <option value="Djimmah Commercial Grade 5">Djimmah Commercial Grade 5</option>
                                                <option value="Harrar Longberry Natural">Harrar Longberry Natural</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Quantity of Bags (60 kg Net)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="quantity_bags" id="quantity_bags" class="form-control" value="320" min="1" step="1" required>
                                                <span class="input-group-text bg-light text-muted">Bags</span>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Standard 20ft FCL = 320 to 350 bags</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Bag Net Weight</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="bag_weight_kg" id="bag_weight_kg" class="form-control" value="60" readonly>
                                                <span class="input-group-text bg-light text-muted">kg / bag</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 2: Commercial Selling Terms -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-money-bill-transfer me-1"></i> 2. Commercial Pricing & Exchange Rate
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Contract Selling Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-muted">$</span>
                                                <input type="number" name="selling_price_usd_per_lb" id="selling_price_usd_per_lb" class="form-control" value="3.15" step="0.01" min="0.01" required>
                                                <span class="input-group-text bg-light text-muted">/ lb</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Incoterm</label>
                                            <select name="incoterm" id="incoterm" class="form-select form-select-sm" onchange="toggleIncoterm()">
                                                <option value="FOB" selected>FOB (Port of Djibouti)</option>
                                                <option value="CIF">CIF (Destination Port)</option>
                                                <option value="CFR">CFR (Cost & Freight)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Commercial Bank Rate</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" value="142.50" step="0.1" min="1" required>
                                                <span class="input-group-text bg-light text-muted">ETB / $</span>
                                            </div>
                                        </div>
                                        <div class="col-md-12" id="freight-group" style="display: none;">
                                            <label class="form-label small fw-semibold">Ocean Freight & Marine Insurance (USD)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-muted">$</span>
                                                <input type="number" name="freight_insurance_usd" id="freight_insurance_usd" class="form-control" value="3200.00" step="10">
                                                <span class="input-group-text bg-light text-muted">USD per 20ft FCL</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 3: Ethiopian Sourcing & Production Costs -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase small pb-2 border-bottom" style="color: #6D1A95; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-seedling me-1"></i> 3. Local Sourcing & Operational Costs (ETB)
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Green Coffee Sourcing / Purchase</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="purchase_price_etb_per_kg" id="purchase_price_etb_per_kg" class="form-control" value="340.00" step="1" required>
                                                <span class="input-group-text bg-light text-muted">ETB / kg</span>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Vertical trade / ECX reference base</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Milling, Cleaning & Grading</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="processing_cost_etb_per_bag" id="processing_cost_etb_per_bag" class="form-control" value="180.00" step="5">
                                                <span class="input-group-text bg-light text-muted">ETB / bag</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Packaging (Jute + GrainPro)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="packaging_cost_etb_per_bag" id="packaging_cost_etb_per_bag" class="form-control" value="450.00" step="10">
                                                <span class="input-group-text bg-light text-muted">ETB / bag</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Corridor Transit (Addis ➔ Djibouti)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="transport_corridor_etb" id="transport_corridor_etb" class="form-control" value="380000.00" step="1000">
                                                <span class="input-group-text bg-light text-muted">ETB / FCL</span>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label small fw-semibold">ECTA Inspection, Port Clearance & Handling</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="handling_customs_etb" id="handling_customs_etb" class="form-control" value="165000.00" step="1000">
                                                <span class="input-group-text bg-light text-muted">ETB total</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                    <button type="submit" class="btn text-white px-4 py-2 rounded-pill fw-semibold" style="background: #6D1A95;">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Deal to Workspace
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Real-Time Calculation Results -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 p-4 text-white mb-4" 
                             style="background: linear-gradient(135deg, #4A0E66 0%, #6D1A95 100%);">
                            <span class="text-white-50 text-uppercase small fw-bold" style="letter-spacing: 0.5px;">ESTIMATED NET MARGIN</span>
                            <div class="d-flex align-items-baseline gap-2 my-2">
                                <h2 class="text-white fw-bold mb-0" id="res_margin">0.0%</h2>
                                <span class="badge bg-success px-2 py-1" id="res_profit_status">Profitable</span>
                            </div>
                            <div class="small text-white-50">
                                Net Profit: <span class="text-white fw-bold" id="res_net_profit_etb">0 ETB</span> (<span id="res_net_profit_usd">$0</span>)
                            </div>
                        </div>

                        <!-- Mathematical Metrics Summary -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                            <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">Executive Deal Metrics</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-muted small">Net Weight (kg)</div>
                                    <div class="fw-bold fs-6" id="res_net_kg">19,200 kg</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Net Weight (lbs)</div>
                                    <div class="fw-bold fs-6" id="res_net_lbs">42,328.7 lbs</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Gross Revenue (USD)</div>
                                    <div class="fw-bold fs-6 text-success" id="res_gross_usd">$0.00</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Gross Revenue (ETB)</div>
                                    <div class="fw-bold fs-6 text-success" id="res_gross_etb">0 ETB</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Total Costs (ETB)</div>
                                    <div class="fw-bold fs-6 text-danger" id="res_total_cost_etb">0 ETB</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Break-Even FOB Price</div>
                                    <div class="fw-bold fs-6 text-primary" id="res_breakeven_usd">$0.00 / lb</div>
                                </div>
                            </div>
                        </div>

                        <!-- Itemized Cost Breakdown Card -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                            <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">Itemized Cost Structure</h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">1. Green Coffee Procurement</span>
                                    <span class="fw-semibold" id="res_cost_purchase">0 ETB</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">2. Processing, Cleaning & Milling</span>
                                    <span class="fw-semibold" id="res_cost_processing">0 ETB</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">3. GrainPro Liners & Jute Bags</span>
                                    <span class="fw-semibold" id="res_cost_packaging">0 ETB</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">4. Corridor Transit (Djibouti)</span>
                                    <span class="fw-semibold" id="res_cost_transport">0 ETB</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">5. Inspection, Port Clearance & Handling</span>
                                    <span class="fw-semibold" id="res_cost_handling">0 ETB</span>
                                </div>
                                <div class="d-flex justify-content-between" id="row_freight" style="display: none;">
                                    <span class="text-muted">6. Ocean Freight & Marine Insurance</span>
                                    <span class="fw-semibold" id="res_cost_freight">0 ETB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saved Calculations History -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4">
                    <h5 class="fw-bold mb-3">Saved Deal Calculations Workspace</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>DATE</th>
                                    <th>DEAL TITLE</th>
                                    <th>COFFEE TYPE</th>
                                    <th>BAGS</th>
                                    <th>PRICE ($/LB)</th>
                                    <th>MARGIN</th>
                                    <th>NET PROFIT (ETB)</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($calculations as $item)
                                    <tr>
                                        <td>{{ $item->created_at->format('M d, Y') }}</td>
                                        <td class="fw-semibold">{{ $item->deal_title }}</td>
                                        <td>{{ $item->coffee_type }}</td>
                                        <td>{{ $item->quantity_bags }} ({{ number_format($item->net_weight_kg) }} kg)</td>
                                        <td class="fw-bold">${{ number_format($item->selling_price_usd_per_lb, 2) }}</td>
                                        <td>
                                            <span class="badge {{ $item->margin_percentage >= 10 ? 'bg-success' : ($item->margin_percentage > 0 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                                {{ $item->margin_percentage }}%
                                            </span>
                                        </td>
                                        <td class="fw-bold {{ $item->net_profit_etb >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($item->net_profit_etb, 2) }} ETB
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No calculations saved yet. Configure your deal above and click "Save Deal".</td>
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
    const KG_TO_LBS = 2.20462262;

    function toggleIncoterm() {
        const incoterm = document.getElementById('incoterm').value;
        const freightGroup = document.getElementById('freight-group');
        const rowFreight = document.getElementById('row_freight');
        if (incoterm === 'CIF' || incoterm === 'CFR') {
            freightGroup.style.display = 'block';
            rowFreight.style.display = 'flex';
        } else {
            freightGroup.style.display = 'none';
            rowFreight.style.display = 'none';
        }
        recalculate();
    }

    function recalculate() {
        const bags = parseFloat(document.getElementById('quantity_bags').value) || 0;
        const bagWeightKg = parseFloat(document.getElementById('bag_weight_kg').value) || 60;
        const priceUsdPerLb = parseFloat(document.getElementById('selling_price_usd_per_lb').value) || 0;
        const exchangeRate = parseFloat(document.getElementById('exchange_rate').value) || 1;
        const incoterm = document.getElementById('incoterm').value;
        const freightUsd = (incoterm === 'CIF' || incoterm === 'CFR') ? (parseFloat(document.getElementById('freight_insurance_usd').value) || 0) : 0;

        const purchasePriceEtbPerKg = parseFloat(document.getElementById('purchase_price_etb_per_kg').value) || 0;
        const processingPerBag = parseFloat(document.getElementById('processing_cost_etb_per_bag').value) || 0;
        const packagingPerBag = parseFloat(document.getElementById('packaging_cost_etb_per_bag').value) || 0;
        const transportCorridorEtb = parseFloat(document.getElementById('transport_corridor_etb').value) || 0;
        const handlingCustomsEtb = parseFloat(document.getElementById('handling_customs_etb').value) || 0;

        const netWeightKg = bags * bagWeightKg;
        const netWeightLbs = netWeightKg * KG_TO_LBS;

        // Costs in ETB
        const purchaseCostEtb = netWeightKg * purchasePriceEtbPerKg;
        const processingCostEtb = bags * processingPerBag;
        const packagingCostEtb = bags * packagingPerBag;
        const freightEtb = freightUsd * exchangeRate;

        const totalCostEtb = purchaseCostEtb + processingCostEtb + packagingCostEtb + transportCorridorEtb + handlingCustomsEtb + freightEtb;
        const totalCostUsd = exchangeRate > 0 ? (totalCostEtb / exchangeRate) : 0;

        // Revenue
        const grossRevenueUsd = netWeightLbs * priceUsdPerLb;
        const grossRevenueEtb = grossRevenueUsd * exchangeRate;

        // Profit
        const netProfitEtb = grossRevenueEtb - totalCostEtb;
        const netProfitUsd = grossRevenueUsd - totalCostUsd;
        const marginPct = grossRevenueEtb > 0 ? ((netProfitEtb / grossRevenueEtb) * 100) : 0;

        // Break even
        const breakEvenUsd = (netWeightLbs > 0 && exchangeRate > 0) ? (totalCostEtb / (netWeightLbs * exchangeRate)) : 0;

        // Render UI
        document.getElementById('res_net_kg').innerText = netWeightKg.toLocaleString(undefined, {maximumFractionDigits: 0}) + ' kg';
        document.getElementById('res_net_lbs').innerText = netWeightLbs.toLocaleString(undefined, {maximumFractionDigits: 1}) + ' lbs';
        document.getElementById('res_gross_usd').innerText = '$' + grossRevenueUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('res_gross_etb').innerText = grossRevenueEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_total_cost_etb').innerText = totalCostEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_breakeven_usd').innerText = '$' + breakEvenUsd.toFixed(4) + ' / lb';

        document.getElementById('res_margin').innerText = marginPct.toFixed(2) + '%';
        document.getElementById('res_net_profit_etb').innerText = (netProfitEtb >= 0 ? '+' : '') + netProfitEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_net_profit_usd').innerText = '$' + netProfitUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        const statusBadge = document.getElementById('res_profit_status');
        if (netProfitEtb >= 0) {
            statusBadge.className = 'badge bg-success px-2 py-1';
            statusBadge.innerText = 'Profitable Deal';
        } else {
            statusBadge.className = 'badge bg-danger px-2 py-1';
            statusBadge.innerText = 'Loss Scenario';
        }

        document.getElementById('res_cost_purchase').innerText = purchaseCostEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_cost_processing').innerText = processingCostEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_cost_packaging').innerText = packagingCostEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_cost_transport').innerText = transportCorridorEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_cost_handling').innerText = handlingCustomsEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
        document.getElementById('res_cost_freight').innerText = freightEtb.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ETB';
    }

    function resetDefaults() {
        document.getElementById('quantity_bags').value = '320';
        document.getElementById('selling_price_usd_per_lb').value = '3.15';
        document.getElementById('exchange_rate').value = '142.50';
        document.getElementById('purchase_price_etb_per_kg').value = '340.00';
        document.getElementById('processing_cost_etb_per_bag').value = '180.00';
        document.getElementById('packaging_cost_etb_per_bag').value = '450.00';
        document.getElementById('transport_corridor_etb').value = '380000.00';
        document.getElementById('handling_customs_etb').value = '165000.00';
        document.getElementById('incoterm').value = 'FOB';
        toggleIncoterm();
    }

    // Bind event listeners for real time updates
    document.querySelectorAll('#calc-form input, #calc-form select').forEach(el => {
        el.addEventListener('input', recalculate);
        el.addEventListener('change', recalculate);
    });

    document.addEventListener('DOMContentLoaded', () => {
        recalculate();
    });
</script>
@endpush
@endsection
