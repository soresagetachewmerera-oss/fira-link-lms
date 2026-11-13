@extends('layouts.admin')

@section('title', $pageTitle ?? 'Assignment Submission Details')

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
                    <h2 class="section-title mb-1">Submission #{{ $submission->id }}</h2>
                    <p class="section-lead mb-0 mt-2">
                        Review the learner's submission, attachments, grading status, and instructor feedback.
                    </p>
                </div>
                <div class="mb-3 mb-md-0">
                    <a href="{{ route('admin.assignment-submissions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Submissions
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
                        @if ($submission->user && $submission->user->profile_photo)
                            <img src="{{ asset('storage/' . $submission->user->profile_photo) }}"
                                alt="{{ $submission->user->name }}" class="rounded-circle mb-3"
                                style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        @endif
                        <h5 class="mb-1">{{ $submission->user->name ?? 'Unknown Student' }}</h5>
                        <p class="text-muted mb-0">{{ $submission->user->email ?? 'Not Available' }}</p>
                        @if ($submission->enrollment && $submission->enrollment->status)
                            <span class="badge badge-info mt-2">{{ ucfirst($submission->enrollment->status) }}
                                Enrollment</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Assignment</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-1">{{ $submission->assignment->title ?? 'Assignment not available' }}</h5>
                        @if (optional($submission->assignment)->description)
                            <p class="text-muted small">{{ Str::limit($submission->assignment->description, 140) }}</p>
                        @endif
                        @php
                            $course =
                                $submission->assignment->topic->course ?? ($submission->enrollment->course ?? null);
                        @endphp
                        <div class="mt-3">
                            <h6 class="text-uppercase text-muted mb-1">Course</h6>
                            <p class="mb-1">{{ $course->title ?? 'N/A' }}</p>
                            <small class="text-muted">
                                Instructor: {{ $course->instructor->name ?? 'N/A' }}
                            </small>
                        </div>
                        <div class="mt-3">
                            <h6 class="text-uppercase text-muted mb-1">Topic</h6>
                            <p class="mb-0">{{ optional(optional($submission->assignment)->topic)->title ?? 'N/A' }}</p>
                        </div>
                        @if (optional($submission->assignment)->instructions)
                            <div class="mt-3">
                                <h6 class="text-uppercase text-muted mb-1">Instructions</h6>
                                <p class="mb-0 text-muted">{{ Str::limit($submission->assignment->instructions, 160) }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Submission Summary</h4>
                    </div>
                    <div class="card-body">
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
                        @endphp
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Status</span>
                            <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Grade</span>
                            @if ($submission->grade !== null)
                                <h3 class="mb-0">{{ $submission->grade }}%</h3>
                            @else
                                <h3 class="mb-0 text-muted">—</h3>
                            @endif
                        </div>
                        <div class="mb-3">
                            <span class="text-uppercase text-muted small d-block">Submitted</span>
                            <p class="mb-0">
                                {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : $submission->created_at->format('M d, Y h:i A') }}
                            </p>
                        </div>
                        <div class="mb-0">
                            <span class="text-uppercase text-muted small d-block">Files Attached</span>
                            <p class="mb-0">{{ $submission->files->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Student Submission</h4>
                    </div>
                    <div class="card-body">
                        @if ($submission->submission_text)
                            <div class="submission-text" style="white-space: pre-wrap;">
                                {{ $submission->submission_text }}
                            </div>
                        @else
                            <p class="text-muted mb-0">No written submission provided.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Instructor Notes</h4>
                    </div>
                    <div class="card-body">
                        @if ($submission->instructor_notes)
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $submission->instructor_notes }}</p>
                        @else
                            <p class="text-muted mb-0">No instructor notes available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Attachment Files</h4>
                        <div class="card-header-action">
                            <span class="badge badge-primary">
                                {{ $submission->files->count() }} {{ Str::plural('file', $submission->files->count()) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($submission->files->isNotEmpty())
                            <div class="list-group">
                                @foreach ($submission->files as $file)
                                    @php
                                        $sizeInKb = $file->file_size ? round($file->file_size / 1024, 1) : null;
                                    @endphp
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $file->original_filename ?? 'Attachment' }}</h6>
                                            <small class="text-muted">
                                                {{ $file->mime_type ?? 'Unknown type' }}
                                                @if ($sizeInKb)
                                                    · {{ $sizeInKb }} KB
                                                @endif
                                            </small>
                                        </div>
                                        <div>
                                            <a href="{{ asset('storage/' . $file->file_path) }}"
                                                class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                                                <i class="fas fa-download mr-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-paperclip fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No files were attached to this submission.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
