@extends('layouts.admin')

@section('title', 'Enrollment Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.enrollments.index') }}">Enrollments</a></div>
        <div class="breadcrumb-item">Enrollment Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Enrollment Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.enrollments.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Enrollments
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Enrollment Header -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h3>Enrollment #{{ $enrollment->id }}</h3>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Student:</strong> {{ $enrollment->user->name }}</p>
                                    <p class="mb-2"><strong>Email:</strong> {{ $enrollment->user->email }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Course:</strong> {{ $enrollment->course->title ?? 'N/A' }}</p>
                                    <p class="mb-2"><strong>Instructor:</strong>
                                        {{ $enrollment->course->instructor->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Status:</strong>
                                        <span
                                            class="badge badge-{{ $enrollment->status == 'completed' ? 'success' : ($enrollment->status == 'approved' ? 'primary' : ($enrollment->status == 'pending' ? 'warning' : 'danger')) }}">
                                            {{ ucfirst($enrollment->status) }}
                                        </span>
                                    </p>
                                    <p class="mb-2"><strong>Enrolled Date:</strong>
                                        {{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('M d, Y h:i A') : $enrollment->created_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Progress:</strong> {{ $progress }}%</p>
                                    <p class="mb-2"><strong>Lessons:</strong>
                                        {{ $completedLessons }}/{{ $totalLessons }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div
                                    class="card-icon bg-{{ $enrollment->status == 'completed' ? 'success' : ($enrollment->status == 'approved' ? 'primary' : ($enrollment->status == 'pending' ? 'warning' : 'danger')) }}">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Status</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ ucfirst($enrollment->status) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Progress</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $progress }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div
                                    class="card-icon bg-{{ $enrollment->payment && $enrollment->payment->status == 'completed' ? 'success' : 'warning' }}">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Payment</h4>
                                    </div>
                                    <div class="card-body">
                                        @if ($enrollment->payment)
                                            {{ currency_format($enrollment->payment->amount) }}
                                        @else
                                            Free
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-primary">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Enrolled</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('M d, Y') : $enrollment->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                @if ($enrollment->status == 'pending')
                                    <form action="{{ route('admin.enrollments.approve', $enrollment->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.enrollments.reject', $enrollment->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                @endif
                                @if ($enrollment->status == 'approved')
                                    <form action="{{ route('admin.enrollments.complete', $enrollment->id) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check-circle"></i> Mark as Completed
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.enrollments.cancel', $enrollment->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-ban"></i> Cancel
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.students.show', $enrollment->user_id) }}" class="btn btn-info">
                                    <i class="fas fa-user"></i> View Student
                                </a>
                                @if ($enrollment->course)
                                    <a href="{{ route('admin.courses.show', $enrollment->course_id) }}"
                                        class="btn btn-info">
                                        <i class="fas fa-book"></i> View Course
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Status Update Form -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <form action="{{ route('admin.enrollments.update-status', $enrollment->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label>Update Status</label>
                                    <select class="form-control" name="status" onchange="this.form.submit()">
                                        <option value="pending" {{ $enrollment->status == 'pending' ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="approved"
                                            {{ $enrollment->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="completed"
                                            {{ $enrollment->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled"
                                            {{ $enrollment->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="enrollmentTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview"
                                role="tab">Overview</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="student-tab" data-toggle="tab" href="#student"
                                role="tab">Student Details</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="course-tab" data-toggle="tab" href="#course" role="tab">Course
                                Details</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="payment-tab" data-toggle="tab" href="#payment"
                                role="tab">Payment Details</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="progress-tab" data-toggle="tab" href="#progress"
                                role="tab">Progress Tracking</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="quizzes-tab" data-toggle="tab" href="#quizzes" role="tab">Quiz
                                Attempts</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="assignments-tab" data-toggle="tab" href="#assignments"
                                role="tab">Assignments</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="enrollmentTabsContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="mt-4">
                                <h5>Enrollment Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Enrollment ID</th>
                                                <td>#{{ $enrollment->id }}</td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $enrollment->status == 'completed' ? 'success' : ($enrollment->status == 'approved' ? 'primary' : ($enrollment->status == 'pending' ? 'warning' : 'danger')) }}">
                                                        {{ ucfirst($enrollment->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Enrolled Date</th>
                                                <td>{{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('M d, Y h:i A') : $enrollment->created_at->format('M d, Y h:i A') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Created At</th>
                                                <td>{{ $enrollment->created_at->format('M d, Y h:i A') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Updated At</th>
                                                <td>{{ $enrollment->updated_at->format('M d, Y h:i A') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Progress Summary</h6>
                                        <div class="progress progress-vertical-lg mb-3">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}"
                                                aria-valuemin="0" aria-valuemax="100">
                                                {{ $progress }}%
                                            </div>
                                        </div>
                                        <p><strong>Lessons Completed:</strong> {{ $completedLessons }} /
                                            {{ $totalLessons }}</p>
                                        @if ($enrollment->payment)
                                            <p><strong>Payment Amount:</strong>
                                                {{ currency_format($enrollment->payment->amount) }}</p>
                                            <p><strong>Payment Method:</strong>
                                                {{ ucfirst($enrollment->payment->payment_method) }}</p>
                                            <p><strong>Payment Status:</strong>
                                                <span
                                                    class="badge badge-{{ $enrollment->payment->status == 'completed' ? 'success' : ($enrollment->payment->status == 'pending' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($enrollment->payment->status) }}
                                                </span>
                                            </p>
                                        @else
                                            <p><strong>Payment:</strong> Free Course</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student Details Tab -->
                        <div class="tab-pane fade" id="student" role="tabpanel">
                            <div class="mt-4">
                                <h5>Student Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Name</th>
                                                <td>{{ $enrollment->user->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td>{{ $enrollment->user->email }}</td>
                                            </tr>
                                            @if ($enrollment->user->phone)
                                                <tr>
                                                    <th>Phone</th>
                                                    <td>{{ $enrollment->user->phone }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th>Member Since</th>
                                                <td>{{ $enrollment->user->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        @if ($enrollment->user->profile_photo)
                                            <img src="{{ asset('storage/' . $enrollment->user->profile_photo) }}"
                                                alt="{{ $enrollment->user->name }}"
                                                class="img-fluid rounded-circle profile-photo-xl">
                                        @endif
                                    </div>
                                </div>

                                @if ($otherEnrollments->count() > 0)
                                    <h6 class="mt-4">Other Enrollments</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Status</th>
                                                    <th>Enrolled Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($otherEnrollments as $otherEnrollment)
                                                    <tr>
                                                        <td>{{ $otherEnrollment->course->title ?? 'N/A' }}</td>
                                                        <td>
                                                            <span
                                                                class="badge badge-{{ $otherEnrollment->status == 'completed' ? 'success' : ($otherEnrollment->status == 'approved' ? 'primary' : ($otherEnrollment->status == 'pending' ? 'warning' : 'danger')) }}">
                                                                {{ ucfirst($otherEnrollment->status) }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $otherEnrollment->enrolled_at ? $otherEnrollment->enrolled_at->format('M d, Y') : $otherEnrollment->created_at->format('M d, Y') }}
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('admin.enrollments.show', $otherEnrollment->id) }}"
                                                                class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Course Details Tab -->
                        <div class="tab-pane fade" id="course" role="tabpanel">
                            <div class="mt-4">
                                <h5>Course Information</h5>
                                @if ($enrollment->course)
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th width="40%">Course Title</th>
                                                    <td>{{ $enrollment->course->title }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Instructor</th>
                                                    <td>{{ $enrollment->course->instructor->name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Category</th>
                                                    <td>{{ $enrollment->course->category ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ $enrollment->course->status == 'published' ? 'success' : ($enrollment->course->status == 'pending' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst($enrollment->course->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Price</th>
                                                    <td>
                                                        @if ($enrollment->course->pricing_model == 'free')
                                                            <span class="badge badge-success">Free</span>
                                                        @else
                                                            {{ currency_format($enrollment->course->regular_price ?? 0) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>View Course</th>
                                                    <td>
                                                        <a href="{{ route('admin.courses.show', $enrollment->course_id) }}"
                                                            class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> View Course
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            @if ($enrollment->course->featured_image)
                                                <img src="{{ asset('storage/' . $enrollment->course->featured_image) }}"
                                                    alt="{{ $enrollment->course->title }}" class="img-fluid rounded">
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">Course information not available.</div>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Details Tab -->
                        <div class="tab-pane fade" id="payment" role="tabpanel">
                            <div class="mt-4">
                                <h5>Payment Information</h5>
                                @if ($enrollment->payment)
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Amount</th>
                                            <td>{{ currency_format($enrollment->payment->amount) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td>{{ ucfirst($enrollment->payment->payment_method) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span
                                                    class="badge badge-{{ $enrollment->payment->status == 'completed' ? 'success' : ($enrollment->payment->status == 'pending' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($enrollment->payment->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Payment Date</th>
                                            <td>{{ $enrollment->payment->created_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        @if ($enrollment->payment->notes)
                                            <tr>
                                                <th>Notes</th>
                                                <td>{{ $enrollment->payment->notes }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                    @if ($enrollment->payment->receipt_file)
                                        <a href="{{ asset('storage/' . $enrollment->payment->receipt_file) }}"
                                            target="_blank" class="btn btn-primary">
                                            <i class="fas fa-download"></i> Download Receipt
                                        </a>
                                    @endif
                                @else
                                    <div class="alert alert-info">This is a free course. No payment information available.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Progress Tracking Tab -->
                        <div class="tab-pane fade" id="progress" role="tabpanel">
                            <div class="mt-4">
                                <h5>Progress Tracking</h5>
                                <div class="mb-3">
                                    <h6>Overall Progress: {{ $progress }}%</h6>
                                    <div class="progress progress-vertical-lg">
                                        <div class="progress-bar" role="progressbar"
                                            style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                            {{ $progress }}%
                                        </div>
                                    </div>
                                    <p class="mt-2"><strong>Lessons Completed:</strong> {{ $completedLessons }} /
                                        {{ $totalLessons }}</p>
                                </div>

                                @if ($enrollment->lessonProgress->count() > 0)
                                    <h6>Lesson Progress</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Lesson</th>
                                                    <th>Status</th>
                                                    <th>Completed At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($enrollment->lessonProgress as $progress)
                                                    <tr>
                                                        <td>{{ $progress->lesson->title ?? 'N/A' }}</td>
                                                        <td>
                                                            @if ($progress->is_completed)
                                                                <span class="badge badge-success">Completed</span>
                                                            @else
                                                                <span class="badge badge-warning">In Progress</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $progress->completed_at ? \Carbon\Carbon::parse($progress->completed_at)->format('M d, Y h:i A') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">No lesson progress recorded yet.</div>
                                @endif
                            </div>
                        </div>

                        <!-- Quiz Attempts Tab -->
                        <div class="tab-pane fade" id="quizzes" role="tabpanel">
                            <div class="mt-4">
                                <h5>Quiz Attempts</h5>
                                @if ($enrollment->quizAttempts->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Quiz</th>
                                                    <th>Score</th>
                                                    <th>Status</th>
                                                    <th>Attempted At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($enrollment->quizAttempts as $attempt)
                                                    <tr>
                                                        <td>{{ $attempt->quiz->title ?? 'N/A' }}</td>
                                                        <td>{{ $attempt->score ?? 'N/A' }}%</td>
                                                        <td>
                                                            @if ($attempt->passed ?? false)
                                                                <span class="badge badge-success">Passed</span>
                                                            @else
                                                                <span class="badge badge-danger">Failed</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $attempt->created_at->format('M d, Y h:i A') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">No quiz attempts recorded yet.</div>
                                @endif
                            </div>
                        </div>

                        <!-- Assignment Submissions Tab -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <div class="mt-4">
                                <h5>Assignment Submissions</h5>
                                @if ($enrollment->assignmentSubmissions->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Assignment</th>
                                                    <th>Submitted At</th>
                                                    <th>Status</th>
                                                    <th>Grade</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($enrollment->assignmentSubmissions as $submission)
                                                    <tr>
                                                        <td>{{ $submission->assignment->title ?? 'N/A' }}</td>
                                                        <td>{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : 'N/A' }}
                                                        </td>
                                                        <td>
                                                            @if ($submission->status == 'pending')
                                                                <span class="badge badge-warning">Pending</span>
                                                            @elseif($submission->status == 'graded')
                                                                <span class="badge badge-success">Graded</span>
                                                            @elseif($submission->status == 'returned')
                                                                <span class="badge badge-info">Returned</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-secondary">{{ ucfirst($submission->status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $submission->grade ?? 'N/A' }}</td>

                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">No assignment submissions recorded yet.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
