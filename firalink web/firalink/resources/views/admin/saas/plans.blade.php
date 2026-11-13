@extends('layouts.admin')

@section('title', 'SaaS Plans & Pricing Management')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item">SaaS Billing</div>
    <div class="breadcrumb-item active">Plans &amp; Entitlements</div>
@endsection

@section('main-content')
<div class="section-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">Dynamic SaaS Plans &amp; Feature Entitlements</h2>
            <p class="section-lead">Manage commercial tiers, currency pricing (ETB / USD), and feature entitlements. Admins can adjust limits without code deployment.</p>
        </div>
        <div>
            <a href="{{ route('admin.saas-billing.features.index') }}" class="btn btn-outline-primary mr-2">
                <i class="fas fa-list-ul mr-1"></i> Feature Registry
            </a>
            <a href="{{ route('admin.saas-billing.overrides.index') }}" class="btn btn-outline-warning">
                <i class="fas fa-magic mr-1"></i> Organization Overrides
            </a>
        </div>
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

    <!-- Plans Cards -->
    <div class="row">
        @foreach($plans as $plan)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-hero h-100 shadow-sm border-{{ $plan->key === 'business' ? 'primary' : ($plan->key === 'growth' ? 'info' : 'secondary') }}">
                    <div class="card-header d-flex justify-content-between align-items-start" style="background: {{ $plan->key === 'business' ? '#4A0E63' : ($plan->key === 'growth' ? '#6D1A95' : '#4b5563') }}; color: #fff;">
                        <div>
                            <span class="badge badge-light text-dark font-weight-bold uppercase mb-1">{{ $plan->key }}</span>
                            <h4 class="text-white mb-0">{{ $plan->name }}</h4>
                            <div class="card-description text-white-50 mt-1" style="font-size: 12px;">{{ $plan->description }}</div>
                        </div>
                        <div class="text-right">
                            @if($plan->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                            @if($plan->is_public)
                                <span class="badge badge-info ml-1">Public</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Prices List -->
                        <h6 class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">Configured Prices</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Curr</th>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($plan->prices as $price)
                                        <tr>
                                            <td class="font-weight-bold">{{ $price->currency }}</td>
                                            <td>{{ ucfirst($price->billing_period) }}</td>
                                            <td>
                                                <form action="{{ route('admin.saas-billing.prices.update', $price->id) }}" method="POST" class="d-flex align-items-center">
                                                    @csrf
                                                    <input type="number" step="0.01" min="0" name="amount" value="{{ $price->amount }}" class="form-control form-control-sm mr-1" style="width: 85px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Save Price"><i class="fas fa-save"></i></button>
                                                </form>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-{{ $price->is_active ? 'success' : 'secondary' }}">{{ $price->is_active ? 'ON' : 'OFF' }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">No prices seeded</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Update Plan Basic Details Form -->
                        <button class="btn btn-sm btn-outline-dark btn-block mb-2" type="button" data-toggle="collapse" data-target="#editPlanMeta-{{ $plan->id }}">
                            <i class="fas fa-cog mr-1"></i> Edit Plan Settings
                        </button>
                        <div class="collapse mb-3" id="editPlanMeta-{{ $plan->id }}">
                            <form action="{{ route('admin.saas-billing.plans.update', $plan->id) }}" method="POST" class="p-3 bg-light rounded">
                                @csrf
                                <div class="form-group mb-2">
                                    <label class="mb-1" style="font-size: 11px;">Plan Display Name</label>
                                    <input type="text" name="name" value="{{ $plan->name }}" class="form-control form-control-sm" required>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="mb-1" style="font-size: 11px;">Description</label>
                                    <textarea name="description" class="form-control form-control-sm" rows="2">{{ $plan->description }}</textarea>
                                </div>
                                <div class="form-row">
                                    <div class="col-6 form-group mb-2">
                                        <label class="mb-1" style="font-size: 11px;">Trial Days</label>
                                        <input type="number" name="trial_days" value="{{ $plan->trial_days }}" class="form-control form-control-sm" min="0">
                                    </div>
                                    <div class="col-6 form-group mb-2">
                                        <label class="mb-1" style="font-size: 11px;">Sort Order</label>
                                        <input type="number" name="sort_order" value="{{ $plan->sort_order }}" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-1">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="isActive-{{ $plan->id }}" {{ $plan->is_active ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="isActive-{{ $plan->id }}" style="font-size: 11px;">Active</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="is_public" value="1" class="custom-control-input" id="isPublic-{{ $plan->id }}" {{ $plan->is_public ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="isPublic-{{ $plan->id }}" style="font-size: 11px;">Public</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </div>
                            </form>
                        </div>

                        <!-- Entitlements Form -->
                        <h6 class="text-muted font-weight-bold text-uppercase mt-3" style="font-size: 11px;">Plan Entitlements Matrix</h6>
                        <form action="{{ route('admin.saas-billing.plans.entitlements', $plan->id) }}" method="POST">
                            @csrf
                            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0" style="font-size: 12px;">
                                    <tbody>
                                        @foreach($features as $f)
                                            @php
                                                $val = $plan->entitlementsMap()[$f->key] ?? '';
                                            @endphp
                                            <tr>
                                                <td class="align-middle">
                                                    <strong>{{ $f->label }}</strong>
                                                    <div class="text-muted" style="font-size: 10px;">{{ $f->key }} ({{ $f->type }})</div>
                                                </td>
                                                <td style="width: 130px;" class="align-middle">
                                                    @if($f->type === 'boolean')
                                                        <select name="entitlements[{{ $f->key }}]" class="form-control form-control-sm">
                                                            <option value="1" {{ $val === true || $val === '1' || $val === 'true' ? 'selected' : '' }}>Enabled (True)</option>
                                                            <option value="0" {{ $val === false || $val === '0' || $val === 'false' || $val === '' ? 'selected' : '' }}>Disabled (False)</option>
                                                        </select>
                                                    @elseif($f->type === 'limit')
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" name="entitlements[{{ $f->key }}]" value="{{ $val }}" class="form-control form-control-sm" placeholder="-1 = unlim">
                                                        </div>
                                                    @else
                                                        <input type="text" name="entitlements[{{ $f->key }}]" value="{{ $val }}" class="form-control form-control-sm">
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-sm btn-success btn-block mt-3 shadow-sm">
                                <i class="fas fa-check-circle mr-1"></i> Save {{ $plan->name }} Entitlements
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

