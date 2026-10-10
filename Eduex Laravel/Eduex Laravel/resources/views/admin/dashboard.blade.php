@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('main-content')
    <div class="dashboard-v2">
        <!-- Top Stat Cards -->
        <div class="stats-grid-v2">
            <!-- Revenue Card -->
            <div class="stat-card-v2 wow fadeInUp" data-wow-delay="0.1s">
                <div class="stat-icon-v2 bg-primary">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info-v2">
                    <span class="label">Total Revenue</span>
                    <span class="value">{{ currency_format($analytics['overview']['total_revenue']) }}</span>
                    @if($analytics['overview']['revenue_change_pct'] !== null)
                        <span class="trend {{ $analytics['overview']['revenue_change_pct'] >= 0 ? 'up' : 'down' }}">
                            <i class="fas fa-arrow-{{ $analytics['overview']['revenue_change_pct'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($analytics['overview']['revenue_change_pct']) }}% vs last month
                        </span>
                    @endif
                </div>
            </div>

            <!-- Total Students Card -->
            <div class="stat-card-v2 wow fadeInUp" data-wow-delay="0.2s">
                <div class="stat-icon-v2 bg-secondary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info-v2">
                    <span class="label">Total Students</span>
                    <span class="value">{{ number_format($analytics['overview']['total_students']) }}</span>
                    @if($analytics['overview']['students_change_pct'] !== null)
                        <span class="trend {{ $analytics['overview']['students_change_pct'] >= 0 ? 'up' : 'down' }}">
                            <i
                                class="fas fa-arrow-{{ $analytics['overview']['students_change_pct'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($analytics['overview']['students_change_pct']) }}% vs last month
                        </span>
                    @endif
                </div>
            </div>

            <!-- Total Enrollments Card -->
            <div class="stat-card-v2 wow fadeInUp" data-wow-delay="0.3s">
                <div class="stat-icon-v2 bg-success">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="stat-info-v2">
                    <span class="label">Total Enrollments</span>
                    <span class="value">{{ number_format($analytics['overview']['total_enrollments']) }}</span>
                    <span class="trend up">
                        {{ number_format($analytics['overview']['this_month_enrollments']) }} this month
                    </span>
                </div>
            </div>

            <!-- Pending Offline Payments Card -->
            <div class="stat-card-v2 wow fadeInUp" data-wow-delay="0.4s">
                <div class="stat-icon-v2 bg-violet">
                    <i class="fa fa-building"></i>
                </div>
                <div class="stat-info-v2">
                    <span class="label">Offline Payments</span>
                    <span class="value">{{ number_format($analytics['overview']['pending_offline_payments']) }}</span>
                    <span class="trend">
                        Pending verification
                    </span>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <!-- Revenue Trend Card -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-card-v2 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="header">
                        <h3>Revenue Trend</h3>
                        <div class="period-filters">
                            <button class="period-btn active" data-period="thirty_days" data-chart="revenue">30
                                Days</button>
                            <button class="period-btn" data-period="this_year" data-chart="revenue">Yearly</button>
                            <button class="period-btn" data-period="last_12_months" data-chart="revenue">12 Months</button>
                        </div>
                    </div>
                    <div class="chart-container" style="position: relative; height:300px; width:100%">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card-v2 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="header">
                        <h3>Enrollment Overview</h3>
                        <div class="period-filters">
                            <select class="period-select" data-chart="enrollment">
                                <option value="thirty_days">30 Days</option>
                                <option value="this_year">Yearly</option>
                                <option value="last_12_months" selected>12 Months</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container" style="position: relative; height:300px; width:100%">
                        <canvas id="enrollmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats Row -->
        <div class="secondary-stats-row-v2 wow fadeInUp mt-4" data-wow-delay="0.7s">
            <div class="stat-card-minimal">
                <div class="stat-icon-minimal stat-icon-1">
                    <i class="fa fa-check"></i>
                </div>
                <div class="stat-content-minimal">
                    <div class="stat-value-minimal">{{ $analytics['overview']['completion_rate'] }}%</div>
                    <div class="stat-label-minimal">Completion Rate</div>
                </div>
            </div>
            <div class="stat-card-minimal">
                <div class="stat-icon-minimal stat-icon-3">
                    <i class="fa fa-laptop-code"></i>
                </div>
                <div class="stat-content-minimal">
                    <div class="stat-value-minimal">{{ $analytics['overview']['active_courses'] }} /
                        {{ $analytics['overview']['total_courses'] }}
                    </div>
                    <div class="stat-label-minimal">Active Courses</div>
                </div>
            </div>
            <div class="stat-card-minimal">
                <div class="stat-icon-minimal" style="background: var(--v2-violet);">
                    <i class="fa fa-star"></i>
                </div>
                <div class="stat-content-minimal">
                    <div class="stat-value-minimal">{{ round($analytics['overview']['average_rating'], 1) }} / 5.0</div>
                    <div class="stat-label-minimal">Average Rating</div>
                </div>
            </div>
            <div class="stat-card-minimal">
                <div class="stat-icon-minimal stat-icon-2" style="background: #ffa729;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content-minimal">
                    <div class="stat-value-minimal">{{ $analytics['overview']['refund_rate'] }}%</div>
                    <div class="stat-label-minimal">Refund Rate</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Performing Courses -->
            <div class="col-lg-8">
                <div class="transactions-wrapper-v2 wow fadeInUp" data-wow-delay="0.8s" style="margin-bottom: 30px;">
                    <div class="section-title-area-v2">
                        <h3>Top Performing Courses</h3>
                        <p class="text-muted small">Your best-selling courses ranked by revenue and enrollments</p>
                    </div>
                    <div class="top-courses-list">
                        @forelse($analytics['top_courses'] as $index => $course)
                            <div class="top-course-item">
                                <div class="course-thumb-v2">
                                    <img src="{{ $course['thumbnail'] ?? asset('assets/front/img/course/placeholder.jpg') }}"
                                        alt="{{ $course['title'] }}">
                                </div>
                                <div class="course-details">
                                    <h4>{{ $course['title'] }}</h4>
                                    <div class="course-metrics">
                                        <div class="metric-item">
                                            <i class="fa-solid fa-users"></i>
                                            <span>{{ number_format($course['enrollments']) }} Students</span>
                                        </div>
                                        <div class="metric-item">
                                            <i class="fa-solid fa-circle-check"></i>
                                            <span>{{ $course['completion_rate'] }}% Comp. Rate</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="course-revenue-v2">
                                    <span class="rev-amount">{{ currency_format($course['revenue']) }}</span>
                                    <span class="rev-rating"><i class="fa fa-star text-warning"></i>
                                        {{ $course['rating'] }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-4">No course data available yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="transactions-wrapper-v2 wow fadeInUp" data-wow-delay="1.0s">
                    <div class="table-header-v2">
                        <h3>Recent Transactions</h3>
                        {{-- Filters removed as requested --}}
                    </div>
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Student</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($analytics['recent_transactions'] as $tx)
                                    <tr>
                                        <td class="text-muted">{{ $tx['date'] }}</td>
                                        <td>
                                            <div class="transaction-type-badge">
                                                <i class="fa-solid fa-cart-shopping"></i> Sale
                                            </div>
                                        </td>
                                        <td class="font-weight-600">{{ $tx['description'] }}</td>
                                        <td>{{ $tx['student'] }}</td>
                                        <td>
                                            <span class="transaction-amount positive">
                                                +{{ currency_format($tx['amount']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge-table {{ $tx['status'] }}">
                                                {{ strtoupper($tx['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No recent transactions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="view-all-btn-wrapper">
                        <a href="{{ route('admin.payments.index') }}" class="view-all-link">View All Transactions</a>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Engagement & Activity) -->
            <div class="col-lg-4">
                <!-- Student Engagement -->
                <div class="sidebar-card-v2 sidebar-section-v2 wow fadeInUp" data-wow-delay="0.9s">
                    <h3>Student Engagement</h3>
                    <p class="text-muted small mb-4">Visualizing active learning patterns across the platform.</p>

                    <div class="engagement-bar-v2">
                        <div class="label-group">
                            <span>Active Students</span>
                            <span class="font-weight-bold">{{ $analytics['student_engagement']['active_students'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success"
                                style="width: {{ $analytics['student_engagement']['active_percentage'] }}%"></div>
                        </div>
                    </div>

                    <div class="engagement-bar-v2">
                        <div class="label-group">
                            <span>Completing Lessons</span>
                            <span
                                class="font-weight-bold">{{ $analytics['student_engagement']['completing_students'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary"
                                style="width: {{ $analytics['student_engagement']['completing_percentage'] }}%"></div>
                        </div>
                    </div>

                    <div class="engagement-bar-v2">
                        <div class="label-group">
                            <span>At Risk</span>
                            <span class="font-weight-bold">{{ $analytics['student_engagement']['at_risk_students'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning"
                                style="width: {{ $analytics['student_engagement']['at_risk_percentage'] }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="sidebar-card-v2 sidebar-section-v2 wow fadeInUp" data-wow-delay="1.1s">
                    <h3 class="mb-4">Recent Activity</h3>
                    <div class="activity-list-v2">
                        @forelse($analytics['recent_activity'] as $activity)
                            <div class="activity-item-v2">
                                <div class="activity-icon-v2">
                                    <i class="fas {{ $activity['icon'] }}"></i>
                                </div>
                                <div class="activity-content-v2">
                                    <p>{{ $activity['message'] }}</p>
                                    <span>{{ $activity['time'] }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-4">No recent activity.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Event Bookings -->
                <div class="sidebar-card-v2 wow fadeInUp" data-wow-delay="1.2s">
                    <h3 class="mb-4">Recent Event Bookings</h3>
                    <div class="activity-list-v2">
                        @forelse($analytics['recent_event_bookings'] as $booking)
                            <div class="activity-item-v2">
                                <div class="activity-icon-v2" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <i class="fa fa-calendar-check"></i>
                                </div>
                                <div class="activity-content-v2">
                                    <p><strong>{{ $booking['user_name'] }}</strong> booked
                                        <strong>{{ $booking['seats'] }}</strong> seat(s) for "{{ $booking['event_title'] }}"
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span>{{ $booking['time'] }}</span>
                                        <span class="status-badge-table {{ $booking['status'] }}"
                                            style="padding: 2px 6px; font-size: 9px;">
                                            {{ strtoupper($booking['status']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-4">No recent bookings.</p>
                        @endforelse
                    </div>
                    @if(count($analytics['recent_event_bookings']) > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.events.index') }}" class="btn btn-block btn-outline-primary btn-sm">View
                                All Events</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/admin/modules/chart.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/wow.min.js') }}"></script>
        <script>
            $(document).ready(function () {
                new WOW().init();

                const analyticsData = {!! json_encode($analytics['charts']) !!};

                // Revenue Chart Init
                const revCtx = document.getElementById('revenueChart').getContext('2d');
                let revenueChart = new Chart(revCtx, {
                    type: 'line',
                    data: {
                        labels: analyticsData.thirty_days.labels,
                        datasets: [{
                            label: 'Revenue',
                            data: analyticsData.thirty_days.revenue,
                            borderColor: '#004F44',
                            backgroundColor: 'rgba(0, 79, 68, 0.05)',
                            borderWidth: 3,
                            pointBackgroundColor: '#004F44',
                            pointRadius: 4,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                ticks: { callback: function (value) { return '$' + value; } }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Enrollment Chart Init
                const enCtx = document.getElementById('enrollmentChart').getContext('2d');
                let enrollmentChart = new Chart(enCtx, {
                    type: 'bar',
                    data: {
                        labels: analyticsData.last_12_months.labels,
                        datasets: [{
                            label: 'Enrollments',
                            data: analyticsData.last_12_months.enrollments,
                            backgroundColor: '#FFA729',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)' }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Chart Filters Logic
                $('.period-btn').on('click', function () {
                    const btn = $(this);
                    const period = btn.data('period');
                    const chartType = btn.data('chart');

                    btn.siblings().removeClass('active');
                    btn.addClass('active');

                    if (chartType === 'revenue') {
                        revenueChart.data.labels = analyticsData[period].labels;
                        revenueChart.data.datasets[0].data = analyticsData[period].revenue;
                        revenueChart.update();
                    }
                });

                $('.period-select').on('change', function () {
                    const select = $(this);
                    const period = select.val();
                    const chartType = select.data('chart');

                    if (chartType === 'enrollment') {
                        enrollmentChart.data.labels = analyticsData[period].labels;
                        enrollmentChart.data.datasets[0].data = analyticsData[period].enrollments;
                        enrollmentChart.update();
                    }
                });
            });
        </script>
    @endpush
@endsection