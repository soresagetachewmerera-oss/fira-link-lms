@extends('layouts.admin')

@section('title', 'Feature Capability Registry')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.saas-billing.plans.index') }}">SaaS Billing</a></div>
    <div class="breadcrumb-item active">Feature Registry</div>
@endsection

@section('main-content')
<div class="section-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">Feature Registry</h2>
            <p class="section-lead">The authoritative registry of gateable capabilities in Fira Link. Features are data, not code.</p>
        </div>
        <button class="btn btn-primary" data-toggle="collapse" data-target="#registerFeatureCollapse">
            <i class="fas fa-plus-circle mr-1"></i> Register New Feature
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert"><span>&times;</span></button>
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert"><span>&times;</span></button>
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- New Feature Modal / Collapse -->
    <div class="collapse mb-4" id="registerFeatureCollapse">
        <div class="card card-primary shadow-sm">
            <div class="card-header">
                <h4><i class="fas fa-plus-square mr-2"></i> Register New Gated Feature</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.saas-billing.features.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="col-md-3 form-group">
                            <label>Feature Key <span class="text-danger">*</span></label>
                            <input type="text" name="key" class="form-control" placeholder="e.g. storage_gb, api_access" required>
                            <small class="form-text text-muted">Stable slug used by EntitlementService::can()</small>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Human Label <span class="text-danger">*</span></label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. Cloud Storage (GB)" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="limit">Limit (Numeric Quota)</option>
                                <option value="boolean">Boolean (On / Off)</option>
                                <option value="tier">Tier (String Enum)</option>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="lots, GB, seats">
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Availability</label>
                            <select name="is_available" class="form-control">
                                <option value="1">Available Now</option>
                                <option value="0">Coming Soon (Preview)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description &amp; Gating Purpose</label>
                        <input type="text" name="description" class="form-control" placeholder="Explains the capability to buyers and exporters">
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Save to Feature Registry
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Feature Registry Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h4><i class="fas fa-cogs mr-2 text-primary"></i> Registered Platform Capabilities ({{ $features->count() }})</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Key</th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Plans Using</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($features as $feature)
                            <tr>
                                <td class="font-mono font-weight-bold text-dark">
                                    <code>{{ $feature->key }}</code>
                                </td>
                                <td>{{ $feature->label }}</td>
                                <td>
                                    <span class="badge badge-{{ $feature->type === 'limit' ? 'primary' : ($feature->type === 'boolean' ? 'success' : 'info') }}">
                                        {{ ucfirst($feature->type) }}
                                    </span>
                                </td>
                                <td>{{ $feature->unit ?? '—' }}</td>
                                <td class="text-muted" style="max-width: 320px;">{{ $feature->description ?? '—' }}</td>
                                <td>
                                    @if($feature->is_available)
                                        <span class="badge badge-success">Available</span>
                                    @else
                                        <span class="badge badge-warning text-dark">Coming Soon</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border">{{ $feature->plan_entitlements_count }} plans</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No features registered in the database.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

