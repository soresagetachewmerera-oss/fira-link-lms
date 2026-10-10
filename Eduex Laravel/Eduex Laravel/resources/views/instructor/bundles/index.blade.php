@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <div class="section-title wow fadeInUp d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h6>Manage Bundles</h6>
                        <h2>My Course Bundles</h2>
                    </div>
                    <a href="{{ route('instructor.bundles.create') }}" class="theme-btn">
                        <i class="fas fa-plus"></i> Create Bundle
                    </a>
                </div>

                <div class="dashboard-content-section">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Bundle Title</th>
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
                                                        style="width: 60px; height: 60px; background: #ddd;">
                                                        <i class="fas fa-layer-group text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ Str::limit($bundle->title, 50) }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ currency_format($bundle->price) }}</td>
                                        <td><span class="badge bg-info text-white">{{ $bundle->courses->count() }} Courses</span></td>
                                        <td>
                                            @if($bundle->status === 'active')
                                                <span class="badge bg-success text-white">Active</span>
                                            @else
                                                <span class="badge bg-secondary text-white">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bundle->approval_status === 'approved')
                                                <span class="badge bg-success text-white">Approved</span>
                                            @elseif($bundle->approval_status === 'rejected')
                                                <span class="badge bg-danger text-white">Rejected</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('instructor.bundles.edit', $bundle->id) }}"
                                                    class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('instructor.bundles.destroy', $bundle->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this bundle?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No course bundles found. You can group together your existing courses to boost sales!</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                @if(method_exists($bundles, 'links'))
                    <div class="mt-4">
                        {{ $bundles->links() }}
                    </div>
                @endif
                
            </div>
        </div>
    </div>
</section>
@endsection
