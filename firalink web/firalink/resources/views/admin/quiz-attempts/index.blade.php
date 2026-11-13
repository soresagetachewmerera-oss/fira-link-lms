@extends('layouts.admin')

@section('title', $pageTitle ?? 'Quiz Attempts')

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
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Attempts</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($totalAttempts) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pass Rate</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($passRate, 1) }}%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Average Score</h4>
                    </div>
                    <div class="card-body">
                        {{ $averageScore !== null ? number_format($averageScore, 1) . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Unique Students</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($uniqueStudents) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-secondary">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Passed Attempts</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($passedAttempts) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Failed Attempts</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($failedAttempts) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-dark">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Attempts Today</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($todayAttempts) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <h5 class="mb-3 mb-md-0">Quiz Attempts</h5>
                        <span class="badge badge-primary">
                            Showing {{ $attempts->total() }} {{ Str::plural('attempt', $attempts->total()) }}
                        </span>
                    </div>
                    <form method="GET" action="{{ route('admin.quiz-attempts.index') }}" id="quizAttemptsFilterForm">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search attempts..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="result"
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
                                    <option value="">All Results</option>
                                    <option value="passed" {{ request('result') === 'passed' ? 'selected' : '' }}>Passed
                                    </option>
                                    <option value="failed" {{ request('result') === 'failed' ? 'selected' : '' }}>Failed
                                    </option>
                                    <option value="in_progress"
                                        {{ request('result') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="course_id"
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
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
                                <select class="form-control" name="quiz_id"
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
                                    <option value="">All Quizzes</option>
                                    @foreach ($quizzes as $quiz)
                                        <option value="{{ $quiz->id }}"
                                            {{ (string) request('quiz_id') === (string) $quiz->id ? 'selected' : '' }}>
                                            {{ Str::limit($quiz->title, 30) }}
                                            @if ($quiz->topic && $quiz->topic->course)
                                                ({{ Str::limit($quiz->topic->course->title, 22) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="instructor_id"
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
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
                                        'result',
                                        'course_id',
                                        'quiz_id',
                                        'student_id',
                                        'instructor_id',
                                        'date_from',
                                        'date_to',
                                    ]))
                                    <a href="{{ route('admin.quiz-attempts.index') }}"
                                        class="btn btn-outline-secondary btn-block" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <select class="form-control" name="student_id"
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
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
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_to"
                                    value="{{ request('date_to') }}"
                                    onchange="document.getElementById('quizAttemptsFilterForm').submit()">
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Quiz</th>
                                    <th>Course</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                    <th>Time Taken</th>
                                    <th>Completed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attempts as $attempt)
                                    <tr>
                                        <td>#{{ $attempt->id }}</td>
                                        <td>
                                            @if ($attempt->user)
                                                <div class="media align-items-center">
                                                    @if ($attempt->user->profile_photo)
                                                        <img class="mr-3 rounded-circle"
                                                            src="{{ asset('storage/' . $attempt->user->profile_photo) }}"
                                                            alt="{{ $attempt->user->name }}"
                                                            style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div class="media-body">
                                                        <h6 class="mb-0">{{ Str::limit($attempt->user->name, 28) }}</h6>
                                                        <small
                                                            class="text-muted">{{ Str::limit($attempt->user->email, 40) }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attempt->quiz)
                                                <div>
                                                    <strong>{{ Str::limit($attempt->quiz->title, 32) }}</strong><br>
                                                    <small class="text-muted">Passing:
                                                        {{ $attempt->quiz->passing_score ?? '—' }}%</small>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $course =
                                                    $attempt->quiz->topic->course ??
                                                    ($attempt->enrollment->course ?? null);
                                            @endphp
                                            @if ($course)
                                                <div>
                                                    <strong>{{ Str::limit($course->title, 32) }}</strong><br>
                                                    <small class="text-muted">
                                                        Instructor:
                                                        {{ Str::limit($course->instructor->name ?? 'N/A', 28) }}
                                                    </small>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attempt->score !== null)
                                                @php
                                                    $scoreBadge =
                                                        $attempt->score >= ($attempt->quiz->passing_score ?? 0)
                                                            ? 'badge-success'
                                                            : 'badge-warning';
                                                @endphp
                                                <span
                                                    class="badge {{ $scoreBadge }} badge-pill">{{ $attempt->score }}%</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attempt->passed === true)
                                                <span class="badge badge-success">Passed</span>
                                            @elseif($attempt->passed === false)
                                                <span class="badge badge-danger">Failed</span>
                                            @else
                                                <span class="badge badge-secondary">In Progress</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $attempt->time_taken ? gmdate('H:i:s', $attempt->time_taken) : '—' }}
                                        </td>
                                        <td>
                                            @if ($attempt->completed_at)
                                                <small>{{ $attempt->completed_at->format('M d, Y h:i A') }}</small>
                                            @else
                                                <span class="text-muted">Not completed</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.quiz-attempts.show', $attempt) }}"
                                                class="btn btn-sm btn-info" title="View details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">No quiz attempts found for the selected filters.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $attempts->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
