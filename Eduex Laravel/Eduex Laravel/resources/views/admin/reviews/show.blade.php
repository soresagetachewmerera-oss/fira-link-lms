@extends('layouts.admin')

@section('title', 'Review Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.reviews.index') }}">Reviews</a></div>
        <div class="breadcrumb-item">Review Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Review Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.reviews.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Reviews
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Review Header -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h3>Review #{{ $review->id }}</h3>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Rating:</strong>
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $review->rating)
                                                <i class="fas fa-star text-warning"></i>
                                            @else
                                                <i class="far fa-star text-muted"></i>
                                            @endif
                                        @endfor
                                        <span class="ml-2">({{ $review->rating }}/5)</span>
                                    </p>
                                    <p class="mb-2"><strong>Status:</strong>
                                        @if ($review->is_approved)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </p>
                                    <p class="mb-2"><strong>Date:</strong>
                                        {{ $review->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div class="col-md-3">
                                    @if ($review->user)
                                        <p class="mb-2"><strong>Student:</strong> {{ $review->user->name }}</p>
                                        <p class="mb-2"><strong>Email:</strong> {{ $review->user->email }}</p>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    @if ($review->course)
                                        <p class="mb-2"><strong>Course:</strong> {{ $review->course->title }}</p>
                                        <p class="mb-2"><strong>Instructor:</strong>
                                            {{ $review->course->instructor->name ?? 'N/A' }}</p>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Replies:</strong> {{ $review->replies->count() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    @if (!$review->is_approved)
                        <div class="row mb-4">
                            <div class="col-12">
                                <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve Review
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="row mb-4">
                            <div class="col-12">
                                <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-times"></i> Set to Pending
                                    </button>
                                </form>
                                <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST"
                                    class="d-inline" id="delete-review-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger delete-review-btn"
                                        data-review-id="{{ $review->id }}"
                                        data-student="{{ $review->user->name ?? 'N/A' }}"
                                        data-course="{{ $review->course->title ?? 'N/A' }}">
                                        <i class="fas fa-trash"></i> Delete Review
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Review Content -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Review Comment</h5>
                                </div>
                                <div class="card-body">
                                    @if ($review->comment)
                                        <p>{{ $review->comment }}</p>
                                    @else
                                        <p class="text-muted">No comment provided.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Information -->
                    @if ($review->user)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Student Information</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                @if ($review->user->profile_photo)
                                                    <img src="{{ asset('storage/' . $review->user->profile_photo) }}"
                                                        alt="{{ $review->user->name }}" class="img-fluid rounded-circle"
                                                        style="width: 100px; height: 100px; object-fit: cover;">
                                                @else
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                        style="width: 100px; height: 100px;">
                                                        <i class="fas fa-user text-white fa-3x"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-9">
                                                <p><strong>Name:</strong> {{ $review->user->name }}</p>
                                                <p><strong>Email:</strong> {{ $review->user->email }}</p>
                                                @if ($review->user->phone)
                                                    <p><strong>Phone:</strong> {{ $review->user->phone }}</p>
                                                @endif
                                                <a href="{{ route('admin.students.show', $review->user->id) }}"
                                                    class="btn btn-sm btn-info">
                                                    <i class="fas fa-user"></i> View Student Profile
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Course Information -->
                    @if ($review->course)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Course Information</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                @if ($review->course->featured_image)
                                                    <img src="{{ asset('storage/' . $review->course->featured_image) }}"
                                                        alt="{{ $review->course->title }}" class="img-fluid rounded"
                                                        style="width: 150px; height: 150px; object-fit: cover;">
                                                @else
                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                                        style="width: 150px; height: 150px;">
                                                        <i class="fas fa-book text-white fa-3x"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-9">
                                                <p><strong>Title:</strong> {{ $review->course->title }}</p>
                                                <p><strong>Instructor:</strong>
                                                    {{ $review->course->instructor->name ?? 'N/A' }}</p>
                                                <p><strong>Price:</strong>
                                                    {{ currency_format($review->course->price ?? 0) }}</p>
                                                <a href="{{ route('admin.courses.show', $review->course->id) }}"
                                                    class="btn btn-sm btn-info">
                                                    <i class="fas fa-book"></i> View Course Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Replies Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Replies ({{ $review->replies->count() }})</h5>
                            @if ($review->replies->count() > 0)
                                @foreach ($review->replies as $reply)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-2">
                                                        @if ($reply->user)
                                                            @if ($reply->user->profile_photo)
                                                                <img src="{{ asset('storage/' . $reply->user->profile_photo) }}"
                                                                    alt="{{ $reply->user->name }}"
                                                                    class="mr-3 rounded-circle"
                                                                    style="width: 40px; height: 40px; object-fit: cover;">
                                                            @else
                                                                <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                                    style="width: 40px; height: 40px;">
                                                                    <i class="fas fa-user text-white"></i>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <h6 class="mb-0">{{ $reply->user->name }}</h6>
                                                                <small class="text-muted">
                                                                    @if ($reply->is_instructor)
                                                                        <span class="badge badge-primary">Instructor</span>
                                                                    @else
                                                                        <span class="badge badge-secondary">Student</span>
                                                                    @endif
                                                                    • {{ $reply->created_at->format('M d, Y h:i A') }}
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <p class="mb-0">{{ $reply->reply }}</p>
                                                </div>
                                                <div>
                                                    <form
                                                        action="{{ route('admin.reviews.delete-reply', [$review->id, $reply->id]) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger delete-reply-btn"
                                                            data-reply-id="{{ $reply->id }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No replies to this review yet.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
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
                        $('#delete-review-form').submit();
                    }
                });
            });

            $(document).on('click', '.delete-reply-btn', function() {
                const replyId = $(this).data('reply-id');
                const form = $(this).closest('form');

                Swal.fire({
                    title: 'Delete Reply?',
                    text: 'Are you sure you want to delete this reply? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
