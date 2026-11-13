@extends('layouts.admin')

@section('title', 'Marketplace Subscriptions & Offline Wires')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Coffee Marketplace</div>
        <div class="breadcrumb-item active">Subscriptions</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Exporter & Buyer Subscriptions</h4>
                    <div class="card-header-form">
                        <form method="GET" action="{{ route('admin.marketplace.subscriptions') }}">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Wire Approval</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-md">
                            <thead>
                                <tr>
                                    <th>User / Company</th>
                                    <th>Tier Plan</th>
                                    <th>Amount</th>
                                    <th>Gateway</th>
                                    <th>Started</th>
                                    <th>Expires</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $sub)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $sub->user->sellerProfile->company_name ?? $sub->user->name }}</div>
                                            <div class="text-small text-muted">{{ $sub->user->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $sub->plan->name }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($sub->plan->price_etb, 0) }} ETB</strong>
                                            <div class="text-small text-muted">${{ number_format($sub->plan->price_usd, 0) }} USD</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light text-uppercase">{{ $sub->payment->payment_method ?? 'Offline/Wire' }}</span>
                                            @if($sub->payment?->receipt_file)
                                                <a href="{{ Storage::disk('public')->url($sub->payment->receipt_file) }}" target="_blank" class="d-block text-small text-primary mt-1">
                                                    <i class="fas fa-file-invoice"></i> View Slip
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ $sub->starts_at?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>{{ $sub->expires_at?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $sub->status === 'active' ? 'badge-success' : 'badge-warning' }} text-capitalize">
                                                {{ $sub->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($sub->status === 'pending')
                                                <form method="POST" action="{{ route('admin.marketplace.subscriptions.approve', $sub->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve Bank Slip & Activate">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted text-small">Verified</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No subscriptions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-right">
                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

