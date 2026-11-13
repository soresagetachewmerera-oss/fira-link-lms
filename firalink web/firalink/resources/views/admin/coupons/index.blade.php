@extends('layouts.admin')

@section('title', 'Coupons & Discounts')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Coupons & Discounts</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Coupons</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Coupon
                </a>
            </div>
        </div>
        <div class="card-body">
            @if ($coupons->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Restrictions</th>
                                <th>Usage Limit</th>
                                <th>Validity</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($coupons as $coupon)
                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $coupon->code }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ ucfirst($coupon->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($coupon->type === 'percentage')
                                            {{ (float)$coupon->value }}%
                                        @else
                                            {{ (float)$coupon->value }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->course)
                                            <span class="d-block text-small">Course: <strong>{{ $coupon->course->title }}</strong></span>
                                        @endif
                                        @if($coupon->instructor)
                                            <span class="d-block text-small">Instructor: <strong>{{ $coupon->instructor->name }}</strong></span>
                                        @endif
                                        @if(!$coupon->course && !$coupon->instructor)
                                            <span class="text-muted">Platform-wide</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}
                                    </td>
                                    <td>
                                        <span class="text-small d-block">
                                            From: {{ $coupon->valid_from ? $coupon->valid_from->format('Y-m-d H:i') : 'Always' }}
                                        </span>
                                        <span class="text-small d-block">
                                            To: {{ $coupon->valid_to ? $coupon->valid_to->format('Y-m-d H:i') : 'Never Expires' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($coupon->isValidNow())
                                            <span class="badge badge-success">Valid</span>
                                        @else
                                            <span class="badge badge-danger">Expired/Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.coupons.edit', $coupon->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.coupons.destroy', $coupon->id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            Showing {{ $coupons->firstItem() ?? 0 }} to {{ $coupons->lastItem() ?? 0 }} of
                            {{ $coupons->total() }} coupons
                        </p>
                    </div>
                    <div>
                        {{ $coupons->links() }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No coupons found.</p>
            @endif
        </div>
    </div>
@endsection
