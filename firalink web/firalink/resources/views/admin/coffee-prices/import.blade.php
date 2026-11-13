@extends('layouts.admin')

@section('title', 'Import Coffee Minimum Prices — Market Intelligence')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.coffee-prices.index') }}">Market Intelligence</a></div>
        <div class="breadcrumb-item active">Import Excel</div>
    </div>
@endsection

@push('styles')
<style>
.wizard-steps-bar {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin-bottom: 30px;
    padding: 0 10px;
}
.wizard-steps-bar::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 40px;
    right: 40px;
    height: 3px;
    background: #E5E7EB;
    z-index: 1;
}
.wizard-step-node {
    position: relative;
    z-index: 2;
    text-align: center;
    background: #fff;
    padding: 0 10px;
    cursor: default;
}
.wizard-step-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #F3F4F6;
    color: #9CA3AF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin: 0 auto 8px auto;
    border: 2px solid #E5E7EB;
    transition: all 0.25s ease;
}
.wizard-step-title {
    font-size: 12px;
    font-weight: 600;
    color: #6B7280;
    white-space: nowrap;
}
.wizard-step-node.active .wizard-step-icon {
    background: #6D1A95;
    color: #fff;
    border-color: #6D1A95;
    box-shadow: 0 0 0 4px rgba(109, 26, 149, 0.2);
}
.wizard-step-node.active .wizard-step-title {
    color: #6D1A95;
}
.wizard-step-node.completed .wizard-step-icon {
    background: #10B981;
    color: #fff;
    border-color: #10B981;
}
.wizard-step-node.completed .wizard-step-title {
    color: #10B981;
}
.dropzone-upload-area {
    border: 2px dashed #D1D5DB;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    background: #FAFAFC;
    cursor: pointer;
    transition: all 0.2s ease;
}
.dropzone-upload-area:hover, .dropzone-upload-area.dragover {
    border-color: #6D1A95;
    background: #F4ECF8;
}
.mapping-card {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    transition: border-color 0.2s ease;
}
.mapping-card:hover {
    border-color: #6D1A95;
}
.summary-metric-card {
    border-radius: 10px;
    padding: 18px;
    text-align: center;
}
</style>
@endpush

