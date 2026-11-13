@extends('layouts.admin')

@section('title', $dataset->title . ' — Dataset Details')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.coffee-prices.index') }}">Market Intelligence</a></div>
        <div class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($dataset->title, 25) }}</div>
    </div>
@endsection

@section('main-content')
    <!-- Top Action & Meta Banner -->
    <div class="card mb-4 border-0 shadow-sm" style="border-left: 4px solid #6D1A95 !important;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge badge-light border text-dark font-weight-bold">{{ $dataset->version ?? 'v1.0' }}</span>
                        <span class="badge {{ $dataset->status === 'published' ? 'badge-success' : ($dataset->status === 'draft' ? 'badge-warning' : 'badge-secondary') }} text-uppercase" id="current-status-badge">
                            {{ $dataset->status }}
                        </span>
                    </div>
                    <h3 class="font-weight-bold text-dark mb-1">{{ $dataset->title }}</h3>
                    <div class="text-muted small">
                        <span><i class="fas fa-building mr-1 text-primary"></i> Source: <strong>{{ $dataset->source }}</strong></span>
                        <span class="mx-2">&bull;</span>
                        <span><i class="fas fa-calendar-alt mr-1 text-primary"></i> Effective: <strong>{{ $dataset->effective_date ? $dataset->effective_date->format('d M Y') : '—' }}</strong></span>
                        <span class="mx-2">&bull;</span>
                        <span><i class="fas fa-file-excel mr-1 text-success"></i> File: {{ $dataset->file_name }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <select id="select-dataset-status" class="form-control form-control-sm" style="width: auto; font-weight: 600;">
                        <option value="published" {{ $dataset->status === 'published' ? 'selected' : '' }}>Published (Active)</option>
                        <option value="draft" {{ $dataset->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="archived" {{ $dataset->status === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>

                    <button class="btn btn-outline-primary btn-sm" id="btn-rerun-ai" style="border-color: #6D1A95; color: #6D1A95;">
                        <i class="fas fa-sparkles mr-1"></i> Re-run Fira AI
                    </button>

                    <a href="{{ route('admin.coffee-prices.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Datasets
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 High-Precision Snapshot Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card card-statistic-1 mb-0">
                <div class="card-icon bg-success">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Highest Minimum Price</h4>
                    </div>
                    <div class="card-body">
                        ${{ number_format($snapshot['highest_price'] ?? 0, 3) }} <small class="text-muted" style="font-size: 11px;">/ lb</small>
                    </div>
                    <div class="px-3 pb-2 text-muted small" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $snapshot['highest_record']['coffee_type'] ?? 'Grade 1 Washed' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="card card-statistic-1 mb-0">
                <div class="card-icon bg-info">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Average Minimum</h4>
                    </div>
                    <div class="card-body">
                        ${{ number_format($snapshot['average_price'] ?? 0, 3) }} <small class="text-muted" style="font-size: 11px;">/ lb</small>
                    </div>
                    <div class="px-3 pb-2 text-muted small" style="font-size: 11px;">
                        Across {{ number_format($snapshot['records_count'] ?? 0) }} specifications
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="card card-statistic-1 mb-0">
                <div class="card-icon bg-warning">
                    <i class="fas fa-arrow-trend-down"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Lowest Floor Price</h4>
                    </div>
                    <div class="card-body">
                        ${{ number_format($snapshot['lowest_price'] ?? 0, 3) }} <small class="text-muted" style="font-size: 11px;">/ lb</small>
                    </div>
                    <div class="px-3 pb-2 text-muted small" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $snapshot['lowest_record']['coffee_type'] ?? 'Commercial Grade' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="card card-statistic-1 mb-0">
                <div class="card-icon" style="background: #6D1A95; color: #fff;">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Spread Corridor</h4>
                    </div>
                    <div class="card-body">
                        ${{ number_format(($snapshot['highest_price'] ?? 0) - ($snapshot['lowest_price'] ?? 0), 3) }}
                    </div>
                    <div class="px-3 pb-2 text-muted small" style="font-size: 11px;">
                        Min to Max Statutory Spread
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FIRA AI MARKET SYNTHESIS SECTION (Professional Financial Intelligence) -->
    <div class="card shadow-sm border-0 mb-4" id="ai-synthesis-card">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #FAF5FC 0%, #FFFFFF 100%); border-bottom: 1px solid #E8D5F2;">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-circle p-2" style="background: #6D1A95; color: #fff;">
                    <i class="fas fa-robot"></i>
                </span>
                <div>
                    <h5 class="font-weight-bold text-dark mb-0">Fira AI Market Synthesis &amp; Intelligence</h5>
                    <div class="text-muted small" style="font-size: 11.5px;">Institutional analysis generated from verified ECTA schedule data &bull; Zero fabricated numbers</div>
                </div>
            </div>
            <div>
                <span class="badge badge-light border text-muted small" id="ai-last-analyzed-badge">
                    {{ $dataset->ai_analyzed_at ? 'Updated ' . $dataset->ai_analyzed_at->diffForHumans() : 'Fresh Analysis' }}
                </span>
            </div>
        </div>
        <div class="card-body p-4" id="ai-synthesis-body">
            @php($ins = $synthesis['insights'] ?? [])
            <!-- Executive Summary -->
            <div class="p-3 rounded mb-4" style="background: #F8FAFC; border-left: 3px solid #6D1A95;">
                <div class="font-weight-bold text-uppercase small text-muted mb-1">EXECUTIVE SUMMARY</div>
                <div class="text-dark font-weight-500" style="line-height: 1.6;" id="ai-exec-summary">
                    {{ $ins['executive_summary'] ?? 'Official minimum export price floors establish certified reference levels for Ethiopian Arabica export registration.' }}
                </div>
            </div>

            <!-- Structured Analytical Insight Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-12">
                    <div class="p-3 border rounded bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="font-weight-bold small text-success text-uppercase"><i class="fas fa-crown mr-1"></i> Price Leader</span>
                            <span class="badge badge-success small">Top Reference</span>
                        </div>
                        <div class="text-dark small" style="line-height: 1.5; white-space: pre-line;" id="ai-price-leader">
                            {{ $ins['price_leader_analysis'] ?? 'Specialty Washed Grade 1 leads the export schedule.' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="p-3 border rounded bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="font-weight-bold small text-primary text-uppercase"><i class="fas fa-anchor mr-1"></i> Lowest Floor Reference</span>
                            <span class="badge badge-primary small">Statutory Baseline</span>
                        </div>
                        <div class="text-dark small" style="line-height: 1.5; white-space: pre-line;" id="ai-lowest-ref">
                            {{ $ins['lowest_reference_analysis'] ?? 'Commercial unwashed grades define the regulatory floor.' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="p-3 border rounded bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="font-weight-bold small text-info text-uppercase"><i class="fas fa-arrow-right-arrow-left mr-1"></i> Largest Revision / Movement</span>
                            <span class="badge badge-info small">Movement</span>
                        </div>
                        <div class="text-dark small" style="line-height: 1.5; white-space: pre-line;" id="ai-largest-mov">
                            {{ $ins['largest_movement_analysis'] ?? 'No previous comparable record available.' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="p-3 border rounded bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="font-weight-bold small text-dark text-uppercase"><i class="fas fa-network-wired mr-1"></i> Emerging Market Pattern</span>
                            <span class="badge badge-light border small">Trend</span>
                        </div>
                        <div class="text-dark small" style="line-height: 1.5;" id="ai-pattern">
                            {{ $ins['emerging_pattern'] ?? 'Specialty premiums remain elevated relative to commercial base Arabica.' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commercial Implication & Recommended Actions -->
            <div class="row g-3">
                <div class="col-md-6 col-12">
                    <div class="p-3 border rounded h-100" style="background: #FDFBFE;">
                        <h6 class="font-weight-bold text-dark mb-2 small text-uppercase">
                            <i class="fas fa-briefcase text-primary mr-1"></i> Commercial Implication
                        </h6>
                        <p class="text-muted small mb-0" style="line-height: 1.6;" id="ai-implication">
                            {{ $ins['commercial_implication'] ?? 'Exporters must audit buyer sales contracts to prevent regulatory re-negotiation or delayed export clearance.' }}
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="p-3 border rounded h-100" style="background: #F8FFF9;">
                        <h6 class="font-weight-bold text-dark mb-2 small text-uppercase">
                            <i class="fas fa-check-circle text-success mr-1"></i> Recommended Practical Actions
                        </h6>
                        <ul class="text-muted small mb-0 pl-3" style="line-height: 1.6;" id="ai-actions-list">
                            @forelse($ins['recommended_actions'] ?? [] as $act)
                                <li>{{ $act }}</li>
                            @empty
                                <li>Audit open export offers against latest minimum prices prior to buyer quotation signoff.</li>
                                <li>Calculate local cherry procurement margins using the integrated Fira Link Profit Calculator.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DATA TABLE: ALL NORMALIZED RECORDS IN THIS DATASET -->
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="font-weight-bold text-dark mb-0">Dataset Specification Table</h5>
                <div class="text-muted small">All normalized coffee varieties, regional origins, and price levels in this schedule</div>
            </div>
            <div>
                <span class="badge badge-light border px-3 py-2">
                    Showing {{ $records->total() }} Specifications
                </span>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.coffee-prices.show', $dataset->id) }}" class="row g-2 mb-3">
                <div class="col-md-3 col-12">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search spec, origin, grade..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 col-6">
                    <select name="origin" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="all">All Origins</option>
                        @foreach($originsList as $orig)
                            <option value="{{ $orig }}" {{ request('origin') === $orig ? 'selected' : '' }}>{{ $orig }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="grade" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="all">All Grades</option>
                        @foreach($gradesList as $grd)
                            <option value="{{ $grd }}" {{ request('grade') === $grd ? 'selected' : '' }}>{{ $grd }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="process" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="all">All Processing</option>
                        <option value="Fully Washed" {{ request('process') === 'Fully Washed' ? 'selected' : '' }}>Fully Washed</option>
                        <option value="Natural / Sun-Dried" {{ request('process') === 'Natural / Sun-Dried' ? 'selected' : '' }}>Natural / Sun-Dried</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="direction" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="all">All Movements</option>
                        <option value="increasing" {{ request('direction') === 'increasing' ? 'selected' : '' }}>Increasing (&uarr;)</option>
                        <option value="decreasing" {{ request('direction') === 'decreasing' ? 'selected' : '' }}>Decreasing (&darr;)</option>
                        <option value="stable" {{ request('direction') === 'stable' ? 'selected' : '' }}>Stable (&minus;)</option>
                        <option value="new" {{ request('direction') === 'new' ? 'selected' : '' }}>New / Baseline</option>
                    </select>
                </div>
                <div class="col-md-1 col-12">
                    <button type="submit" class="btn btn-primary btn-sm w-100" style="background: #6D1A95; border-color: #6D1A95;">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped align-middle mb-0" style="font-size: 13px;">
                    <thead class="thead-light">
                        <tr>
                            <th>Coffee Specification</th>
                            <th>Origin Region</th>
                            <th>Grade</th>
                            <th>Processing</th>
                            <th class="text-right">Minimum Price</th>
                            <th class="text-right">Previous Price</th>
                            <th class="text-center">Movement</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $rec)
                            <tr id="row-record-{{ $rec->id }}">
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $rec->coffee_type }}</div>
                                    @if($rec->notes)
                                        <div class="text-muted small" style="font-size: 11px;">{{ $rec->notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border text-dark">{{ $rec->origin }}</span>
                                </td>
                                <td>{{ $rec->grade }}</td>
                                <td>
                                    <span class="badge badge-light">{{ $rec->processing_method }}</span>
                                </td>
                                <td class="text-right font-weight-bold text-dark" style="font-size: 13.5px;">
                                    ${{ number_format($rec->minimum_price, 3) }} <small class="text-muted">{{ $rec->price_unit }}</small>
                                </td>
                                <td class="text-right text-muted">
                                    @if($rec->previous_price !== null && $rec->previous_price > 0)
                                        ${{ number_format($rec->previous_price, 3) }}
                                    @else
                                        <span class="text-muted small">No prior record</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($rec->price_change_direction === 'increasing')
                                        <span class="text-success font-weight-bold small">
                                            <i class="fas fa-arrow-up mr-1"></i> +${{ number_format($rec->price_change, 3) }} ({{ $rec->price_change_percentage }}%)
                                        </span>
                                    @elseif($rec->price_change_direction === 'decreasing')
                                        <span class="text-danger font-weight-bold small">
                                            <i class="fas fa-arrow-down mr-1"></i> -${{ number_format(abs($rec->price_change), 3) }} ({{ $rec->price_change_percentage }}%)
                                        </span>
                                    @elseif($rec->price_change_direction === 'stable')
                                        <span class="text-muted small">
                                            <i class="fas fa-minus mr-1"></i> Stable
                                        </span>
                                    @else
                                        <span class="badge badge-light border small text-muted">New Spec</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rec->status === 'valid')
                                        <span class="badge badge-success small">Valid</span>
                                    @else
                                        <span class="badge badge-warning small">Review</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-light py-0 px-2 btn-edit-record" 
                                            data-id="{{ $rec->id }}"
                                            data-coffee="{{ $rec->coffee_type }}"
                                            data-origin="{{ $rec->origin }}"
                                            data-grade="{{ $rec->grade }}"
                                            data-proc="{{ $rec->processing_method }}"
                                            data-price="{{ $rec->minimum_price }}"
                                            data-currency="{{ $rec->currency }}"
                                            data-unit="{{ $rec->price_unit }}"
                                            data-notes="{{ $rec->notes }}"
                                            title="Edit record">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light py-0 px-2 btn-delete-record text-danger" 
                                            data-id="{{ $rec->id }}"
                                            title="Delete record">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No records match the current filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $records->links() }}
            </div>
        </div>
    </div>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">Edit Price Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editRecordForm">
                    <input type="hidden" id="edit-record-id">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold small">Coffee Specification</label>
                            <input type="text" id="edit-coffee-type" class="form-control" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="font-weight-bold small">Origin Region</label>
                                <input type="text" id="edit-origin" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="font-weight-bold small">Grade</label>
                                <input type="text" id="edit-grade" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="font-weight-bold small">Processing Method</label>
                                <select id="edit-proc" class="form-control">
                                    <option value="Fully Washed">Fully Washed</option>
                                    <option value="Natural / Sun-Dried">Natural / Sun-Dried</option>
                                    <option value="Honey Processed">Honey Processed</option>
                                    <option value="Anaerobic Fermented">Anaerobic Fermented</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="font-weight-bold small">Minimum Price</label>
                                <input type="number" step="0.0001" id="edit-price" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Notes / Remarks</label>
                            <textarea id="edit-notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: #6D1A95; border-color: #6D1A95;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const datasetId = {{ $dataset->id }};

    // Toggle Dataset Status
    const statusSelect = document.getElementById('select-dataset-status');
    statusSelect.addEventListener('change', async function() {
        const newStatus = this.value;
        try {
            const res = await fetch(`/admin/coffee-prices/${datasetId}/toggle-status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });
            const data = await res.json();
            if (data.success) {
                const badge = document.getElementById('current-status-badge');
                badge.textContent = newStatus;
                badge.className = `badge ${newStatus === 'published' ? 'badge-success' : (newStatus === 'draft' ? 'badge-warning' : 'badge-secondary')} text-uppercase`;
            } else {
                alert(data.message || 'Failed to update status');
            }
        } catch(e) {
            alert('Error updating status');
        }
    });

    // Re-run AI Synthesis
    const btnRerunAi = document.getElementById('btn-rerun-ai');
    btnRerunAi.addEventListener('click', async function() {
        btnRerunAi.disabled = true;
        btnRerunAi.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Synthesizing...';

        try {
            const res = await fetch(`/admin/coffee-prices/${datasetId}/run-ai`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success && data.synthesis) {
                window.location.reload();
            } else {
                alert(data.message || 'Analysis failed');
                btnRerunAi.disabled = false;
                btnRerunAi.innerHTML = '<i class="fas fa-sparkles mr-1"></i> Re-run Fira AI';
            }
        } catch(e) {
            alert('Error running AI analysis: ' + e.message);
            btnRerunAi.disabled = false;
            btnRerunAi.innerHTML = '<i class="fas fa-sparkles mr-1"></i> Re-run Fira AI';
        }
    });

    // Edit Record Modal Handler
    document.querySelectorAll('.btn-edit-record').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit-record-id').value = this.dataset.id;
            document.getElementById('edit-coffee-type').value = this.dataset.coffee;
            document.getElementById('edit-origin').value = this.dataset.origin;
            document.getElementById('edit-grade').value = this.dataset.grade;
            document.getElementById('edit-proc').value = this.dataset.proc;
            document.getElementById('edit-price').value = this.dataset.price;
            document.getElementById('edit-notes').value = this.dataset.notes || '';
            $('#editRecordModal').modal('show');
        });
    });

    // Submit Edit Record
    document.getElementById('editRecordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const recordId = document.getElementById('edit-record-id').value;

        const payload = {
            coffee_type: document.getElementById('edit-coffee-type').value,
            origin: document.getElementById('edit-origin').value,
            grade: document.getElementById('edit-grade').value,
            processing_method: document.getElementById('edit-proc').value,
            minimum_price: parseFloat(document.getElementById('edit-price').value),
            currency: 'USD',
            price_unit: 'USD/lb',
            notes: document.getElementById('edit-notes').value,
        };

        try {
            const res = await fetch(`/admin/coffee-prices/${datasetId}/records/${recordId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                $('#editRecordModal').modal('hide');
                window.location.reload();
            } else {
                alert(data.message || 'Update failed');
            }
        } catch(err) {
            alert('Error updating record: ' + err.message);
        }
    });

    // Delete Record Handler
    document.querySelectorAll('.btn-delete-record').forEach(btn => {
        btn.addEventListener('click', async function() {
            const recordId = this.dataset.id;
            if (confirm('Delete this price record?')) {
                try {
                    const res = await fetch(`/admin/coffee-prices/${datasetId}/records/${recordId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        const row = document.getElementById(`row-record-${recordId}`);
                        if (row) row.remove();
                    } else {
                        alert(data.message || 'Delete failed');
                    }
                } catch(e) {
                    alert('Error deleting record');
                }
            }
        });
    });
});
</script>
@endpush
