@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 25px;">
                        <div class="section-title">
                            <h6>{{ __('instructor.quizzes_label') }}</h6>
                            <h2>{{ $quiz->title }}</h2>
                            <p>{{ __('instructor.monitor_attempts_desc') }}</p>
                        </div>
                        <div class="section-actions">
                            <a href="{{ route('instructor.quizzes.index') }}" class="breadcrumb-back">
                                <i class="fa-solid fa-arrow-left-long"></i>
                                {{ __('instructor.back_to_quizzes') }}
                            </a>
                        </div>
                    </div>

                    <div class="assignment-overview-card wow fadeInUp" data-wow-delay=".05s">
                        <div class="overview-header">
                            <div class="overview-row">
                                <div>
                                    <span class="overview-label">{{ __('instructor.course') }}</span>
                                    <h4>{{ optional($quiz->topic->course)->title ?? __('instructor.unknown_course') }}</h4>
                                </div>
                                <div>
                                    <span class="overview-label">{{ __('instructor.passing_score') }}</span>
                                    <h4>{{ $quiz->passing_score }}%</h4>
                                </div>
                                <div>
                                    <span class="overview-label">{{ __('instructor.attempts') }}</span>
                                    <h4>{{ $stats['total_attempts'] }}</h4>
                                </div>
                            </div>
                            <div class="overview-row">
                                <div class="overview-stat graded">
                                    <span class="stat-label">{{ __('instructor.passed') }}</span>
                                    <span class="stat-value">{{ $stats['passed_attempts'] }}</span>
                                </div>
                                <div class="overview-stat returned">
                                    <span class="stat-label">{{ __('instructor.failed') }}</span>
                                    <span class="stat-value">{{ $stats['failed_attempts'] }}</span>
                                </div>
                                <div class="overview-stat pending">
                                    <span class="stat-label">{{ __('instructor.avg_score') }}</span>
                                    <span
                                        class="stat-value">{{ $stats['average_score'] !== null ? $stats['average_score'] . '%' : '—' }}</span>
                                </div>
                                <div class="overview-stat">
                                    <span class="stat-label">{{ __('instructor.best_score') }}</span>
                                    <span
                                        class="stat-value">{{ $stats['best_score'] !== null ? $stats['best_score'] . '%' : '—' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="overview-body">
                            <div class="overview-section">
                                <h5>{{ __('instructor.average_completion_time') }}</h5>
                                <p class="assignment-description" style="margin: 0;">
                                    {{ $stats['average_time'] ?? __('instructor.not_enough_data') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="submissions-filter-card wow fadeInUp" data-wow-delay=".1s">
                        <form method="GET" action="{{ route('instructor.quizzes.show', $quiz) }}"
                            class="submissions-filter-form">
                            <div class="filter-row d-flex justify-content-between gap-3">
                                <div class="filter-group">
                                    <label for="attempt-status-filter">{{ __('instructor.result') }}</label>
                                    <div class="filter-select">
                                        <select id="attempt-status-filter" name="status" class="nice-select">
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
                                    <label for="attempt-search">{{ __('instructor.search') }}</label>
                                    <div class="filter-search">
                                        <input type="search" id="attempt-search" name="search" class="search-input"
                                            value="{{ $searchTerm }}" placeholder="{{ __('instructor.search_student_placeholder') }}">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-search"></i>
                                    {{ __('instructor.apply_filters') }}
                                </button>
                                @if ($statusFilter || $searchTerm)
                                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        {{ __('instructor.reset') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="submissions-table-wrapper wow fadeInUp" data-wow-delay=".15s">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>{{ __('instructor.student_col') }}</th>
                                    <th>{{ __('instructor.score') }}</th>
                                    <th>{{ __('instructor.result') }}</th>
                                    <th>{{ __('instructor.time_taken') }}</th>
                                    <th>{{ __('instructor.started') }}</th>
                                    <th>{{ __('instructor.completed') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attempts as $attempt)
                                    <tr>
                                        <td>
                                            <div class="student-table-info">
                                                <div class="student-details">
                                                    <div class="student-name">
                                                        {{ $attempt->user?->name ?? __('instructor.unknown_student') }}</div>
                                                    <div class="student-email-small">{{ $attempt->user?->email }}</div>
                                                </div>
                                            </div>
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
                                            <span class="table-value">{{ $attempt->started_at_formatted ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $attempt->completed_at_formatted ?? '—' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="dashboard-empty-state">
                                                <i class="fa-solid fa-chart-line"></i>
                                                <h3>{{ __('instructor.no_attempts_yet') }}</h3>
                                                <p>{{ __('instructor.no_attempts_desc') }}</p>
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
                                {{ __('instructor.showing_attempts', ['from' => $attempts->firstItem(), 'to' => $attempts->lastItem(), 'total' => $attempts->total()]) }}
                            </div>
                            <div class="pagination-links">
                                @if ($attempts->onFirstPage())
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('instructor.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $attempts->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('instructor.previous') }}
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
@endsection
