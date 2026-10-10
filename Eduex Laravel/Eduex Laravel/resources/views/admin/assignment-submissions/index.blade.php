@extends('layouts.admin')

@section('title', $pageTitle ?? 'Assignment Submissions')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        @foreach ($breadcrumbItems as $item)
            @if ($item['route'])
                <div class="breadcrumb-item {{ $loop->last ? '' : 'active' }}">
                    <a href="{{ route($item['route']) }}">{{ $item['name'] }}</a>
                </div>
            @else
                <div class="breadcrumb-item">{{ $item['name'] }}</div>
            @endif
        @endforeach
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Submissions</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($totalSubmissions) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Review</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($pendingSubmissions) }}
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
                        <h4>Graded</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($gradedSubmissions) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Returned</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($returnedSubmissions) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 col-md-6 col-sm-12 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-secondary">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Average Grade</h4>
                    </div>
                    <div class="card-body">
                        {{ $averageGrade !== null ? number_format($averageGrade, 1) . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-dark">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Submitted Today</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($todaySubmissions) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <h5 class="mb-3 mb-md-0">Assignment Submissions</h5>
                        <span class="badge badge-primary">
                            Showing {{ $submissions->total() }} {{ Str::plural('submission', $submissions->total()) }}
                        </span>
                    </div>
                    <form method="GET" action="{{ route('admin.assignment-submissions.index') }}"
                        id="assignmentSubmissionsFilterForm">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search submissions..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="status"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="graded" {{ request('status') === 'graded' ? 'selected' : '' }}>Graded
                                    </option>
                                    <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>
                                        Returned</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="course_id"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ (string) request('course_id') === (string) $course->id ? 'selected' : '' }}>
                                            {{ Str::limit($course->title, 32) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="assignment_id"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                                    <option value="">All Assignments</option>
                                    @foreach ($assignments as $assignment)
                                        <option value="{{ $assignment->id }}"
                                            {{ (string) request('assignment_id') === (string) $assignment->id ? 'selected' : '' }}>
                                            {{ Str::limit($assignment->title, 30) }}
                                            @if ($assignment->topic && $assignment->topic->course)
                                                ({{ Str::limit($assignment->topic->course->title, 18) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="instructor_id"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                                    <option value="">All Instructors</option>
                                    @foreach ($instructors as $instructor)
                                        <option value="{{ $instructor->id }}"
                                            {{ (string) request('instructor_id') === (string) $instructor->id ? 'selected' : '' }}>
                                            {{ Str::limit($instructor->name, 26) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1 text-right">
                                @if (request()->anyFilled([
                                        'search',
                                        'status',
                                        'course_id',
                                        'assignment_id',
                                        'student_id',
                                        'instructor_id',
                                        'date_from',
                                        'date_to',
                                    ]))
                                    <a href="{{ route('admin.assignment-submissions.index') }}"
                                        class="btn btn-outline-secondary btn-block" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <select class="form-control" name="student_id"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                                    <option value="">All Students</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}"
                                            {{ (string) request('student_id') === (string) $student->id ? 'selected' : '' }}>
                                            {{ Str::limit($student->name, 32) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_from"
                                    value="{{ request('date_from') }}"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_to"
                                    value="{{ request('date_to') }}"
                                    onchange="document.getElementById('assignmentSubmissionsFilterForm').submit()">
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Grade</th>
                                    <th>Submitted</th>
                                    <th>Files</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($submissions as $submission)
                                    @php
                                        $statusStyles = [
                                            'pending' => ['label' => 'Pending Review', 'class' => 'badge-warning'],
                                            'graded' => ['label' => 'Graded', 'class' => 'badge-success'],
                                            'returned' => ['label' => 'Returned', 'class' => 'badge-info'],
                                        ];
                                        $statusMeta = $statusStyles[$submission->status] ?? [
                                            'label' => ucfirst($submission->status ?? 'Unknown'),
                                            'class' => 'badge-secondary',
                                        ];
                                        $course =
                                            $submission->assignment->topic->course ??
                                            ($submission->enrollment->course ?? null);
                                    @endphp
                                    <tr>
                                        <td>#{{ $submission->id }}</td>
                                        <td>
                                            @if ($submission->user)
                                                <div class="media align-items-center">
                                                    @if ($submission->user->profile_photo)
                                                        <img class="mr-3 rounded-circle"
                                                            src="{{ asset('storage/' . $submission->user->profile_photo) }}"
                                                            alt="{{ $submission->user->name }}"
                                                            style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div class="media-body">
                                                        <h6 class="mb-0">{{ Str::limit($submission->user->name, 28) }}
                                                        </h6>
                                                        <small
                                                            class="text-muted">{{ Str::limit($submission->user->email, 40) }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ Str::limit($submission->assignment->title ?? 'N/A', 32) }}</strong><br>
                                                <small class="text-muted">Topic:
                                                    {{ Str::limit(optional(optional($submission->assignment)->topic)->title ?? 'N/A', 28) }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($course)
                                                <div>
                                                    <strong>{{ Str::limit($course->title, 32) }}</strong><br>
                                                    <small class="text-muted">Instructor:
                                                        {{ Str::limit($course->instructor->name ?? 'N/A', 28) }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                        </td>
                                        <td>
                                            @if ($submission->grade !== null)
                                                <span
                                                    class="badge badge-pill badge-primary">{{ $submission->grade }}%</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($submission->submitted_at)
                                                <small>{{ $submission->submitted_at->format('M d, Y h:i A') }}</small>
                                            @else
                                                <small
                                                    class="text-muted">{{ $submission->created_at->format('M d, Y h:i A') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light">
                                                <i class="fas fa-paperclip mr-1"></i>{{ $submission->files_count }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.assignment-submissions.show', $submission) }}"
                                                class="btn btn-sm btn-info" title="View details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">No assignment submissions found for the selected
                                                    filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $submissions->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
