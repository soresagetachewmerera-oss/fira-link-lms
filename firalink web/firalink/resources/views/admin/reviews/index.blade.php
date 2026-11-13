@extends('layouts.admin')

@section('title', $pageTitle ?? 'All Reviews')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        @if (isset($breadcrumbItems))
            @foreach ($breadcrumbItems as $item)
                @if ($item['route'])
                    <div class="breadcrumb-item {{ $loop->last ? '' : 'active' }}">
                        <a href="{{ route($item['route']) }}">{{ $item['name'] }}</a>
                    </div>
                @else
                    <div class="breadcrumb-item">{{ $item['name'] }}</div>
                @endif
            @endforeach
        @else
            <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Reviews</div>
        @endif
    </div>
@endsection

@section('main-content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalReviews }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Approved</h4>
                    </div>
                    <div class="card-body">
                        {{ $approvedReviews }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending</h4>
                    </div>
                    <div class="card-body">
                        {{ $pendingReviews }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Average Rating</h4>
                    </div>
                    <div class="card-body">
                        {{ $averageRating }}/5
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Rating Distribution</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h3 class="mb-1">5 <i class="fas fa-star text-warning"></i></h3>
                                <p class="mb-0">{{ $fiveStar }}</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h3 class="mb-1">4 <i class="fas fa-star text-warning"></i></h3>
                                <p class="mb-0">{{ $fourStar }}</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h3 class="mb-1">3 <i class="fas fa-star text-warning"></i></h3>
                                <p class="mb-0">{{ $threeStar }}</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h3 class="mb-1">2 <i class="fas fa-star text-warning"></i></h3>
                                <p class="mb-0">{{ $twoStar }}</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h3 class="mb-1">1 <i class="fas fa-star text-warning"></i></h3>
                                <p class="mb-0">{{ $oneStar }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filters and Search -->
                    <form method="GET" action="{{ route('admin.reviews.index') }}" id="filterForm">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search reviews..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="status"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                        Approved</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="rating"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="all">All Ratings</option>
                                    <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars
                                    </option>
                                    <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars
                                    </option>
                                    <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars
                                    </option>
                                    <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars
                                    </option>
                                    <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="course_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ Str::limit($course->title, 25) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="instructor_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Instructors</option>
                                    @foreach ($instructors as $instructor)
                                        <option value="{{ $instructor->id }}"
                                            {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                            {{ $instructor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                @if (request()->anyFilled(['search', 'status', 'rating', 'course_id', 'instructor_id', 'student_id']))
                                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary btn-block"
                                        title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-control" name="student_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Students</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}"
                                            {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                            {{ $student->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-9 text-right">
                                <span class="badge badge-info">Total: {{ $reviews->total() }} reviews</span>
                                @if ($pendingReviews > 0)
                                    <a href="{{ route('admin.reviews.pending') }}" class="badge badge-warning ml-1">
                                        Pending: {{ $pendingReviews }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped" id="reviews-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Status</th>
                                    <th>Replies</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reviews as $review)
                                    <tr>
                                        <td>#{{ $review->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($review->user)
                                                    @if ($review->user->profile_photo)
                                                        <img src="{{ asset('storage/' . $review->user->profile_photo) }}"
                                                            alt="{{ $review->user->name }}" class="mr-3 rounded-circle"
                                                            style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ $review->user->name }}</h6>
                                                        <small class="text-muted">{{ $review->user->email }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($review->course)
                                                <div class="d-flex align-items-center">
                                                    @if ($review->course->featured_image)
                                                        <img src="{{ asset('storage/' . $review->course->featured_image) }}"
                                                            alt="{{ $review->course->title }}" class="mr-3 rounded"
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    @else
                                                        <div class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="fas fa-book text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ Str::limit($review->course->title, 30) }}
                                                        </h6>
                                                        <small
                                                            class="text-muted">{{ $review->course->instructor->name ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $review->rating)
                                                        <i class="fas fa-star text-warning"></i>
                                                    @else
                                                        <i class="far fa-star text-muted"></i>
                                                    @endif
                                                @endfor
                                                <span class="ml-2">({{ $review->rating }})</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($review->comment)
                                                <p class="mb-0">{{ Str::limit($review->comment, 100) }}</p>
                                            @else
                                                <span class="text-muted">No comment</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($review->is_approved)
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $review->replies->count() }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $review->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.reviews.show', $review->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if (!$review->is_approved)
                                                    <button type="button"
                                                        class="btn btn-sm btn-success approve-review-btn"
                                                        data-review-id="{{ $review->id }}" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-warning reject-review-btn"
                                                        data-review-id="{{ $review->id }}" title="Set to Pending">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-danger delete-review-btn"
                                                    data-review-id="{{ $review->id }}"
                                                    data-student="{{ $review->user->name ?? 'N/A' }}"
                                                    data-course="{{ $review->course->title ?? 'N/A' }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No reviews found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $reviews->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.approve-review-btn', function() {
                const reviewId = $(this).data('review-id');

                Swal.fire({
                    title: 'Approve Review?',
                    text: 'Are you sure you want to approve this review?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check"></i> Yes, Approve',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ route('admin.reviews.approve', ':id') }}'
                                .replace(':id', reviewId)
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.reject-review-btn', function() {
                const reviewId = $(this).data('review-id');

                Swal.fire({
                    title: 'Set Review to Pending?',
                    text: 'This review will be hidden from public view until approved again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times"></i> Yes, Set to Pending',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ route('admin.reviews.reject', ':id') }}'.replace(
                                ':id', reviewId)
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.delete-review-btn', function() {
                const reviewId = $(this).data('review-id');
                const student = $(this).data('student');
                const course = $(this).data('course');

                Swal.fire({
                    title: 'Delete Review?',
                    html: `
                <p>Are you sure you want to delete this review?</p>
                <p><strong>Student:</strong> ${student}</p>
                <p><strong>Course:</strong> ${course}</p>
                <p class="text-danger">This action cannot be undone.</p>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ route('admin.reviews.destroy', ':id') }}'
                                .replace(':id', reviewId)
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
