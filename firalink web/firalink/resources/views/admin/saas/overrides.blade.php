@extends('layouts.admin')

@section('title', 'Organization Entitlement Overrides')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.saas-billing.plans.index') }}">SaaS Billing</a></div>
    <div class="breadcrumb-item active">Entitlement Overrides</div>
@endsection

@section('main-content')
<div class="section-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">Organization Entitlement Overrides</h2>
            <p class="section-lead">Grant individual quota exceptions, founding-member deals, and trial extensions. Overrides take precedence over plan entitlements and expire automatically.</p>
        </div>
        <button class="btn btn-warning text-dark font-weight-bold" data-toggle="collapse" data-target="#grantOverrideCollapse">
            <i class="fas fa-magic mr-1"></i> Grant Entitlement Override
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

    <!-- Grant Override Form Collapse -->
    <div class="collapse mb-4" id="grantOverrideCollapse">
        <div class="card card-warning shadow-sm">
            <div class="card-header">
                <h4><i class="fas fa-hand-holding-heart mr-2"></i> Grant Organization Quota Override</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.saas-billing.overrides.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <label>Target Organization <span class="text-danger">*</span></label>
                            <select name="organization_id" class="form-control select2" required>
                                <option value="">Select Organization...</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }} ({{ $org->business_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Feature Capability <span class="text-danger">*</span></label>
                            <select name="feature_key" class="form-control select2" required>
                                <option value="">Select Feature...</option>
                                @foreach($features as $f)
                                    <option value="{{ $f->key }}">{{ $f->label }} ({{ $f->key }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Override Value <span class="text-danger">*</span></label>
                            <input type="text" name="value" class="form-control" placeholder="e.g. 50 or 1" required>
                            <small class="form-text text-muted">-1 for unlimited</small>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Expires At (Optional)</label>
                            <input type="datetime-local" name="expires_at" class="form-control">
                            <small class="form-text text-muted">Leave empty for non-expiring</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason / Audit Note <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Founding Exporter 6-Month Promo (+50 active lots)" required>
                    </div>
                    <button type="submit" class="btn btn-warning text-dark font-weight-bold">
                        <i class="fas fa-check-circle mr-1"></i> Apply &amp; Audit Override
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Overrides Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-history mr-2 text-warning"></i> Active &amp; Historical Organization Overrides</h4>
            <span class="badge badge-light border">{{ $overrides->count() }} records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Organization</th>
                            <th>Feature Key</th>
                            <th>Override Value</th>
                            <th>Reason / Promo</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Granted By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overrides as $ov)
                            <tr>
                                <td class="font-weight-bold text-dark">
                                    {{ $ov->organization->name ?? "Org #{$ov->organization_id}" }}
                                </td>
                                <td><code>{{ $ov->feature_key }}</code></td>
                                <td class="font-weight-bold text-primary">{{ $ov->value }}</td>
                                <td>{{ $ov->reason }}</td>
                                <td>
                                    @if($ov->expires_at)
                                        {{ $ov->expires_at->format('M d, Y H:i') }}
                                        <div class="text-muted" style="font-size: 11px;">({{ $ov->expires_at->diffForHumans() }})</div>
                                    @else
                                        <span class="text-muted">Permanent</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ov->isExpired())
                                        <span class="badge badge-secondary">Expired</span>
                                    @else
                                        <span class="badge badge-success">Active</span>
                                    @endif
                                </td>
                                <td>{{ $ov->creator->name ?? 'System Admin' }}</td>
                                <td>
                                    <form action="{{ route('admin.saas-billing.overrides.destroy', $ov->id) }}" method="POST" onsubmit="return confirm('Revoke this override? The organization will immediately revert to standard plan entitlements.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Revoke Override">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No organization overrides found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

