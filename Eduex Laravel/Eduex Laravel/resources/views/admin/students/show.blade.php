@extends('layouts.admin')

@section('title', 'Student Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Students</a></div>
        <div class="breadcrumb-item">Student Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Student Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.students.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Students
                        </a>
                        <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-icon btn-warning">
                            <i class="fas fa-edit"></i> Edit Student
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Student Header -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            @if ($student->profile_photo)
                                <img src="{{ asset('storage/' . $student->profile_photo) }}" alt="{{ $student->name }}"
                                    class="img-fluid rounded-circle profile-photo-xl">
                            @else
                                <div
                                    class="bg-secondary rounded-circle d-flex align-items-center justify-content-center profile-placeholder-xl mx-auto">
                                    <i class="fas fa-user fa-5x text-white"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <h2 class="mb-3">{{ $student->name }}</h2>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Email:</strong> {{ $student->email }}</p>
                                    @if ($student->phone)
                                        <p class="mb-2"><strong>Phone:</strong> {{ $student->phone }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Total Enrollments:</strong> {{ $totalEnrollments }}</p>
                                    <p class="mb-2"><strong>Completed Courses:</strong> {{ $completedCourses }}</p>
                                    <p class="mb-2"><strong>Total Spent:</strong> {{ currency_format($totalSpent) }}</p>
                                    <p class="mb-2"><strong>Active Enrollments:</strong> {{ $activeEnrollments }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form action="{{ route('admin.impersonate.student', $student->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-secret"></i> Login as Student
                                </button>
                            </form>
                            <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-warning ml-2">
                                <i class="fas fa-edit"></i> Edit Details
                            </a>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-primary">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Enrollments</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $totalEnrollments }}
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
                                        <h4>Completed Courses</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $completedCourses }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-warning">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Spent</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ currency_format($totalSpent) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-info">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Active Enrollments</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $activeEnrollments }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile"
                                role="tab">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="enrollments-tab" data-toggle="tab" href="#enrollments"
                                role="tab">Enrollments ({{ $totalEnrollments }})</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="reviews-tab" data-toggle="tab" href="#reviews" role="tab">Reviews
                                ({{ $student->reviews->count() }})</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="studentTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="mt-4">
                                <h5>Profile Information</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Contact Information</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2">
                                                <strong>Email:</strong> {{ $student->email }}
                                            </li>
                                            @if ($student->phone)
                                                <li class="mb-2">
                                                    <strong>Phone:</strong> {{ $student->phone }}
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Account Information</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2">
                                                <strong>Member Since:</strong> {{ $student->created_at->format('M d, Y') }}
                                            </li>
                                            <li class="mb-2">
                                                <strong>Last Updated:</strong>
                                                {{ $student->updated_at->format('M d, Y h:i A') }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollments Tab -->
                        <div class="tab-pane fade" id="enrollments" role="tabpanel">
                            <div class="mt-4">
                                <h5>Student Enrollments</h5>
                                @if ($enrollmentsWithProgress->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Instructor</th>
                                                    <th>Status</th>
                                                    <th>Progress</th>
                                                    <th>Amount Paid</th>
                                                    <th>Enrolled Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($enrollmentsWithProgress as $enrollment)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                @if ($enrollment->course && $enrollment->course->featured_image)
                                                                    <img src="{{ asset('storage/' . $enrollment->course->featured_image) }}"
                                                                        alt="{{ $enrollment->course->title }}"
                                                                        class="mr-3 rounded course-thumb-sm">
                                                                @else
                                                                    <div
                                                                        class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center course-thumb-placeholder">
                                                                        <i class="fas fa-book text-white"></i>
                                                                    </div>
                                                                @endif
                                                                <div>
                                                                    <h6 class="mb-0">
                                                                        {{ $enrollment->course->title ?? 'N/A' }}</h6>
                                                                    <small
                                                                        class="text-muted">{{ $enrollment->course->category ?? 'N/A' }}</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            {{ $enrollment->course->instructor->name ?? 'N/A' }}
                                                        </td>
                                                        <td>
                                                            @if ($enrollment->status === 'completed')
                                                                <span class="badge badge-success">Completed</span>
                                                            @elseif($enrollment->status === 'approved')
                                                                <span class="badge badge-primary">Active</span>
                                                            @elseif($enrollment->status === 'pending')
                                                                <span class="badge badge-warning">Pending</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-secondary">{{ ucfirst($enrollment->status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="progress progress-vertical-md">
                                                                <div class="progress-bar" role="progressbar"
                                                                    style="width: {{ $enrollment->progress_percentage }}%;"
                                                                    aria-valuenow="{{ $enrollment->progress_percentage }}"
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    {{ $enrollment->progress_percentage }}%
                                                                </div>
                                                            </div>
                                                            <small
                                                                class="text-muted">{{ $enrollment->completed_lessons ?? 0 }}
                                                                / {{ $enrollment->total_lessons ?? 0 }} lessons</small>
                                                        </td>
                                                        <td>
                                                            @if ($enrollment->payment)
                                                                <strong>{{ currency_format($enrollment->payment->amount) }}</strong>
                                                                <br><small
                                                                    class="text-muted">{{ ucfirst($enrollment->payment->payment_method) }}</small>
                                                            @else
                                                                <span class="text-muted">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <small>{{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('M d, Y') : $enrollment->created_at->format('M d, Y') }}</small>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> This student has no enrollments yet.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <div class="mt-4">
                                <h5>Student Reviews</h5>
                                @if ($student->reviews->count() > 0)
                                    <div class="list-group">
                                        @foreach ($student->reviews as $review)
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">{{ $review->course->title ?? 'N/A' }}</h6>
                                                        <div class="mb-2">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <i
                                                                    class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                                                            @endfor
                                                            <span class="ml-2">{{ $review->rating }}/5</span>
                                                        </div>
                                                        @if ($review->comment)
                                                            <p class="mb-1">{{ $review->comment }}</p>
                                                        @endif
                                                        <small
                                                            class="text-muted">{{ $review->created_at->format('M d, Y h:i A') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> This student has not written any reviews yet.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
