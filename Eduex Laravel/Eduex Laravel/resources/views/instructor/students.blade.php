@extends('layouts.instructor')

@push('styles')
<style>
    .students-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        gap: 20px;
        flex-wrap: wrap;
    }

    .students-header-actions {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-select {
        position: relative;
        min-width: 180px;
    }

    .filter-select select {
        width: 100%;
        padding: 12px 40px 12px 16px;
        border-radius: 10px;
        border: 1px solid rgba(108, 112, 111, 0.2);
        font-size: 14px;
        font-weight: 500;
        color: var(--header);
        background: var(--white);
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        transition: all 0.3s ease;
    }

    .filter-select select:hover {
        border-color: var(--theme);
    }

    .filter-select i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        color: rgba(108, 112, 111, 0.5);
        pointer-events: none;
    }

    .table-search {
        position: relative;
        min-width: 280px;
    }

    .table-search .search-input {
        width: 100%;
        padding: 12px 45px 12px 18px;
        border-radius: 10px;
        border: 1px solid rgba(108, 112, 111, 0.2);
        font-size: 14px;
        background: var(--white);
        transition: all 0.3s ease;
    }

    .table-search .search-input:focus {
        border-color: var(--theme);
        box-shadow: 0 0 0 4px rgba(0, 79, 68, 0.05);
        outline: none;
    }

    .table-search i {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(108, 112, 111, 0.5);
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .students-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .students-header-actions {
            width: 100%;
        }
        .filter-select, .table-search {
            flex: 1;
            min-width: 100%;
        }
    }
</style>
@endpush

