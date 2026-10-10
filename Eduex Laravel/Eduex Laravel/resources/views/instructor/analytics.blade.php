@extends('layouts.instructor')

@section('content')
    <!-- Instructor Analytics Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6>{{ __('instructor.analytics') }}</h6>
                            <h2>{{ __('instructor.performance_dashboard') }}</h2>
                            <p>{{ __('instructor.analytics_subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Overview Stats -->
                    <div class="dashboard-stats-minimal dashboard-gap-lg">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-money-bill"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">
                                    {{ currency_format($analytics['overview']['total_revenue']) }}
                                </div>
                                <div class="stat-label-minimal">{{ __('instructor.total_revenue') }}</div>
                                @if (!is_null($analytics['overview']['revenue_change_pct']))
                                    @php $revPositive = $analytics['overview']['revenue_change_pct'] >= 0; @endphp
                                    <div class="stat-change {{ $revPositive ? 'positive' : 'negative' }}">
                                        {{ $revPositive ? '+' : '' }}{{ $analytics['overview']['revenue_change_pct'] }}% {{ __('instructor.vs_last_month') }}
                                    </div>
                                @else
                                    <div class="stat-change">{{ __('instructor.no_prior_month_data') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">
                                    {{ number_format($analytics['overview']['total_students']) }}
                                </div>
                                <div class="stat-label-minimal">{{ __('instructor.total_students') }}</div>
                                <div
                                    class="stat-change {{ ($analytics['overview']['students_change_pct'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                                    +{{ number_format($analytics['overview']['new_students_this_month']) }} {{ __('instructor.this_month') }}
                                    @if (!is_null($analytics['overview']['students_change_pct']))
                                        ({{ $analytics['overview']['students_change_pct'] >= 0 ? '+' : '' }}{{ $analytics['overview']['students_change_pct'] }}%)
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">
                                    {{ number_format($analytics['overview']['total_enrollments']) }}
                                </div>
                                <div class="stat-label-minimal">{{ __('instructor.total_enrollments') }}</div>
                                <div class="stat-change positive">
                                    +{{ number_format($analytics['overview']['monthly_enrollments']) }} {{ __('instructor.this_month') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">
                                    {{ number_format($analytics['overview']['average_rating'], 1) }}
                                </div>
                                <div class="stat-label-minimal">{{ __('instructor.average_rating') }}</div>
                                <div class="stat-change">{{ number_format($analytics['overview']['total_reviews']) }}
                                    {{ __('instructor.reviews') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue & Enrollments Charts -->
                    <div class="row analytics-row-gap">
                        <div class="col-lg-6 col-md-6">
                            <div class="analytics-chart-card wow fadeInUp">
                                <div class="chart-header">
                                    <h4>{{ __('instructor.revenue_overview') }}</h4>
                                    <span class="chart-period">{{ __('instructor.last_12_months') }}</span>
                                </div>
                                <div class="chart-container">
                                    <canvas id="revenueChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <div class="analytics-chart-card wow fadeInUp" data-wow-delay=".2s">
                                <div class="chart-header">
                                    <h4>{{ __('instructor.enrollments_trend') }}</h4>
                                    <span class="chart-period">{{ __('instructor.last_12_months') }}</span>
                                </div>
                                <div class="chart-container">
                                    <canvas id="enrollmentsChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Stats Row -->
                    <div class="dashboard-stats-minimal dashboard-gap-lg">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-percent"></i>
                            </div>
                             <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $analytics['overview']['completion_rate'] }}%</div>
                                <div class="stat-label-minimal">{{ __('instructor.completion_rate') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content-minimal">
                                 <div class="stat-value-minimal">
                                    {{ $analytics['overview']['active_courses'] }}/{{ $analytics['overview']['total_courses'] }}
                                </div>
                                <div class="stat-label-minimal">{{ __('instructor.active_courses') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-undo"></i>
                            </div>
                             <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $analytics['overview']['refund_rate'] }}%</div>
                                <div class="stat-label-minimal">{{ __('instructor.refund_rate') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="stat-content-minimal">
                                 <div class="stat-value-minimal">
                                    {{ currency_format($analytics['overview']['monthly_revenue']) }}
                                </div>
                                <div class="stat-label-minimal">{{ __('instructor.monthly_revenue') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Courses & Student Engagement -->
                    <div class="row analytics-row-gap">
                        <div class="col-lg-8">
                            <div class="dashboard-courses-section">
                                 <div class="section-title-area">
                                    <div class="section-title">
                                        <h3>{{ __('instructor.top_performing_courses') }}</h3>
                                        <p>{{ __('instructor.top_courses_subtitle') }}</p>
                                    </div>
                                </div>

                                <div class="top-courses-list">
                                    @forelse($analytics['top_courses'] as $index => $course)
                                        <div class="top-course-item wow fadeInUp" data-wow-delay="{{ ($index % 3) * 0.1 }}s">
                                            <div class="course-rank">
                                                <span class="rank-number">#{{ $index + 1 }}</span>
                                            </div>
                                            <div class="course-details">
                                                <h4>{{ $course['title'] }}</h4>
                                                <div class="course-metrics">
                                                     <div class="metric-item">
                                                        <i class="fa-solid fa-users"></i>
                                                        <span>{{ __('instructor.enrollments_count', ['count' => number_format($course['enrollments'])]) }}</span>
                                                    </div>
                                                    <div class="metric-item">
                                                        <i class="fa-solid fa-dollar-sign"></i>
                                                        <span>{{ __('instructor.revenue_count', ['amount' => currency_format($course['revenue'])]) }}</span>
                                                    </div>
                                                    <div class="metric-item">
                                                        <i class="fa-solid fa-star"></i>
                                                        <span>{{ __('instructor.rating_count', ['rating' => number_format($course['rating'], 1)]) }}</span>
                                                    </div>
                                                    <div class="metric-item">
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        <span>{{ __('instructor.completion_count', ['percentage' => $course['completion_rate']]) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="course-progress-bar">
                                                <div class="progress-bar-small">
                                                    <div class="progress-fill-small"
                                                        style="width: {{ $course['completion_rate'] }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                         <div class="empty-state text-center py-5">
                                            <div class="empty-state-icon">
                                                <i class="fa-solid fa-book-open"></i>
                                            </div>
                                            <h5>{{ __('instructor.no_course_data') }}</h5>
                                            <p class="text-muted mb-0">{{ __('instructor.no_course_data_desc') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                             <div class="analytics-card analytics-card-auto h-auto">
                                <div class="analytics-card-header">
                                    <h4>{{ __('instructor.student_engagement') }}</h4>
                                </div>
                                <div class="engagement-stats">
                                    <div class="engagement-item">
                                        <div class="engagement-icon active">
                                            <i class="fa-solid fa-user-check"></i>
                                        </div>
                                        <div class="engagement-info">
                                             <div class="engagement-value">
                                                {{ number_format($analytics['student_engagement']['active_students']) }}
                                            </div>
                                            <div class="engagement-label">{{ __('instructor.active_students_label') }}</div>
                                            <div class="engagement-percentage">
                                                {{ $analytics['student_engagement']['active_percentage'] }}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="engagement-item">
                                        <div class="engagement-icon completing">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </div>
                                        <div class="engagement-info">
                                             <div class="engagement-value">
                                                {{ number_format($analytics['student_engagement']['completing_students']) }}
                                            </div>
                                            <div class="engagement-label">{{ __('instructor.completing_courses') }}</div>
                                            <div class="engagement-percentage">
                                                {{ $analytics['student_engagement']['completing_percentage'] }}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="engagement-item">
                                        <div class="engagement-icon at-risk">
                                            <i class="fa-solid fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="engagement-info">
                                             <div class="engagement-value">
                                                {{ number_format($analytics['student_engagement']['at_risk_students']) }}
                                            </div>
                                            <div class="engagement-label">{{ __('instructor.at_risk_students') }}</div>
                                            <div class="engagement-percentage">
                                                {{ $analytics['student_engagement']['at_risk_percentage'] }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                             <div class="analytics-card analytics-card-spacing h-auto">
                                <div class="analytics-card-header">
                                    <h4>{{ __('instructor.recent_activity') }}</h4>
                                </div>
                                <div class="recent-activity-list">
                                    @forelse($analytics['recent_activity'] as $activity)
                                        <div class="activity-item">
                                            <div class="activity-icon {{ $activity['type'] }}">
                                                <i class="fa-solid {{ $activity['icon'] }}"></i>
                                            </div>
                                            <div class="activity-content">
                                                <p>{{ $activity['message'] }}</p>
                                                <span class="activity-time">{{ $activity['time'] }}</span>
                                            </div>
                                        </div>
                                    @empty
                                         <div class="empty-state text-center py-4">
                                            <div class="empty-state-icon">
                                                <i class="fa-solid fa-chart-line"></i>
                                            </div>
                                            <h6>{{ __('instructor.no_activity_yet') }}</h6>
                                            <p class="text-muted mb-0">{{ __('instructor.no_activity_desc') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="{{ asset('assets/front/js/chart.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx) {
                    new Chart(revenueCtx, {
                        type: 'line',
                        data: {
                            labels: @json($analytics['revenue']['labels']),
                            datasets: [{
                                label: 'Revenue ({{ $analytics['revenue']['currency'] }})',
                                data: @json($analytics['revenue']['data']),
                                borderColor: '#004F44',
                                backgroundColor: 'rgba(0, 79, 68, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#004F44',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (value) {
                                            return '{{ $analytics['revenue']['currency'] }} ' + Number(
                                                value).toLocaleString(undefined, {
                                                    minimumFractionDigits: 0,
                                                    maximumFractionDigits: 2
                                                });
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(108, 112, 111, 0.1)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }

                // Enrollments Chart
                const enrollmentsCtx = document.getElementById('enrollmentsChart');
                if (enrollmentsCtx) {
                    new Chart(enrollmentsCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($analytics['enrollments']['labels']),
                            datasets: [{
                                label: 'Enrollments',
                                data: @json($analytics['enrollments']['data']),
                                backgroundColor: 'rgba(0, 79, 68, 0.8)',
                                borderColor: '#004F44',
                                borderWidth: 2,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 50
                                    },
                                    grid: {
                                        color: 'rgba(108, 112, 111, 0.1)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection