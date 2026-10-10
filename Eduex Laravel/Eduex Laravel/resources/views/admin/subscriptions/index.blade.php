@extends('layouts.admin')

@section('title', 'User Subscriptions')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">User Subscriptions</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header justify-content-between align-items-center">
                    <h4 class="mb-0">All User Subscriptions</h4>
                    <div class="card-header-action">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#assignSubscriptionModal">
                            <i class="fas fa-plus-circle"></i> Manually Assign Subscription
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter bar -->
                    <form action="{{ route('admin.subscriptions.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by student name or email..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-secondary mr-1"><i class="fas fa-filter"></i> Filter</button>
                                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Package Plan</th>
                                    <th>Payment</th>
                                    <th>Starts At</th>
                                    <th>Ends At / Remaining</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $sub)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $sub->user->name ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $sub->user->email ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $sub->plan->name ?? 'N/A' }}</span>
                                            <br><small class="text-muted">{{ currency_symbol() }}{{ number_format($sub->plan->effective_price ?? 0, 2) }}</small>
                                        </td>
                                        <td>
                                            @if($sub->payment)
                                                <span class="badge badge-light text-uppercase">{{ $sub->payment->payment_method }}</span>
                                                <br><small class="text-muted">{{ currency_symbol() }}{{ number_format($sub->payment->amount, 2) }}</small>
                                                @if($sub->payment->receipt_url)
                                                    <a href="{{ $sub->payment->receipt_url }}" target="_blank" class="ml-1 text-primary" title="View Receipt"><i class="fas fa-paperclip"></i></a>
                                                @endif
                                            @else
                                                <span class="badge badge-light">Manual Grant</span>
                                            @endif
                                        </td>
                                        <td>{{ $sub->starts_at ? $sub->starts_at->format('M d, Y') : 'N/A' }}</td>
                                        <td>
                                            @if($sub->ends_at)
                                                {{ $sub->ends_at->format('M d, Y') }}
                                                <br>
                                                @if($sub->isActive())
                                                    <small class="text-success font-weight-bold">{{ $sub->remaining_days }} Days Remaining</small>
                                                @else
                                                    <small class="text-danger">Expired</small>
                                                @endif
                                            @else
                                                <span class="badge badge-success">Lifetime Access</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sub->status === 'active' && $sub->isActive())
                                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                            @elseif($sub->status === 'pending_approval')
                                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Pending Approval</span>
                                            @elseif($sub->status === 'cancelled')
                                                <span class="badge badge-secondary"><i class="fas fa-ban mr-1"></i> Cancelled</span>
                                            @else
                                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> Expired</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sub->status === 'pending_approval' && $sub->payment)
                                                <form action="{{ route('admin.subscriptions.approve-offline', $sub->payment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve offline subscription payment?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve Offline Payment">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No user subscriptions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $subscriptions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Assign Subscription Modal -->
    <div class="modal fade" id="assignSubscriptionModal" tabindex="-1" role="dialog" aria-labelledby="assignSubscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.subscriptions.assign') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignSubscriptionModalLabel">
                            <i class="fas fa-id-card text-primary mr-2"></i> Manually Assign Subscription
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="user_id">Select Student <span class="text-danger">*</span></label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">-- Choose Student --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="subscription_plan_id">Select Package Plan <span class="text-danger">*</span></label>
                            <select name="subscription_plan_id" id="subscription_plan_id" class="form-control" required>
                                <option value="">-- Choose Package Plan --</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ currency_symbol() }}{{ number_format($plan->effective_price, 2) }} - {{ $plan->duration_days }} Days)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="duration_days">Custom Duration (Days) <small class="text-muted">(Optional)</small></label>
                            <input type="number" name="duration_days" id="duration_days" class="form-control" placeholder="Leave empty to use plan default duration">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Assign Subscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