@section('main-content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-11">

        <!-- Wizard Step Bar -->
        <div class="wizard-steps-bar">
            <div class="wizard-step-node active" id="step-node-1">
                <div class="wizard-step-icon"><i class="fas fa-file-upload"></i></div>
                <div class="wizard-step-title">1. Upload Excel</div>
            </div>
            <div class="wizard-step-node" id="step-node-2">
                <div class="wizard-step-icon"><i class="fas fa-table-columns"></i></div>
                <div class="wizard-step-title">2. Map Columns</div>
            </div>
            <div class="wizard-step-node" id="step-node-3">
                <div class="wizard-step-icon"><i class="fas fa-shield-halved"></i></div>
                <div class="wizard-step-title">3. Validate Data</div>
            </div>
            <div class="wizard-step-node" id="step-node-4">
                <div class="wizard-step-icon"><i class="fas fa-check-double"></i></div>
                <div class="wizard-step-title">4. Confirm &amp; Import</div>
            </div>
        </div>

        <!-- Wizard Container -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">

                <!-- ========================================================
                     STAGE 1: FILE UPLOAD
                     ======================================================== -->
                <div id="wizard-stage-1">
                    <div class="text-center mb-4">
                        <h4 class="font-weight-bold text-dark mb-1">Import Coffee Minimum Price Schedule</h4>
                        <p class="text-muted small">Upload official ECTA Excel files (.xlsx, .xls) or market CSV sheets. Perfect formatting is not required; the parser normalizes mixed columns automatically.</p>
                    </div>

                    <form id="upload-form" enctype="multipart/form-data">
                        @csrf
                        <div class="dropzone-upload-area" id="dropzone-area">
                            <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                            <h5 class="mb-1 text-dark">Click to browse or drag &amp; drop your spreadsheet</h5>
                            <p class="text-muted small mb-3">Supports .xlsx, .xls, and .csv files up to 10MB</p>
                            <input type="file" id="excel-file-input" name="excel_file" accept=".xlsx,.xls,.csv" style="display: none;">
                            <button type="button" class="btn btn-outline-primary px-4" id="btn-browse-file" style="border-color: #6D1A95; color: #6D1A95;">
                                <i class="fas fa-folder-open mr-1"></i> Choose File
                            </button>
                        </div>

                        <div id="selected-file-info" class="mt-3 p-3 bg-light rounded" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-excel fa-2x text-success mr-3"></i>
                                    <div>
                                        <div class="font-weight-bold text-dark" id="display-filename">file.xlsx</div>
                                        <div class="text-muted small" id="display-filesize">0 KB</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger" id="btn-remove-file">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.coffee-prices.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Datasets
                            </a>
                            <button type="button" class="btn btn-primary px-4" id="btn-proceed-preview" disabled style="background: #6D1A95; border-color: #6D1A95;">
                                Read Spreadsheet <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================================
                     STAGE 2: COLUMN MAPPING & METADATA
                     ======================================================== -->
                <div id="wizard-stage-2" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">Confirm Detected Column Mapping</h5>
                            <p class="text-muted small mb-0">Review the auto-detected fields below. You can re-assign or skip any column.</p>
                        </div>
                        <span class="badge badge-light border text-muted px-3 py-2" id="preview-row-count-badge">
                            0 data rows detected
                        </span>
                    </div>

                    <!-- Dataset Metadata Config -->
                    <div class="row g-3 p-3 mb-4 bg-light rounded">
                        <div class="col-md-4">
                            <label class="form-label font-weight-bold small">Dataset Title</label>
                            <input type="text" id="dataset-title-input" class="form-control" value="Coffee Minimum Export Reference Prices">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold small">Official Source</label>
                            <input type="text" id="dataset-source-input" class="form-control" value="ECTA (Ethiopian Coffee &amp; Tea Authority)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold small">Effective Date</label>
                            <input type="date" id="dataset-date-input" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label font-weight-bold small">Version Tag</label>
                            <input type="text" id="dataset-version-input" class="form-control" value="v{{ date('Y.m.d') }}">
                        </div>
                    </div>

                    <!-- Column Mapping Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm align-middle" id="mapping-table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 28%;">Spreadsheet Header</th>
                                    <th style="width: 38%;">Sample Values in Sheet</th>
                                    <th style="width: 34%;">Map to Internal Field</th>
                                </tr>
                            </thead>
                            <tbody id="mapping-table-body">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <button type="button" class="btn btn-light" id="btn-back-to-step1">
                            <i class="fas fa-arrow-left mr-1"></i> Upload Different File
                        </button>
                        <button type="button" class="btn btn-primary px-4" id="btn-proceed-validate" style="background: #6D1A95; border-color: #6D1A95;">
                            Validate Dataset <i class="fas fa-shield-alt ml-1"></i>
                        </button>
                    </div>
                </div>

                <!-- ========================================================
                     STAGE 3: VALIDATION SUMMARY & PREVIEW
                     ======================================================== -->
                <div id="wizard-stage-3" style="display: none;">
                    <div class="text-center mb-4">
                        <h4 class="font-weight-bold text-dark mb-1">Dataset Validation Complete</h4>
                        <p class="text-muted small">Our parser validated prices, dates, origins, and grades according to ECTA export standards.</p>
                    </div>

                    <!-- 4 Summary Metric Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="summary-metric-card bg-light border">
                                <div class="text-muted small font-weight-bold">TOTAL DETECTED</div>
                                <h3 class="font-weight-bold text-dark my-1" id="metric-total">0</h3>
                                <small class="text-muted">Rows parsed</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="summary-metric-card border" style="background: #ECFDF5; border-color: #A7F3D0 !important;">
                                <div class="text-success small font-weight-bold">VALID RECORDS</div>
                                <h3 class="font-weight-bold text-success my-1" id="metric-valid">0</h3>
                                <small class="text-success">Ready for publish</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="summary-metric-card border" style="background: #FFFBEB; border-color: #FDE68A !important;">
                                <div class="text-warning small font-weight-bold">DUPLICATES</div>
                                <h3 class="font-weight-bold text-warning my-1" id="metric-duplicates">0</h3>
                                <small class="text-muted">Skipped safely</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="summary-metric-card border" style="background: #FEF2F2; border-color: #FECACA !important;">
                                <div class="text-danger small font-weight-bold">REQUIRES REVIEW</div>
                                <h3 class="font-weight-bold text-danger my-1" id="metric-review">0</h3>
                                <small class="text-muted">Missing price/origin</small>
                            </div>
                        </div>
                    </div>

                    <!-- Preview of Normalized Valid Records -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold small text-dark"><i class="fas fa-check-circle text-success mr-1"></i> Preview of Normalized Valid Records (Top 10)</span>
                            <span class="badge badge-success" id="valid-badge-count">0 Valid</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0" style="font-size: 12.5px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Coffee Spec</th>
                                        <th>Origin Region</th>
                                        <th>Grade</th>
                                        <th>Processing</th>
                                        <th class="text-right">Minimum Price</th>
                                        <th>Currency</th>
                                        <th>Effective Date</th>
                                    </tr>
                                </thead>
                                <tbody id="valid-preview-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Publishing Configuration Option -->
                    <div class="p-3 bg-light rounded mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="font-weight-bold text-dark">Publish Dataset Status</div>
                            <div class="text-muted small">Published datasets immediately appear in Free Tools &rarr; Market Intelligence with Fira AI analysis.</div>
                        </div>
                        <div>
                            <select id="import-publish-status" class="form-control" style="width: auto; font-weight: 600;">
                                <option value="published" selected>Publish Immediately</option>
                                <option value="draft">Save as Draft</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <button type="button" class="btn btn-light" id="btn-back-to-step2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Mapping
                        </button>
                        <button type="button" class="btn btn-success px-5 py-2 font-weight-bold" id="btn-execute-import" style="background: #10B981; border-color: #10B981;">
                            <i class="fas fa-file-import mr-1"></i> Import Valid Records
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let uploadedTempPath = null;
    let detectedHeaders = {};
    let detectedMapping = {};
    let internalFields = {};
    let previewRows = [];
    let headerRowIndex = 0;

    const fileInput = document.getElementById('excel-file-input');
    const dropzone = document.getElementById('dropzone-area');
    const btnBrowse = document.getElementById('btn-browse-file');
    const btnProceedPreview = document.getElementById('btn-proceed-preview');
    const selectedFileInfo = document.getElementById('selected-file-info');
    const displayFilename = document.getElementById('display-filename');
    const displayFilesize = document.getElementById('display-filesize');
    const btnRemoveFile = document.getElementById('btn-remove-file');

    // Drag and drop handlers
    btnBrowse.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('click', (e) => {
        if (e.target !== btnBrowse) fileInput.click();
    });

    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer.files && e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelected();
        }
    });

    fileInput.addEventListener('change', handleFileSelected);

    function handleFileSelected() {
        const file = fileInput.files[0];
        if (!file) return;

        displayFilename.textContent = file.name;
        displayFilesize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        selectedFileInfo.style.display = 'block';
        dropzone.style.display = 'none';
        btnProceedPreview.disabled = false;
    }

    btnRemoveFile.addEventListener('click', () => {
        fileInput.value = '';
        selectedFileInfo.style.display = 'none';
        dropzone.style.display = 'block';
        btnProceedPreview.disabled = true;
    });

    // ========================================================
    // STEP 1 -> STEP 2: PREVIEW UPLOAD
    // ========================================================
    btnProceedPreview.addEventListener('click', async function() {
        const file = fileInput.files[0];
        if (!file) return;

        btnProceedPreview.disabled = true;
        btnProceedPreview.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Reading Spreadsheet...';

        const formData = new FormData();
        formData.append('excel_file', file);
        formData.append('_token', '{{ csrf_token() }}');

        try {
            const res = await fetch('{{ route("admin.coffee-prices.preview") }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
            });
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Failed to inspect spreadsheet.');
                btnProceedPreview.disabled = false;
                btnProceedPreview.innerHTML = 'Read Spreadsheet <i class="fas fa-arrow-right ml-1"></i>';
                return;
            }

            uploadedTempPath = data.temp_path;
            detectedHeaders = data.headers;
            detectedMapping = data.detected_mapping;
            internalFields = data.internal_fields;
            previewRows = data.preview_rows;
            headerRowIndex = data.header_row_index;

            document.getElementById('preview-row-count-badge').textContent = data.total_data_rows + ' data rows detected';

            buildMappingTable();

            // Transition stage
            document.getElementById('wizard-stage-1').style.display = 'none';
            document.getElementById('wizard-stage-2').style.display = 'block';
            document.getElementById('step-node-1').classList.add('completed');
            document.getElementById('step-node-1').classList.remove('active');
            document.getElementById('step-node-2').classList.add('active');

        } catch (err) {
            alert('Upload error: ' + err.message);
        } finally {
            btnProceedPreview.disabled = false;
            btnProceedPreview.innerHTML = 'Read Spreadsheet <i class="fas fa-arrow-right ml-1"></i>';
        }
    });

    function buildMappingTable() {
        const tbody = document.getElementById('mapping-table-body');
        tbody.innerHTML = '';

        for (const [colIdx, headerName] of Object.entries(detectedHeaders)) {
            const mappedField = detectedMapping[colIdx] || 'skip';

            // Gather sample values for this column from preview rows
            const samples = previewRows.map(r => r[headerName]).filter(v => v !== null && v !== '').slice(0, 3).join(', ');

            let optionsHtml = '<option value="skip">-- Skip this Column --</option>';
            for (const [fKey, fLabel] of Object.entries(internalFields)) {
                const selected = mappedField === fKey ? 'selected' : '';
                optionsHtml += `<option value="${fKey}" ${selected}>${fLabel}</option>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="font-weight-bold text-dark">${escapeHtml(headerName)}</div>
                    <div class="text-muted small">Col #${parseInt(colIdx) + 1}</div>
                </td>
                <td class="text-muted small" style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${samples ? escapeHtml(samples) : '<em>Empty / No sample</em>'}
                </td>
                <td>
                    <select class="form-control form-control-sm column-map-select" data-col="${colIdx}" style="font-weight: 500;">
                        ${optionsHtml}
                    </select>
                </td>
            `;
            tbody.appendChild(tr);
        }
    }

    // Step 2 Back
    document.getElementById('btn-back-to-step1').addEventListener('click', function() {
        document.getElementById('wizard-stage-2').style.display = 'none';
        document.getElementById('wizard-stage-1').style.display = 'block';
        document.getElementById('step-node-2').classList.remove('active');
        document.getElementById('step-node-1').classList.remove('completed');
        document.getElementById('step-node-1').classList.add('active');
    });

    // ========================================================
    // STEP 2 -> STEP 3: VALIDATE MAPPING
    // ========================================================
    const btnProceedValidate = document.getElementById('btn-proceed-validate');
    btnProceedValidate.addEventListener('click', async function() {
        // Collect current mappings
        const mapping = {};
        document.querySelectorAll('.column-map-select').forEach(sel => {
            mapping[sel.dataset.col] = sel.value;
        });

        btnProceedValidate.disabled = true;
        btnProceedValidate.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Validating Data...';

        try {
            const res = await fetch('{{ route("admin.coffee-prices.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    temp_path: uploadedTempPath,
                    column_mapping: mapping,
                    header_row_index: headerRowIndex,
                    default_effective_date: document.getElementById('dataset-date-input').value,
                })
            });
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Validation error.');
                btnProceedValidate.disabled = false;
                btnProceedValidate.innerHTML = 'Validate Dataset <i class="fas fa-shield-alt ml-1"></i>';
                return;
            }

            // Populate summary metrics
            document.getElementById('metric-total').textContent = Number(data.summary.total_detected).toLocaleString();
            document.getElementById('metric-valid').textContent = Number(data.summary.valid_count).toLocaleString();
            document.getElementById('metric-duplicates').textContent = Number(data.summary.duplicate_count).toLocaleString();
            document.getElementById('metric-review').textContent = Number(data.summary.review_count).toLocaleString();
            document.getElementById('valid-badge-count').textContent = data.summary.valid_count + ' Valid';

            // Populate preview table of valid records
            const previewTbody = document.getElementById('valid-preview-table-body');
            previewTbody.innerHTML = '';
            (data.valid_sample || []).forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="font-weight-bold text-dark">${escapeHtml(r.coffee_type || '—')}</td>
                    <td><span class="badge badge-light border">${escapeHtml(r.origin || '—')}</span></td>
                    <td>${escapeHtml(r.grade || '—')}</td>
                    <td>${escapeHtml(r.processing_method || '—')}</td>
                    <td class="text-right font-weight-bold text-dark">$${Number(r.minimum_price || 0).toFixed(3)}</td>
                    <td>${escapeHtml(r.currency || 'USD')}</td>
                    <td class="text-muted">${escapeHtml(r.effective_date || '—')}</td>
                `;
                previewTbody.appendChild(tr);
            });

            // Transition stage
            document.getElementById('wizard-stage-2').style.display = 'none';
            document.getElementById('wizard-stage-3').style.display = 'block';
            document.getElementById('step-node-2').classList.add('completed');
            document.getElementById('step-node-2').classList.remove('active');
            document.getElementById('step-node-3').classList.add('active');

        } catch (err) {
            alert('Validation error: ' + err.message);
        } finally {
            btnProceedValidate.disabled = false;
            btnProceedValidate.innerHTML = 'Validate Dataset <i class="fas fa-shield-alt ml-1"></i>';
        }
    });

    // Step 3 Back
    document.getElementById('btn-back-to-step2').addEventListener('click', function() {
        document.getElementById('wizard-stage-3').style.display = 'none';
        document.getElementById('wizard-stage-2').style.display = 'block';
        document.getElementById('step-node-3').classList.remove('active');
        document.getElementById('step-node-2').classList.remove('completed');
        document.getElementById('step-node-2').classList.add('active');
    });

    // ========================================================
    // STEP 3 -> EXECUTE IMPORT
    // ========================================================
    const btnExecuteImport = document.getElementById('btn-execute-import');
    btnExecuteImport.addEventListener('click', async function() {
        const mapping = {};
        document.querySelectorAll('.column-map-select').forEach(sel => {
            mapping[sel.dataset.col] = sel.value;
        });

        btnExecuteImport.disabled = true;
        btnExecuteImport.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Importing &amp; Synthesizing with Fira AI...';

        try {
            const res = await fetch('{{ route("admin.coffee-prices.execute") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    temp_path: uploadedTempPath,
                    title: document.getElementById('dataset-title-input').value,
                    source: document.getElementById('dataset-source-input').value,
                    effective_date: document.getElementById('dataset-date-input').value,
                    version: document.getElementById('dataset-version-input').value,
                    column_mapping: mapping,
                    header_row_index: headerRowIndex,
                    status: document.getElementById('import-publish-status').value,
                })
            });
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Import failed.');
                btnExecuteImport.disabled = false;
                btnExecuteImport.innerHTML = '<i class="fas fa-file-import mr-1"></i> Import Valid Records';
                return;
            }

            document.getElementById('step-node-3').classList.add('completed');
            document.getElementById('step-node-4').classList.add('active');

            window.location.href = data.redirect_url;

        } catch (err) {
            alert('Execution error: ' + err.message);
            btnExecuteImport.disabled = false;
            btnExecuteImport.innerHTML = '<i class="fas fa-file-import mr-1"></i> Import Valid Records';
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
