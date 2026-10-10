@extends('layouts.admin')

@section('title', 'Revenue Distribution')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item">Revenue Distribution</div>
    </div>
@endsection

@section('main-content')
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="section-title mb-1">Revenue Distribution</h2>
                    <p class="section-lead mb-0 mt-2">
                        Configure how course revenue is split between the platform and instructors.
                    </p>
                </div>
                <div class="mb-3 mb-md-0">
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Commission Structure</h4>
                    </div>
                    <form action="{{ route('admin.settings.revenue.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="mode">Commission Type</label>
                                <select name="mode" id="mode"
                                    class="form-control @error('mode') is-invalid @enderror">
                                    <option value="percentage"
                                        {{ old('mode', $settings['mode'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>
                                        Percentage of sale amount
                                    </option>
                                    <option value="fixed"
                                        {{ old('mode', $settings['mode'] ?? 'percentage') === 'fixed' ? 'selected' : '' }}>
                                        Fixed amount per enrollment
                                    </option>
                                </select>
                                @error('mode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="value">
                                    Commission Value
                                    <span class="text-muted small d-block mt-1" id="value-help">
                                        {{ old('mode', $settings['mode'] ?? 'percentage') === 'fixed' ? 'Amount taken by platform for each paid enrollment.' : 'Percentage retained by the platform from each paid enrollment.' }}
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="value" id="value" min="0" step="0.01"
                                        value="{{ old('value', $settings['value'] ?? 0) }}"
                                        class="form-control @error('value') is-invalid @enderror">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="value-suffix">
                                            {{ old('mode', $settings['mode'] ?? 'percentage') === 'fixed' ? currency_code() : '%' }}
                                        </span>
                                    </div>
                                    @error('value')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading font-weight-bold mb-2"><i class="fas fa-info-circle mr-1"></i> Key System Rules:</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Commission applies to all paid course sales, bundle sales, and subscription plan purchases.</li>
                                    <li>For offline payments, commission & instructor earnings are processed once the payment is approved by admin.</li>
                                    <li>Instructor earnings are credited directly to their wallet balance automatically upon payment completion.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- How Revenue Share Works (Detailed Guide & Examples) -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h4><i class="fas fa-calculator mr-2 text-primary"></i> How Revenue Distribution Works (With Examples)</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Below is a breakdown of how the platform automatically distributes earnings across single courses, course bundles, and subscription plans based on your set commission rate.</p>

                        <div class="row">
                            <!-- Example 1: Direct Course Sale -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded bg-light h-100">
                                    <h6 class="font-weight-bold text-dark"><i class="fas fa-book mr-1 text-info"></i> 1. Single Course Sale</h6>
                                    <p class="small text-muted mb-2">When a student buys an individual course:</p>
                                    <ul class="small pl-3 mb-0">
                                        <li><strong>Platform Commission:</strong> Deducted first based on settings (e.g., 20%).</li>
                                        <li><strong>Instructor Share:</strong> Remaining 80% credited directly to course instructor.</li>
                                    </ul>
                                    <div class="mt-2 p-2 bg-white rounded border small">
                                        <span class="d-block font-weight-bold text-success">Example ($100 Course, 20% Comm.):</span>
                                        • Platform gets: <strong>$20</strong><br>
                                        • Instructor gets: <strong>$80</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Example 2: Bundle Course Sale -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded bg-light h-100">
                                    <h6 class="font-weight-bold text-dark"><i class="fas fa-boxes mr-1 text-warning"></i> 2. Bundle Course Sale</h6>
                                    <p class="small text-muted mb-2">When a student purchases a course bundle:</p>
                                    <ul class="small pl-3 mb-0">
                                        <li><strong>Single-Instructor Bundle:</strong> Net instructor pool goes 100% to bundle creator.</li>
                                        <li><strong>Multi-Instructor Admin Bundle:</strong> Net pool splits proportionally based on course count per instructor.</li>
                                    </ul>
                                    <div class="mt-2 p-2 bg-white rounded border small">
                                        <span class="d-block font-weight-bold text-success">Example ($100 Bundle, 4 Courses, Inst. A: 3, Inst. B: 1):</span>
                                        • Platform: <strong>$20</strong> | Pool: <strong>$80</strong><br>
                                        • Inst. A (3/4): <strong>$60</strong> | Inst. B (1/4): <strong>$20</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Example 3: Subscription Plan Sale -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded bg-light h-100">
                                    <h6 class="font-weight-bold text-dark"><i class="fas fa-sync-alt mr-1 text-primary"></i> 3. Subscription Plan Purchase</h6>
                                    <p class="small text-muted mb-2">When a student purchases a Subscription Plan:</p>
                                    <ul class="small pl-3 mb-0">
                                        <li>Platform commission is deducted from subscription total.</li>
                                        <li>Net instructor pool is divided equally across all included courses & bundles in that plan.</li>
                                        <li>Full breakdown is logged in DB notes for audit.</li>
                                    </ul>
                                    <div class="mt-2 p-2 bg-white rounded border small">
                                        <span class="d-block font-weight-bold text-success">Example ($100 Plan, 2 Courses + 1 Bundle):</span>
                                        • Platform: <strong>$20</strong> | Pool: <strong>$80</strong> ($26.66 / item)<br>
                                        • Course 1 (Inst A): <strong>$26.66</strong><br>
                                        • Course 2 (Inst B): <strong>$26.66</strong><br>
                                        • Bundle (Inst C): <strong>$26.66</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0 small">
                            <i class="fas fa-shield-alt mr-1"></i> <strong>Auditability & Tracking:</strong> Instructors can inspect their exact subscription payout breakdown anytime under their <strong>Subscription Sales</strong> dashboard menu. All transaction shares are processed inside transactional database blocks to guarantee 100% mathematical precision with zero revenue leak.
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
            const modeInput = document.getElementById('mode');
            const valueSuffix = document.getElementById('value-suffix');
            const valueHelp = document.getElementById('value-help');

            if (modeInput) {
                modeInput.addEventListener('change', function() {
                    if (modeInput.value === 'fixed') {
                        valueSuffix.textContent = '{{ currency_code() }}';
                        valueHelp.textContent = 'Amount taken by platform for each paid enrollment.';
                    } else {
                        valueSuffix.textContent = '%';
                        valueHelp.textContent =
                            'Percentage retained by the platform from each paid enrollment.';
                    }
                });
            }
        });
    </script>
@endpush
