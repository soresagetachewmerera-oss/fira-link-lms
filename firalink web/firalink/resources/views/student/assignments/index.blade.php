@extends('layouts.student')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('student.my_assignments_title') }}</h6>
                            <h2>{{ __('student.my_assignments_title') }}</h2>
                            <p>{{ __('student.assignments_desc') }}</p>
                        </div>
                    </div>

                    <div class="dashboard-stats-minimal" style="margin-bottom: 40px;">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['total_submissions'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.total_submissions') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-paper-plane"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['graded_assignments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.graded_label') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">
                                    {{ $summary['pending_assignments'] + $summary['returned_assignments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.awaiting_feedback') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="assignments-filter-card wow fadeInUp" data-wow-delay=".1s">
                        <form method="GET" action="{{ route('student.assignments') }}" class="assignments-filter-form">
                            <div class="filter-group">
                                <label for="student-assignment-course">{{ __('student.course_label') }}</label>
                                <div class="filter-select">
                                    <select id="student-assignment-course" name="course_id" class="nice-select">
                                        <option value="">{{ __('student.all_courses') }}</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}"
                                                {{ $selectedCourseId === $course->id ? 'selected' : '' }}>
                                                {{ $course->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="filter-group">
                                <label for="student-assignment-status">{{ __('student.status_label') }}</label>
                                <div class="filter-select">
                                    <select id="student-assignment-status" name="status" class="nice-select">
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
                                <label for="student-assignment-search">{{ __('student.search_label') }}</label>
                                <div class="filter-search">
                                    <input type="search" id="student-assignment-search" name="search" class="search-input"
                                        value="{{ $searchTerm }}" placeholder="{{ __('student.search_assignment_title') }}">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-search"></i>
                                    <span>{{ __('student.apply') }}</span>
                                </button>
                                @if ($selectedCourseId || $statusFilter || $searchTerm)
                                    <a href="{{ route('student.assignments') }}" class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <span>{{ __('student.reset_label') }}</span>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="assignments-grid">
                        @forelse($assignments as $assignment)
                            <div class="assignment-card wow fadeInUp" data-wow-delay=".05s">
                                <div class="assignment-card-header">
                                    <div class="assignment-icon">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <div>
                                        <h3>{{ $assignment->title }}</h3>
                                        <p>
                                            <i class="fa-solid fa-graduation-cap"></i>
                                            {{ optional($assignment->topic->course)->title ?? __('student.unknown_course') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="assignment-card-body">
                                    <p class="assignment-description">{{ $assignment->short_description }}</p>

                                    <div class="assignment-stats">
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('student.status_label') }}</span>
                                            <span class="stat-value">
                                                <span
                                                    class="status-badge-table {{ $assignment->student_submission['status_class'] }}">
                                                    {{ $assignment->student_submission['status_label'] }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('student.grade_label') }}</span>
                                            <span class="stat-value">
                                                {{ $assignment->student_submission['grade'] !== null ? $assignment->student_submission['grade'] . '%' : '—' }}
                                            </span>
                                        </div>
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('student.submitted_label') }}</span>
                                            <span
                                                class="stat-value">{{ $assignment->student_submission['submitted_at'] ?? '—' }}</span>
                                        </div>
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('student.files_label') }}</span>
                                            <span class="stat-value">{{ $assignment->files_count }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="assignment-card-footer">
                                    <a href="{{ route('student.courses.access', optional($assignment->topic)->course_id) }}"
                                        class="assignment-action-btn">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        {{ __('student.open_course') }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="dashboard-empty-state">
                                <i class="fa-solid fa-clipboard-question"></i>
                                <h3>{{ __('student.no_assignments_display') }}</h3>
                                <p>{{ __('student.no_assignments_desc') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($assignments->hasPages())
                        <div class="table-pagination">
                            <div class="pagination-info">
                                Showing {{ $assignments->firstItem() }} to {{ $assignments->lastItem() }} of
                                {{ $assignments->total() }} assignments
                            </div>
                            <div class="pagination-links">
                                @if ($assignments->onFirstPage())
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('student.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $assignments->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('student.previous') }}
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @foreach ($assignments->getUrlRange(max(1, $assignments->currentPage() - 2), min($assignments->lastPage(), $assignments->currentPage() + 2)) as $page => $url)
                                        @if ($page === $assignments->currentPage())
                                            <span class="pagination-number active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}"
                                                class="pagination-number">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                </div>

                                @if ($assignments->hasMorePages())
                                    <a href="{{ $assignments->nextPageUrl() }}" class="pagination-btn">
                                        {{ __('student.next') }}
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        {{ __('student.next') }}
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
@endsection
