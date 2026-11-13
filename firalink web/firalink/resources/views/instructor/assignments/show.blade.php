@extends('layouts.instructor')

@section('content')
    <!-- Instructor Assignment Detail Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 25px;">
                        <div class="section-title">
                            <h6>{{ __('instructor.assignments') }}</h6>
                            <h2>{{ $assignment->title }}</h2>
                            <p>{{ __('instructor.review_grade_subtitle') }}</p>
                        </div>
                        <div class="section-actions">
                            <a href="{{ route('instructor.assignments.index') }}" class="breadcrumb-back">
                                <i class="fa-solid fa-arrow-left-long"></i>
                                {{ __('instructor.back_to_assignments') }}
                            </a>
                        </div>
                    </div>

                    <!-- Assignment Overview -->
                    <div class="assignment-overview-card wow fadeInUp" data-wow-delay=".05s">
                        <div class="overview-header">
                            <div class="overview-row">
                                <div>
                                    <span class="overview-label">{{ __('instructor.course') }}</span>
                                    <h4>{{ optional($assignment->topic->course)->title ?? __('instructor.unknown_course') }}</h4>
                                </div>
                                <div>
                                    <span class="overview-label">{{ __('instructor.topic') }}</span>
                                    <h4>{{ optional($assignment->topic)->title ?? __('instructor.no_topic') }}</h4>
                                </div>
                                <div>
                                    <span class="overview-label">{{ __('instructor.total_submissions') }}</span>
                                    <h4>{{ $assignment->submissions_count }}</h4>
                                </div>
                            </div>
                            <div class="overview-row">
                                <div class="overview-stat pending">
                                    <span class="stat-label">{{ __('instructor.status_pending') }}</span>
                                    <span class="stat-value">{{ $assignment->pending_submissions_count }}</span>
                                </div>
                                <div class="overview-stat graded">
                                    <span class="stat-label">{{ __('instructor.graded') }}</span>
                                    <span class="stat-value">{{ $assignment->graded_submissions_count }}</span>
                                </div>
                                <div class="overview-stat returned">
                                    <span class="stat-label">{{ __('instructor.returned') }}</span>
                                    <span class="stat-value">{{ $assignment->returned_submissions_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="overview-body">
                            @if ($assignment->instructions)
                                <div class="overview-section">
                                    <h5>{{ __('instructor.instructions') }}</h5>
                                    <div class="assignment-content">
                                        {!! $assignment->instructions !!}
                                    </div>
                                </div>
                            @elseif($assignment->description)
                                <div class="overview-section">
                                    <h5>{{ __('instructor.description_label') }}</h5>
                                    <div class="assignment-content">
                                        {!! $assignment->description !!}
                                    </div>
                                </div>
                            @else
                                <div class="overview-section">
                                    <p>{{ __('instructor.no_instructions_desc') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="submissions-filter-card wow fadeInUp" data-wow-delay=".1s">
                        <form method="GET" action="{{ route('instructor.assignments.show', $assignment->id) }}"
                            class="submissions-filter-form">
                            <div class="filter-row d-flex flex-wrap gap-3">
                                <div class="filter-group">
                                    <label for="status-filter">{{ __('instructor.status_col') }}</label>
                                    <div class="filter-select">
                                        <select id="status-filter" name="status" class="nice-select">
                                            <option value="">{{ __('instructor.select') }}</option>
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ $statusFilter === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-group">
                                    <label for="submission-search">{{ __('instructor.search') }}</label>
                                    <div class="filter-search">
                                        <input type="search" id="submission-search" name="search" class="search-input"
                                            value="{{ $searchTerm }}" placeholder="{{ __('instructor.search_student_placeholder') }}">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-filter"></i>
                                    {{ __('instructor.apply_filters') }}
                                </button>
                                @if ($statusFilter || $searchTerm)
                                    <a href="{{ route('instructor.assignments.show', $assignment->id) }}"
                                        class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        {{ __('instructor.reset') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Submissions Table -->
                    <div class="submissions-table-wrapper wow fadeInUp" data-wow-delay=".15s">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>{{ __('instructor.student_col') }}</th>
                                    <th>{{ __('instructor.submitted') }}</th>
                                    <th>{{ __('instructor.status_col') }}</th>
                                    <th>{{ __('instructor.grade') }}</th>
                                    <th>{{ __('instructor.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($submissions as $submission)
                                    <tr>
                                        <td>
                                            <div class="student-table-info">
                                                <div class="student-avatar-small">
                                                    <img src="{{ $submission->user->profile_photo ? asset('storage/' . $submission->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}"
                                                        alt="{{ $submission->user->name }}">
                                                </div>
                                                <div class="student-details">
                                                    <div class="student-name">{{ $submission->user->name }}</div>

                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="table-value">
                                                {{ optional($submission->submitted_at)->format('M d, Y h:i A') ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge-table {{ $submission->status_class }}">
                                                {{ $statusOptions[$submission->status] ?? ucfirst($submission->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="table-value">
                                                {{ $submission->grade !== null ? $submission->grade . '%' : __('instructor.not_graded') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="{{ route('instructor.assignments.submissions.show', $submission->id) }}"
                                                    class="table-action-btn" title="{{ __('instructor.review_submission') }}">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="dashboard-empty-state">
                                                <i class="fa-solid fa-inbox"></i>
                                                <h3>{{ __('instructor.no_submissions_yet') }}</h3>
                                                <p>{{ __('instructor.no_submissions_desc') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($submissions->hasPages())
                        <div class="table-pagination">
                            <div class="pagination-info">
                                {{ __('instructor.showing_submissions', ['from' => $submissions->firstItem(), 'to' => $submissions->lastItem(), 'total' => $submissions->total()]) }}
                            </div>
                            <div class="pagination-links">
                                @if ($submissions->onFirstPage())
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('instructor.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $submissions->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('instructor.previous') }}
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @foreach ($submissions->getUrlRange(max(1, $submissions->currentPage() - 2), min($submissions->lastPage(), $submissions->currentPage() + 2)) as $page => $url)
                                        @if ($page == $submissions->currentPage())
                                            <span class="pagination-number active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}"
                                                class="pagination-number">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                </div>

                                @if ($submissions->hasMorePages())
                                    <a href="{{ $submissions->nextPageUrl() }}" class="pagination-btn">
                                        {{ __('instructor.next') }}
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        {{ __('instructor.next') }}
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!-- Instructor Assignment Detail Section End -->
@endsection
