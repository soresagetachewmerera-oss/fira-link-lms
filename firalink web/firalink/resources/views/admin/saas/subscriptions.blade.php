@extends('layouts.admin')

@section('title', 'Subscriptions Management')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.saas-billing.plans.index') }}">SaaS Billing</a></div>
    <div class="breadcrumb-item active">Subscriptions</div>
@endsection

@section('main-content')
<div class="section-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">Platform Subscriptions</h2>
            <p class="section-lead">Manage customer organization subscriptions, monitor past-due and grace periods, and handle manual plan transitions.</p>
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

    <!-- Status Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted mr-2 font-weight-bold" style="font-size: 12px;">Filter Status:</span>
                <a href="{{ route('admin.saas-billing.subscriptions.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
                <a href="{{ route('admin.saas-billing.subscriptions.index', ['status' => 'active']) }}" class="btn btn-sm {{ request('status') === 'active' ? 'btn-success' : 'btn-outline-success' }}">Active</a>
                <a href="{{ route('admin.saas-billing.subscriptions.index', ['status' => 'grace']) }}" class="btn btn-sm {{ request('status') === 'grace' ? 'btn-warning text-dark font-weight-bold' : 'btn-outline-warning' }}">Grace Period (14d)</a>
                <a href="{{ route('admin.saas-billing.subscriptions.index', ['status' => 'past_due']) }}" class="btn btn-sm {{ request('status') === 'past_due' ? 'btn-danger' : 'btn-outline-danger' }}">Past Due</a>
                <a href="{{ route('admin.saas-billing.subscriptions.index', ['status' => 'trialing']) }}" class="btn btn-sm {{ request('status') === 'trialing' ? 'btn-info' : 'btn-outline-info' }}">Trialing</a>
                <a href="{{ route('admin.saas-billing.subscriptions.index', ['status' => 'canceled']) }}" class="btn btn-sm {{ request('status') === 'canceled' ? 'btn-secondary' : 'btn-outline-secondary' }}">Canceled</a>
            </div>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-file-contract mr-2 text-primary"></i> Subscriptions ({{ $subscriptions->total() }})</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Organization</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Period</th>
                            <th>Dates</th>
                            <th>Gateway</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $sub)
                            @php
                                $statusClass = [
                                    'active' => 'badge-success',
                                    'grace' => 'badge-warning text-dark font-weight-bold',
                                    'past_due' => 'badge-danger',
                                    'trialing' => 'badge-info',
                                    'canceled' => 'badge-secondary',
                                    'expired' => 'badge-dark',
                                ][$sub->status] ?? 'badge-secondary';
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $sub->organization->name ?? "Org #{$sub->organization_id}" }}</div>
                                    <div class="text-muted" style="font-size: 11px;">{{ $sub->organization->billing_email ?? '—' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">{{ $sub->plan->name ?? "Plan #{$sub->plan_id}" }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }} uppercase">{{ $sub->status }}</span>
                                    @if($sub->cancel_at_period_end)
                                        <div class="badge badge-danger mt-1" style="font-size: 9px;">Cancels At End</div>
                                    @endif
                                </td>
                                <td>{{ ucfirst($sub->billing_period) }}</td>
                                <td style="font-size: 12px;">
                                    <div><strong>Start:</strong> {{ $sub->current_period_start ? $sub->current_period_start->format('M d, Y') : '—' }}</div>
                                    <div><strong>End:</strong> {{ $sub->current_period_end ? $sub->current_period_end->format('M d, Y') : '—' }}</div>
                                    @if($sub->grace_until)
                                        <div class="text-danger font-weight-bold"><strong>Grace Until:</strong> {{ $sub->grace_until->format('M d, Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-uppercase font-weight-bold" style="font-size: 11px;">{{ $sub->provider ?? 'chapa' }}</span>
                                    <div class="text-muted font-mono" style="font-size: 10px;">{{ $sub->provider_ref ?? '—' }}</div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#changePlanModal-{{ $sub->id }}">
                                        <i class="fas fa-edit mr-1"></i> Manage
                                    </button>

                                    <!-- Plan Change Modal -->
                                    <div class="modal fade" id="changePlanModal-{{ $sub->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.saas-billing.subscriptions.change-plan', $sub->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Manage Subscription #{{ $sub->id }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted" style="font-size: 13px;">
                                                            Organization: <strong>{{ $sub->organization->name ?? "Org #{$sub->organization_id}" }}</strong><br>
                                                            Current: <strong>{{ $sub->plan->name ?? '—' }}</strong> ({{ $sub->status }})
                                                        </p>
                                                        <div class="form-group">
                                                            <label>Assign Plan</label>
                                                            <select name="plan_id" class="form-control" required>
                                                                @foreach($plans as $p)
                                                                    <option value="{{ $p->id }}" {{ $sub->plan_id == $p->id ? 'selected' : '' }}>
                                                                        {{ $p->name }} ({{ $p->key }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Override Status</label>
                                                            <select name="status" class="form-control" required>
                                                                <option value="active" {{ $sub->status === 'active' ? 'selected' : '' }}>Active</option>
                                                                <option value="grace" {{ $sub->status === 'grace' ? 'selected' : '' }}>Grace Period</option>
                                                                <option value="past_due" {{ $sub->status === 'past_due' ? 'selected' : '' }}>Past Due</option>
                                                                <option value="trialing" {{ $sub->status === 'trialing' ? 'selected' : '' }}>Trialing</option>
                                                                <option value="canceled" {{ $sub->status === 'canceled' ? 'selected' : '' }}>Canceled</option>
                                                                <option value="expired" {{ $sub->status === 'expired' ? 'selected' : '' }}>Expired</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Extend Grace Days</label>
                                                            <input type="number" name="grace_days" value="14" class="form-control" min="0" max="90">
                                                            <small class="form-text text-muted">Applied if status is Grace Period</small>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Reason / Audit Note</label>
                                                            <input type="text" name="reason" class="form-control" placeholder="e.g. Granted courtesy extension or customer service upgrade" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No subscriptions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($subscriptions->hasPages())
            <div class="card-footer py-2">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

