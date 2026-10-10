@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Reports &amp; Analytics</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($financials['total_revenue']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Instructor Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($financials['total_instructor']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Platform Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($financials['total_platform']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>This Month's Revenue</h4>
                    </div>
                    <div class="card-body">
                        <div>{{ currency_format($financials['monthly_revenue']) }}</div>
                        <div class="text-muted small mt-1">
                            @php
                                $growth = $financials['revenue_growth'];
                            @endphp
                            @if ($growth === null)
                                <span>No previous data</span>
                            @elseif($growth >= 0)
                                <span class="text-success"><i class="fas fa-arrow-up"></i> {{ number_format($growth, 1) }}%
                                    vs last month</span>
                            @else
                                <span class="text-danger"><i class="fas fa-arrow-down"></i>
                                    {{ number_format(abs($growth), 1) }}% vs last month</span>
                            @endif
                        </div>
                        <div class="text-muted small mt-2">
                            <span class="d-block">Instructor share: <strong
                                    class="text-dark">{{ currency_format($financials['monthly_instructor']) }}</strong></span>
                            <span>Platform share: <strong
                                    class="text-dark">{{ currency_format($financials['monthly_platform']) }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Revenue &amp; Enrollment Trend</h4>
                    <span class="text-muted small">Last 6 months</span>
                </div>
                <div class="card-body card-body-tall">
                    @if (count($monthlyTrendChart['labels']))
                        <div class="chart-wrapper-lg">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    @else
                        <p class="text-muted mb-0">Not enough data to display the trend.</p>
                    @endif
                </div>
                @if ($monthlyTrend->isNotEmpty())
                    <div class="table-responsive border-top">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Revenue</th>
                                    <th>Enrollments</th>
                                    <th>Completions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthlyTrend as $trend)
                                    <tr>
                                        <td>{{ $trend['label'] }}</td>
                                        <td>{{ currency_format($trend['revenue']) }}</td>
                                        <td>{{ number_format($trend['enrollments']) }}</td>
                                        <td>{{ number_format($trend['completions']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Enrollment Snapshot</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Enrollments</span>
                            <strong>{{ number_format($enrollmentStats['total_enrollments']) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Completed Enrollments</span>
                            <strong>{{ number_format($enrollmentStats['completed_enrollments']) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>New This Month</span>
                            <strong>{{ number_format($enrollmentStats['new_enrollments_month']) }}</strong>
                        </li>
                        <li class="list-group-item">
                            <span class="d-block text-muted mb-2">Completion Rate</span>
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 mr-3">{{ number_format($enrollmentStats['completion_rate'], 1) }}%</h5>
                                <div class="progress grow progress-thin">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: {{ min(100, $enrollmentStats['completion_rate']) }}%"
                                        aria-valuenow="{{ $enrollmentStats['completion_rate'] }}" aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Student &amp; Instructor Stats</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Students</span>
                            <strong>{{ number_format($studentStats['total_students']) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>New This Month</span>
                            <strong>{{ number_format($studentStats['new_students_month']) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Instructors</span>
                            <strong>{{ number_format($studentStats['total_instructors']) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Approved Instructors</span>
                            <strong>{{ number_format($studentStats['approved_instructors']) }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Key Conversion Insights</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Revenue per Enrollment</span>
                            @php
                                $revenuePerEnrollment =
                                    $enrollmentStats['total_enrollments'] > 0
                                        ? $financials['total_revenue'] / $enrollmentStats['total_enrollments']
                                        : 0;
                            @endphp
                            <strong>{{ currency_format($revenuePerEnrollment) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Average Instructor Earning per Sale</span>
                            @php
                                $totalSales = array_sum($topCourseChart['sales']);
                                $avgInstructorSale =
                                    $totalSales > 0 ? $financials['total_instructor'] / $totalSales : 0;
                            @endphp
                            <strong>{{ currency_format($avgInstructorSale) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Instructor Approval Rate</span>
                            @php
                                $instructorApprovalRate =
                                    $studentStats['total_instructors'] > 0
                                        ? ($studentStats['approved_instructors'] / $studentStats['total_instructors']) *
                                            100
                                        : 0;
                            @endphp
                            <strong>{{ number_format($instructorApprovalRate, 1) }}%</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Completions per Enrollment</span>
                            <strong>{{ number_format($enrollmentStats['completion_rate'], 1) }}%</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Top Performing Courses</h4>
                    <span class="text-muted small">Revenue &amp; sales performance</span>
                </div>
                <div class="card-body">
                    @if (count($topCourseChart['labels']))
                        <div class="chart-wrapper-md">
                            <canvas id="topCoursesChart"></canvas>
                        </div>
                    @else
                        <p class="text-muted mb-0">No paid course data available yet.</p>
                    @endif

                    @if ($topCourses->isNotEmpty())
                        <div class="table-responsive mt-4">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Revenue</th>
                                        <th>Sales</th>
                                        <th>Completions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topCourses as $course)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.courses.show', $course['course_id']) }}">
                                                    {{ $course['course_title'] }}
                                                </a>
                                            </td>
                                            <td>{{ currency_format($course['total_revenue']) }}</td>
                                            <td>{{ number_format($course['total_sales']) }}</td>
                                            <td>{{ number_format($course['total_completions']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Top Instructors</h4>
                    <span class="text-muted small">Earnings share</span>
                </div>
                <div class="card-body">
                    @if (count($topInstructorChart['labels']))
                        <div class="chart-wrapper-md">
                            <canvas id="topInstructorsChart"></canvas>
                        </div>
                    @else
                        <p class="text-muted mb-0">No instructor earnings recorded yet.</p>
                    @endif

                    @if ($topInstructors->isNotEmpty())
                        <div class="mt-4">
                            <ul class="list-group list-group-flush">
                                @foreach ($topInstructors as $index => $instructor)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>#{{ $index + 1 }} {{ $instructor['instructor_name'] }}</strong>
                                            <div class="text-muted small">{{ number_format($instructor['total_sales']) }}
                                                sales</div>
                                        </div>
                                        <span
                                            class="badge badge-light">{{ currency_format($instructor['total_earning']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/front/js/chart.js') }}"></script>
    <script>
        (function() {
            const monthlyTrendData = @json($monthlyTrendChart);
            const topCoursesData = @json($topCourseChart);
            const topInstructorsData = @json($topInstructorChart);
            const currencyLabel = '{{ currency_symbol() }}';
            const currencySpacer = currencyLabel.length > 1 ? ' ' : '';

            const currencyFormatter = (value) => {
                const absolute = Math.abs(value || 0);
                const prefix = currencyLabel + currencySpacer;

                if (absolute >= 1000000) {
                    return prefix + (value / 1000000).toFixed(1) + 'M';
                }

                if (absolute >= 1000) {
                    return prefix + (value / 1000).toFixed(1) + 'k';
                }

                return prefix + Number(value || 0).toFixed(0);
            };

            const getCanvas = (id) => {
                const element = document.getElementById(id);

                if (!element) {
                    return null;
                }

                const existingChart = Chart.getChart(element);
                if (existingChart) {
                    existingChart.destroy();
                }

                return element;
            };

            const chartDefaults = {
                animation: {
                    duration: 300
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            };

            const revenueCanvas = getCanvas('monthlyTrendChart');
            if (revenueCanvas && monthlyTrendData.labels.length) {
                new Chart(revenueCanvas, {
                    type: 'line',
                    data: {
                        labels: monthlyTrendData.labels,
                        datasets: [{
                                label: 'Revenue',
                                data: monthlyTrendData.revenue,
                                borderColor: '#6777ef',
                                backgroundColor: 'rgba(103, 119, 239, 0.12)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Enrollments',
                                data: monthlyTrendData.enrollments,
                                borderColor: '#47c363',
                                backgroundColor: 'rgba(71, 195, 99, 0.12)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y1'
                            },
                            {
                                label: 'Completions',
                                data: monthlyTrendData.completions,
                                borderColor: '#ffa426',
                                backgroundColor: 'rgba(255, 164, 38, 0.12)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        ...chartDefaults,
                        scales: {
                            y: {
                                position: 'left',
                                ticks: {
                                    callback: (value) => currencyFormatter(value)
                                }
                            },
                            y1: {
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    callback: (value) => Number(value).toFixed(0)
                                }
                            }
                        }
                    }
                });
            }

            const topCoursesCanvas = getCanvas('topCoursesChart');
            if (topCoursesCanvas && topCoursesData.labels.length) {
                new Chart(topCoursesCanvas, {
                    type: 'bar',
                    data: {
                        labels: topCoursesData.labels,
                        datasets: [{
                                label: 'Revenue',
                                data: topCoursesData.revenue,
                                backgroundColor: '#3abaf4',
                                borderRadius: 6,
                                barThickness: 36,
                                order: 1
                            },
                            {
                                label: 'Sales',
                                data: topCoursesData.sales,
                                backgroundColor: '#63ed7a',
                                borderRadius: 6,
                                barThickness: 24,
                                yAxisID: 'y1',
                                order: 2
                            }
                        ]
                    },
                    options: {
                        ...chartDefaults,
                        scales: {
                            x: {
                                ticks: {
                                    callback: function(value, index) {
                                        const label = this.getLabelForValue(index) || '';
                                        return label.length > 15 ? label.slice(0, 12) + '…' : label;
                                    }
                                }
                            },
                            y: {
                                position: 'left',
                                ticks: {
                                    callback: (value) => currencyFormatter(value)
                                }
                            },
                            y1: {
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    callback: (value) => Number(value).toFixed(0)
                                }
                            }
                        }
                    }
                });
            }

            const topInstructorsCanvas = getCanvas('topInstructorsChart');
            if (topInstructorsCanvas && topInstructorsData.labels.length) {
                new Chart(topInstructorsCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: topInstructorsData.labels,
                        datasets: [{
                            data: topInstructorsData.earnings,
                            backgroundColor: ['#6777ef', '#fc544b', '#63ed7a', '#ffa426', '#3abaf4'],
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const dataset = context.dataset;
                                        const currentValue = dataset.data[context.dataIndex] || 0;
                                        const total = dataset.data.reduce((sum, value) => sum + Number(
                                            value || 0), 0);
                                        const percentage = total > 0 ? ((currentValue / total) * 100)
                                            .toFixed(1) : 0;
                                        return `${context.label}: ${currencyFormatter(currentValue)} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endpush
