@extends('layouts.student')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('student.my_quiz_attempts') }}</h6>
                            <h2>{{ __('student.my_quiz_attempts') }}</h2>
                            <p>{{ __('student.no_quiz_attempts_desc') }}</p>
                        </div>
                    </div>

                    <div class="dashboard-stats-minimal" style="margin-bottom: 30px;">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-circle-question"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['total_attempts'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.total_attempts') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-badge-check"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['passed_attempts'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.passed_label') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['failed_attempts'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.failed_label') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-percent"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">
                                    {{ $summary['average_score'] !== null ? $summary['average_score'] . '%' : '—' }}</div>
                                <div class="stat-label-minimal">{{ __('student.avg_score') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="submissions-filter-card wow fadeInUp" data-wow-delay=".1s">
                        <form method="GET" action="{{ route('student.quiz-attempts') }}" class="submissions-filter-form">

                            <div class="filter-group">
                                <label for="student-quiz-course">{{ __('student.course_label') }}</label>
                                <div class="filter-select">
                                    <select id="student-quiz-course" name="course_id" class="nice-select">
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
                                <label for="student-quiz-status">{{ __('student.result') }}</label>
                                <div class="filter-select">
                                    <select id="student-quiz-status" name="status" class="nice-select">
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
                                <label for="student-quiz-search">{{ __('student.search_label') }}</label>
                                <div class="filter-search">
                                    <input type="search" id="student-quiz-search" name="search" class="search-input"
                                        value="{{ $searchTerm }}" placeholder="{{ __('student.search_quiz_title') }}">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-search"></i>
                                    {{ __('student.apply_filters') }}
                                </button>
                                @if ($selectedCourseId || $statusFilter || $searchTerm)
                                    <a href="{{ route('student.quiz-attempts') }}" class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        {{ __('student.reset_label') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="submissions-table-wrapper wow fadeInUp" data-wow-delay=".15s">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>{{ __('student.quiz_label') }}</th>
                                    <th>{{ __('student.course_label') }}</th>
                                    <th>{{ __('student.score_label') }}</th>
                                    <th>{{ __('student.result') }}</th>
                                    <th>{{ __('student.time_taken') }}</th>
                                    <th>{{ __('student.completed_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attempts as $attempt)
                                    <tr>
                                        <td>
                                            <span class="table-value">{{ $attempt->quiz_title }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $attempt->course_title }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $attempt->score }}%</span>
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge-table {{ $attempt->status_class }}">{{ $attempt->status_label }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $attempt->time_taken_formatted ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $attempt->completed_at_formatted ?? '—' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="dashboard-empty-state">
                                                <i class="fa-solid fa-clipboard-question"></i>
                                                <h3>{{ __('student.no_quiz_attempts') }}</h3>
                                                <p>{{ __('student.no_quiz_attempts_desc') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($attempts->hasPages())
                        <div class="table-pagination">
                            <div class="pagination-info">
                                Showing {{ $attempts->firstItem() }} to {{ $attempts->lastItem() }} of
                                {{ $attempts->total() }} attempts
                            </div>
                            <div class="pagination-links">
                                @if ($attempts->onFirstPage())
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('student.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $attempts->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('student.previous') }}
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @foreach ($attempts->getUrlRange(max(1, $attempts->currentPage() - 2), min($attempts->lastPage(), $attempts->currentPage() + 2)) as $page => $url)
                                        @if ($page === $attempts->currentPage())
                                            <span class="pagination-number active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}"
                                                class="pagination-number">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                </div>

                                @if ($attempts->hasMorePages())
                                    <a href="{{ $attempts->nextPageUrl() }}" class="pagination-btn">
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
