@extends('layouts.instructor')

@section('content')
    <!-- Instructor Submission Review Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 25px;">
                        <div class="section-title">
                            <h6>Assignments</h6>
                            <h2>Review Submission</h2>
                            <p>Review student work, provide feedback, and update grading status.</p>
                        </div>
                        <div class="section-actions">
                            <a href="{{ route('instructor.assignments.show', $assignment->id) }}" class="breadcrumb-back">
                                <i class="fa-solid fa-arrow-left-long"></i>
                                Back to {{ $assignment->title }}
                            </a>
                        </div>
                    </div>

                    <!-- Submission Header -->
                    <div class="submission-overview-card wow fadeInUp" data-wow-delay=".05s">
                        <div class="submission-header d-flex flex-row justify-content-between align-items-center">
                            <div class="student-profile">
                                <div class="student-avatar">
                                    <img src="{{ $submission->user->profile_photo ? asset('storage/' . $submission->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}"
                                        alt="{{ $submission->user->name }}">
                                </div>
                                <div>
                                    <h4>{{ $submission->user->name }}</h4>
                                </div>
                            </div>
                            <div class="submission-meta">
                                <div>
                                    <span class="meta-label">Submitted</span>
                                    <span
                                        class="meta-value">{{ optional($submission->submitted_at)->format('M d, Y h:i A') ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="meta-label">Status</span>
                                    <span class="status-badge-table {{ $statusClasses[$submission->status] ?? 'pending' }}">
                                        {{ $statusOptions[$submission->status] ?? ucfirst($submission->status) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="meta-label">Grade</span>
                                    <span class="meta-value">
                                        {{ $submission->grade !== null ? $submission->grade . '%' : 'Not graded' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submission-content-layout">
                        <!-- Submission Content -->
                        <div class="submission-content wow fadeInUp" data-wow-delay=".1s">
                            <div class="submission-section">
                                <h5>Submission Content</h5>
                                @if ($submission->submission_text)
                                    <div class="submission-text">
                                        {!! nl2br(e($submission->submission_text)) !!}
                                    </div>
                                @else
                                    <p class="submission-empty">No written response provided.</p>
                                @endif
                            </div>

                            <div class="submission-section">
                                <h5>Uploaded Files</h5>
                                @if ($submission->files->isNotEmpty())
                                    <ul class="submission-files">
                                        @foreach ($submission->files as $file)
                                            <li>
                                                <i class="fa-solid fa-file"></i>
                                                <div class="file-details">
                                                    <a href="{{ $file->file_url }}" target="_blank" rel="noopener">
                                                        {{ $file->original_filename }}
                                                    </a>
                                                    <span class="file-meta">
                                                        {{ strtoupper(pathinfo($file->original_filename, PATHINFO_EXTENSION)) ?: 'FILE' }}
                                                        • {{ number_format(($file->file_size ?? 0) / 1024, 1) }} KB
                                                    </span>
                                                </div>
                                                <a class="file-download" href="{{ $file->file_url }}" target="_blank"
                                                    rel="noopener" download>
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="submission-empty">No files were uploaded for this submission.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Grading Form -->
                        <div class="submission-grading-card wow fadeInUp" data-wow-delay=".15s">
                            <h5>Grade & Feedback</h5>
                            <form method="POST"
                                action="{{ route('instructor.assignments.submissions.update', $submission->id) }}"
                                class="grading-form">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <div class="filter-select">
                                        <select id="status" name="status" class="nice-select">
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('status', $submission->status) === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                    @error('status')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="grade">Grade (%)</label>
                                    <input type="number" id="grade" name="grade" min="0" max="100"
                                        step="1" class="form-control" value="{{ old('grade', $submission->grade) }}"
                                        placeholder="Enter a value between 0 and 100">
                                    @error('grade')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="instructor_notes">Feedback</label>
                                    <textarea id="instructor_notes" name="instructor_notes" rows="5" class="form-control"
                                        placeholder="Provide feedback or instructions for the student">{{ old('instructor_notes', $submission->instructor_notes) }}</textarea>
                                    @error('instructor_notes')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grading-form-actions">
                                    <button type="submit" class="primary-btn">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Save Updates
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Instructor Submission Review Section End -->
@endsection