@section('content')
    <!-- Instructor Students Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6>{{ __('instructor.students') }}</h6>
                            <h2>{{ __('instructor.my_students') }}</h2>
                            <p>{{ __('instructor.all_students_subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="dashboard-stats-minimal dashboard-gap-lg">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ number_format($stats['total_students']) }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.total_students_stat') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ number_format($stats['active_students']) }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.active_students_stat') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['total_enrollments'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.total_enrollments') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ number_format($stats['avg_progress']) }}%</div>
                                <div class="stat-label-minimal">{{ __('instructor.avg_course_progress') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Students Section with Gradient Background -->
                    <div class="dashboard-courses-section">
                        <div class="students-header">
                            <h3 class="wow fadeInUp">{{ __('instructor.all_students_count', ['total' => $pagination->total]) }}</h3>
                            <div class="students-header-actions wow fadeInUp" data-wow-delay=".2s">
                                <div class="filter-select">
                                    <select id="courseFilter">
                                        <option value="all">{{ __('instructor.all_courses') }}</option>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}" {{ $selectedCourse == $course->id ? 'selected' : '' }}>
                                                {{ $course->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                                <div class="filter-select">
                                    <select id="sortFilter">
                                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('instructor.newest_first') }}</option>
                                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>{{ __('instructor.oldest_first') }}</option>
                                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>{{ __('instructor.name_az') }}</option>
                                        <option value="progress_desc" {{ request('sort') == 'progress_desc' ? 'selected' : '' }}>{{ __('instructor.highest_progress') }}</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                                <div class="table-search">
                                    <input type="text" id="studentSearch" placeholder="{{ __('instructor.search_students_placeholder') }}" class="search-input">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('instructor.student_name') }}</th>
                                        <th>{{ __('instructor.courses_enrolled') }}</th>
                                        <th>{{ __('instructor.enrollment_date') }}</th>
                                        <th>{{ __('instructor.progress') }}</th>
                                        <th>{{ __('instructor.last_active') }}</th>
                                        <th>{{ __('instructor.action_col') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($students as $student)
                                        <tr data-status="{{ $student['status'] }}">
                                            <td>
                                                <div class="student-table-info">
                                                    <div class="student-avatar-small">
                                                        <img src="{{ $student['avatar'] }}" alt="{{ $student['name'] }}">
                                                    </div>
                                                    <div class="student-details">
                                                        <div class="student-name">{{ $student['name'] }}</div>
                                                        <div class="student-email-small">{{ $student['email'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="table-value">{{ $student['enrolled_courses'] }}</span>
                                            </td>
                                            <td>
                                                <span class="table-value">{{ $student['enrollment_date'] }}</span>
                                            </td>
                                            <td>
                                                <span class="table-value">{{ $student['progress'] }}%</span>
                                            </td>
                                            <td>
                                                <span class="table-value">{{ $student['last_active'] }}</span>
                                            </td>
                                            <td>
                                                <div class="student-actions">
                                                    <a href="{{ route('instructor.messages', ['student_id' => $student['id']]) }}"
                                                        class="action-btn message-btn" title="{{ __('instructor.message_student') }}">
                                                        <i class="fa-solid fa-comment-dots"></i>
                                                    </a>
                                                    <a href="{{ route('instructor.students.show', $student['id']) }}"
                                                        class="action-btn view-btn" title="{{ __('instructor.view_profile') }}">
                                                        <i class="fa-solid fa-user"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">
                                                <div class="dashboard-empty-state">
                                                    <i class="fa-solid fa-users"></i>
                                                    <h3>{{ __('instructor.no_students_yet') }}</h3>
                                                    <p>{{ __('instructor.no_students_enrolled_desc') }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($pagination->lastPage > 1)
                            <div class="table-pagination">
                                <div class="pagination-info">
                                    {{ __('instructor.showing_info', [
                                        'from' => $pagination->from,
                                        'to' => $pagination->to,
                                        'total' => $pagination->total
                                    ]) }}
                                </div>
                                <div class="pagination-links">
                                    @if ($pagination->currentPage > 1)
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $pagination->currentPage - 1]) }}"
                                            class="pagination-btn">
                                            <i class="fa-solid fa-chevron-left"></i> {{ __('instructor.prev_btn') }}
                                        </a>
                                    @else
                                        <span class="pagination-btn disabled">
                                            <i class="fa-solid fa-chevron-left"></i> {{ __('instructor.prev_btn') }}
                                        </span>
                                    @endif

                                    <div class="pagination-numbers">
                                        @for ($i = max(1, $pagination->currentPage - 2); $i <= min($pagination->lastPage, $pagination->currentPage + 2); $i++)
                                            @if ($i == $pagination->currentPage)
                                                <span class="pagination-number active">{{ $i }}</span>
                                            @else
                                                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                                    class="pagination-number">{{ $i }}</a>
                                            @endif
                                        @endfor
                                    </div>

                                    @if ($pagination->currentPage < $pagination->lastPage)
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $pagination->currentPage + 1]) }}"
                                            class="pagination-btn">
                                            {{ __('instructor.next_btn') }} <i class="fa-solid fa-chevron-right"></i>
                                        </a>
                                    @else
                                        <span class="pagination-btn disabled">
                                            {{ __('instructor.next_btn') }} <i class="fa-solid fa-chevron-right"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <!-- Students Section End -->
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>
@push('scripts')
<script>
    $(document).ready(function() {
        const courseFilter = $('#courseFilter');
        const sortFilter = $('#sortFilter');
        const studentSearch = $('#studentSearch');
        let searchTimeout;

        function applyFilters() {
            const course = courseFilter.val();
            const sort = sortFilter.val();
            const search = studentSearch.val();
            
            let url = new URL(window.location.href);
            url.searchParams.set('course', course);
            url.searchParams.set('sort', sort);
            url.searchParams.set('search', search);
            url.searchParams.set('page', 1); // Reset to first page on filter change
            
            window.location.href = url.toString();
        }

        courseFilter.on('change', applyFilters);
        sortFilter.on('change', applyFilters);
        
        studentSearch.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 1000); // Debounce search
        });

        // Set initial values from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('search')) {
            studentSearch.val(urlParams.get('search'));
            // Move cursor to end
            const val = studentSearch.val();
            studentSearch.val('').val(val).focus();
        }
    });
</script>
@endpush
@endsection
