@extends('layouts.instructor')

@section('content')
    <!-- Instructor Student Detail Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Back Button -->
                    <div class="student-detail-header wow fadeInUp" style="margin-bottom: 30px;">
                        <a href="{{ route('instructor.students') }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Back to Students</span>
                        </a>
                    </div>

                    <!-- Student Profile Header -->
                    <div class="student-profile-header wow fadeInUp">
                        <div class="student-profile-main">
                            <div class="student-profile-avatar">
                                <img src="{{ $student['avatar'] }}" alt="{{ $student['name'] }}">
                                <span class="mt-6 status-badge-large {{ $student['status'] }}">
                                    {{ ucfirst($student['status']) }}
                                </span>
                            </div>
                            <div class="student-profile-info">
                                <h2>{{ $student['name'] }}</h2>
                                <p class="student-email-header">{{ $student['email'] }}</p>
                                <div class="student-profile-meta">
                                    <div class="meta-item">
                                        <i class="fa-solid fa-phone"></i>
                                        <span>{{ $student['phone'] }}</span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="dashboard-stats-minimal" style="margin-bottom: 40px;">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['total_courses'] }}</div>
                                <div class="stat-label-minimal">Total Courses</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['completed'] }}</div>
                                <div class="stat-label-minimal">Completed</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['completion_rate'] }}%</div>
                                <div class="stat-label-minimal">Completion Rate</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['average_score'] }}%</div>
                                <div class="stat-label-minimal">Average Score</div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrolled Courses Section -->
                    <div class="dashboard-courses-section">
                        <div class="section-title-area">
                            <div class="section-title">
                                <h3>Enrolled Courses</h3>
                                <p>Courses that {{ explode(' ', $student['name'])[0] }} is currently taking or has
                                    completed</p>
                            </div>
                        </div>

                        <div class="student-courses-list">
                            @forelse($enrolledCourses as $index => $course)
                                <div class="student-course-card wow fadeInUp" data-wow-delay="{{ ($index % 3) * 0.1 }}s">
                                    <div class="course-image">
                                        <img src="{{ $course['image'] }}" alt="{{ $course['title'] }}">
                                        <div class="course-status-overlay">
                                            @if ($course['status'] === 'completed')
                                                <span class="status-badge-complete">
                                                    <i class="fa-solid fa-check"></i>
                                                    Completed
                                                </span>
                                            @elseif($course['status'] === 'in_progress')
                                                <span class="status-badge-progress">
                                                    <i class="fa-solid fa-spinner"></i>
                                                    In Progress
                                                </span>
                                            @else
                                                <span class="status-badge-not-started">
                                                    <i class="fa-solid fa-clock"></i>
                                                    Not Started
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="course-content">
                                        <h4>{{ $course['title'] }}</h4>
                                        <div class="course-progress-section">
                                            <div class="progress-header">
                                                <span class="progress-label">Progress</span>
                                                <span class="progress-percentage">{{ $course['progress'] }}%</span>
                                            </div>
                                            <div class="progress-bar-wrapper">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: {{ $course['progress'] }}%">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="course-stats">
                                                <div class="stat-item-small">
                                                    <i class="fa-solid fa-video"></i>
                                                    <span>{{ $course['lessons_completed'] }}/{{ $course['total_lessons'] }}
                                                        Lessons</span>
                                                </div>
                                                @if ($course['score'])
                                                    <div class="stat-item-small">
                                                        <i class="fa-solid fa-trophy"></i>
                                                        <span>Score: {{ $course['score'] }}%</span>
                                                    </div>
                                                @endif
                                                <div class="stat-item-small">
                                                    <i class="fa-solid fa-calendar"></i>
                                                    <span>Started
                                                        {{ date('M d, Y', strtotime($course['started_date'])) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="dashboard-empty-state">
                                    <i class="fa-solid fa-book-open"></i>
                                    <h3>No Enrolled Courses</h3>
                                    <p>This student hasn't enrolled in any courses yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- Enrolled Courses Section End -->
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>
@endsection
