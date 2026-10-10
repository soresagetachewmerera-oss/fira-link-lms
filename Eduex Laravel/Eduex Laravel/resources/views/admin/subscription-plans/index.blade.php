@extends('layouts.admin')

@section('title', 'Package Plans')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Subscription Package Plans</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header justify-content-between align-items-center">
                    <h4 class="mb-0">All Package Plans</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.subscription-plans.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Create Package Plan
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Plan Name</th>
                                    <th>Regular Price</th>
                                    <th>Offer Price</th>
                                    <th>Billing Period</th>
                                    <th>Included Items</th>
                                    <th>Subscribers</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $plan->name }}</strong>
                                            @if($plan->is_featured)
                                                <span class="badge badge-warning ml-1">Featured</span>
                                            @endif
                                        </td>
                                        <td>{{ currency_symbol() }}{{ number_format($plan->price, 2) }}</td>
                                        <td>
                                            @if($plan->has_discount)
                                                <span class="text-success font-weight-bold">{{ currency_symbol() }}{{ number_format($plan->discount_price, 2) }}</span>
                                                <small class="badge badge-success ml-1">{{ $plan->discount_percentage }}% OFF</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $plan->billing_period) }}</span>
                                            <br><small class="text-muted">{{ $plan->duration_days }} Days</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-light" title="Courses & Live Courses">
                                                <i class="fas fa-book mr-1"></i> {{ $plan->courses_count }} Courses
                                            </span>
                                            <br>
                                            <span class="badge badge-light" title="Course Bundles">
                                                <i class="fas fa-cubes mr-1"></i> {{ $plan->bundles_count }} Bundles
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $plan->user_subscriptions_count }} Users</span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.subscription-plans.toggle-status', $plan->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @if($plan->status === 'active')
                                                    <button type="submit" class="btn btn-sm btn-success">Active</button>
                                                @else
                                                    <button type="submit" class="btn btn-sm btn-danger">Inactive</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.subscription-plans.edit', $plan->id) }}" class="btn btn-sm btn-info me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.subscription-plans.destroy', $plan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this plan?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No subscription package plans found. Create your first plan!</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
