@extends('layouts.admin')

@section('title', $pageTitle ?? 'Quiz Attempt Details')

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
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="section-title mb-1">Quiz Attempt #{{ $attempt->id }}</h2>
                    <p class="section-lead mb-0 mt-2">
                        Review the attempt details, student performance and question level insights.
                    </p>
                </div>
                <div class="mb-3 mb-md-0">
                    <a href="{{ route('admin.quiz-attempts.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Attempts
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Student</h4>
                    </div>
                    <div class="card-body text-center">
                        @if ($attempt->user && $attempt->user->profile_photo)
                            <img src="{{ asset('storage/' . $attempt->user->profile_photo) }}"
                                alt="{{ $attempt->user->name }}" class="rounded-circle mb-3"
                                style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        @endif
                        <h5 class="mb-1">{{ $attempt->user->name ?? 'Unknown Student' }}</h5>
                        <p class="text-muted mb-0">{{ $attempt->user->email ?? 'Not Available' }}</p>
                        @if ($attempt->enrollment && $attempt->enrollment->status)
                            <span class="badge badge-info mt-2">{{ ucfirst($attempt->enrollment->status) }}
                                Enrollment</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Quiz & Course</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-1">{{ $attempt->quiz->title ?? 'Quiz not available' }}</h5>
                        @if (optional($attempt->quiz)->description)
                            <p class="text-muted small">{{ Str::limit($attempt->quiz->description, 120) }}</p>
                        @endif
                        @php
                            $course = $attempt->quiz->topic->course ?? ($attempt->enrollment->course ?? null);
                        @endphp
                        <div class="mt-3">
                            <h6 class="text-uppercase text-muted mb-1">Course</h6>
                            <p class="mb-1">
                                {{ $course->title ?? 'N/A' }}
                            </p>
                            <small class="text-muted">
                                Instructor: {{ $course->instructor->name ?? 'N/A' }}
                            </small>
                        </div>
                        <div class="mt-3">
                            <h6 class="text-uppercase text-muted mb-1">Topic</h6>
                            <p class="mb-0">{{ optional($attempt->quiz->topic)->title ?? 'N/A' }}</p>
                        </div>
                        <div class="mt-3">
                            <h6 class="text-uppercase text-muted mb-1">Passing Score</h6>
                            <p class="mb-0">{{ optional($attempt->quiz)->passing_score ?? '—' }}%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Attempt Summary</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Score</span>
                            @if ($attempt->score !== null)
                                <h3 class="mb-0">{{ $attempt->score }}%</h3>
                            @else
                                <h3 class="mb-0 text-muted">—</h3>
                            @endif
                        </div>
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Result</span>
                            @if ($attempt->passed === true)
                                <span class="badge badge-success">Passed</span>
                            @elseif($attempt->passed === false)
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-secondary">In Progress</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Time Taken</span>
                            <p class="mb-0">{{ $attempt->time_taken ? gmdate('H:i:s', $attempt->time_taken) : '—' }}</p>
                        </div>
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Started</span>
                            <p class="mb-0">
                                {{ $attempt->started_at ? $attempt->started_at->format('M d, Y h:i A') : '—' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-uppercase text-muted small d-block">Completed</span>
                            <p class="mb-0">
                                {{ $attempt->completed_at ? $attempt->completed_at->format('M d, Y h:i A') : 'Not completed' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Question Review</h4>
                        <div class="card-header-action">
                            <span class="badge badge-primary">
                                {{ $attempt->answers->count() }} {{ Str::plural('response', $attempt->answers->count()) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($attempt->answers as $answer)
                            @php
                                $question = $answer->question;
                                $options = $question->options ?? [];
                                $selectedIndex = $answer->selected_answer;
                                $correctIndex = $question->correct_answer;
                                $selectedText =
                                    $selectedIndex !== null && array_key_exists($selectedIndex, $options)
                                        ? $options[$selectedIndex]
                                        : null;
                                $correctText =
                                    $correctIndex !== null && array_key_exists($correctIndex, $options)
                                        ? $options[$correctIndex]
                                        : null;
                                $optionLabel = function ($index) {
                                    return chr(65 + $index);
                                };
                            @endphp
                            <div class="border rounded mb-4 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">
                                        Question {{ $loop->iteration }}
                                    </h5>
                                    @if ($answer->is_correct)
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Correct</span>
                                    @elseif($selectedIndex !== null)
                                        <span class="badge badge-danger"><i class="fas fa-times mr-1"></i> Incorrect</span>
                                    @else
                                        <span class="badge badge-secondary">Not Answered</span>
                                    @endif
                                </div>
                                <p class="mb-3">{{ $question->question }}</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <span class="text-uppercase text-muted small d-block">Selected Answer</span>
                                            @if ($selectedText !== null)
                                                <p class="mb-0">
                                                    <strong>{{ $optionLabel($selectedIndex) }}.</strong>
                                                    {{ $selectedText }}
                                                </p>
                                            @else
                                                <p class="mb-0 text-muted">No answer submitted.</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <span class="text-uppercase text-muted small d-block">Correct Answer</span>
                                            @if ($correctText !== null)
                                                <p class="mb-0">
                                                    <strong>{{ $optionLabel($correctIndex) }}.</strong>
                                                    {{ $correctText }}
                                                </p>
                                            @else
                                                <p class="mb-0 text-muted">Not configured.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if (!empty($options))
                                    <div class="mt-3">
                                        <span class="text-uppercase text-muted small d-block">Options</span>
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($options as $idx => $option)
                                                <li class="mb-1">
                                                    <strong>{{ $optionLabel($idx) }}.</strong> {{ $option }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No answer data available for this attempt.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
