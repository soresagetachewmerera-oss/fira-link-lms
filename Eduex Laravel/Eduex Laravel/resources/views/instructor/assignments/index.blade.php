@extends('layouts.instructor')

@section('content')
    <!-- Instructor Assignments Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('instructor.assignments') }}</h6>
                            <h2>{{ __('instructor.your_course_assignments') }}</h2>
                            <p>{{ __('instructor.assignments_subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="dashboard-stats-minimal" style="margin-bottom: 40px;">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['total_assignments'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.assignments') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-inbox"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['total_submissions'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.total_submissions') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['pending_submissions'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.pending_review') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['graded_submissions'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.graded') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="assignments-filter-card wow fadeInUp" data-wow-delay=".1s">
                        <form method="GET" action="{{ route('instructor.assignments.index') }}"
                            class="assignments-filter-form">
                            <div class="filter-group">
                                <label for="course-filter">{{ __('instructor.course') }}</label>
                                <div class="filter-select">
                                    <select id="course-filter" name="course_id" class="nice-select">
                                        <option value="">{{ __('instructor.select') }}</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}"
                                                {{ $selectedCourseId === $course->id ? 'selected' : '' }}>
                                                {{ $course->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-filter"></i>
                                    <span>{{ __('instructor.apply') }}</span>
                                </button>
                                @if ($selectedCourseId)
                                    <a href="{{ route('instructor.assignments.index') }}" class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <span>{{ __('instructor.reset') }}</span>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Assignments List -->
                    <div class="assignments-grid">
                        @forelse($assignments as $assignment)
                            <div class="assignment-card wow fadeInUp" data-wow-delay=".15s">
                                <div class="assignment-card-header">
                                    <div class="assignment-icon">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <div>
                                        <h3>{{ $assignment->title }}</h3>
                                        <p>
                                            <i class="fa-solid fa-graduation-cap"></i>
                                            {{ optional($assignment->topic->course)->title ?? __('instructor.unknown_course') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="assignment-card-body">
                                    <p class="assignment-description">
                                        {{ $assignment->short_description }}
                                    </p>

                                    <div class="assignment-stats">
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('instructor.total') }}</span>
                                            <span class="stat-value">{{ $assignment->submissions_count }}</span>
                                        </div>
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('instructor.status_pending') }}</span>
                                            <span class="stat-value">{{ $assignment->pending_submissions_count }}</span>
                                        </div>
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('instructor.graded') }}</span>
                                            <span class="stat-value">{{ $assignment->graded_submissions_count }}</span>
                                        </div>
                                        <div class="assignment-stat">
                                            <span class="stat-label">{{ __('instructor.returned') }}</span>
                                            <span class="stat-value">{{ $assignment->returned_submissions_count }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="assignment-card-footer">
                                    <a href="{{ route('instructor.assignments.show', $assignment->id) }}"
                                        class="assignment-action-btn">
                                        <i class="fa-solid fa-eye"></i>
                                        {{ __('instructor.view_submissions') }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="dashboard-empty-state">
                                <i class="fa-solid fa-clipboard-check"></i>
                                <h3>{{ __('instructor.no_assignments_found') }}</h3>
                                <p>{{ __('instructor.create_assignments_desc') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if ($assignments->hasPages())
                        <div class="table-pagination">
                            <div class="pagination-info">
                                {{ __('instructor.showing_assignments', ['from' => $assignments->firstItem(), 'to' => $assignments->lastItem(), 'total' => $assignments->total()]) }}
                            </div>
                            <div class="pagination-links">
                                @if ($assignments->onFirstPage())
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('instructor.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $assignments->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('instructor.previous') }}
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @foreach ($assignments->getUrlRange(max(1, $assignments->currentPage() - 2), min($assignments->lastPage(), $assignments->currentPage() + 2)) as $page => $url)
                                        @if ($page == $assignments->currentPage())
                                            <span class="pagination-number active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}"
                                                class="pagination-number">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                </div>

                                @if ($assignments->hasMorePages())
                                    <a href="{{ $assignments->nextPageUrl() }}" class="pagination-btn">
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
    <!-- Instructor Assignments Section End -->
@endsection
