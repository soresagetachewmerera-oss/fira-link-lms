@extends('layouts.admin')

@section('title', 'Manage Bundles')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Bundles</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Course Bundles</h4>
                    <a href="{{ route('admin.bundles.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Create Bundle</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="bundles-table">
                            <thead>
                                <tr>
                                    <th>Bundle Title</th>
                                    <th>Vendor</th>
                                    <th>Price</th>
                                    <th>Courses Included</th>
                                    <th>Status</th>
                                    <th>Approval</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bundles as $bundle)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($bundle->featured_image)
                                                    <img src="{{ asset('storage/' . $bundle->featured_image) }}"
                                                        alt="{{ $bundle->title }}" class="mr-3 rounded p-1"
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                @else
                                                    <div class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center"
                                                        style="width: 60px; height: 60px;">
                                                        <i class="fas fa-layer-group text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ Str::limit($bundle->title, 50) }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $bundle->vendor ? $bundle->vendor->name : 'N/A' }}</td>
                                        <td>{{ currency_format($bundle->price) }}</td>
                                        <td><span class="badge badge-info">{{ $bundle->courses->count() }} Courses</span></td>
                                        <td>
                                            @if($bundle->status === 'active')
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bundle->approval_status === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif($bundle->approval_status === 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if ($bundle->approval_status === 'pending')
                                                    <form action="{{ route('admin.bundles.approve', $bundle->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.bundles.reject', $bundle->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('admin.bundles.edit', $bundle->id) }}"
                                                    class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.bundles.destroy', $bundle->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this bundle?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No course bundles found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $bundles->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
