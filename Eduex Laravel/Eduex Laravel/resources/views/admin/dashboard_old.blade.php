@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    </div>
@endsection

@section('main-content')

    <div class="row mt-4">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Recent Enrollments</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEnrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->user->name ?? 'Student' }}</td>
                                        <td>{{ $enrollment->course->title ?? 'Course' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $enrollment->status === 'completed' ? 'success' : ($enrollment->status === 'pending' ? 'warning' : 'primary') }} text-capitalize">
                                                {{ $enrollment->status }}
                                            </span>
                                        </td>
                                        <td>{{ $enrollment->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent enrollments.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Recent Payments</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Payer</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayments as $payment)
                                    <tr>
                                        <td>{{ optional(optional($payment->enrollment)->user)->name ?? 'User' }}</td>
                                        <td>{{ optional(optional($payment->enrollment)->course)->title ?? 'Course' }}</td>
                                        <td>{{ currency_format($payment->amount) }}</td>
                                        <td>{{ $payment->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent payments.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Latest Users</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentUsers as $recentUser)
                                    <tr>
                                        <td>{{ $recentUser->name }}</td>
                                        <td>{{ $recentUser->email }}</td>
                                        <td class="text-capitalize">{{ $recentUser->role }}</td>
                                        <td>{{ $recentUser->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent users.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">Today</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['total_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['pending_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Completed Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['completed_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Today's Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($todayStats['total_revenue']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>New Users</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['new_users'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>New Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['new_reviews'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Assignments Submitted</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['assignments_submitted'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Event Bookings</h4>
                    </div>
                    <div class="card-body">
                        {{ $todayStats['event_bookings'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">This Month</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['total_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['pending_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Completed Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['completed_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Monthly Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($monthStats['total_revenue']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>New Users</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['new_users'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>New Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['new_reviews'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Assignments Submitted</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['assignments_submitted'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Event Bookings</h4>
                    </div>
                    <div class="card-body">
                        {{ $monthStats['event_bookings'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">This Year</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['total_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['pending_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Completed Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['completed_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Yearly Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($yearStats['total_revenue']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>New Users</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['new_users'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>New Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['new_reviews'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Assignments Submitted</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['assignments_submitted'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Event Bookings</h4>
                    </div>
                    <div class="card-body">
                        {{ $yearStats['event_bookings'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">All Time</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['total_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['pending_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Completed Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['completed_enrollments'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($totalStats['total_revenue']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Users</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['new_users'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['new_reviews'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Assignment Submissions</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['assignments_submitted'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Event Bookings</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalStats['event_bookings'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">Platform Overview</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-book"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Courses</h4>
                    </div>
                    <div class="card-body">
                        {{ $additionalStats['total_courses'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Courses</h4>
                    </div>
                    <div class="card-body">
                        {{ $additionalStats['pending_courses'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Approved Instructors</h4>
                    </div>
                    <div class="card-body">
                        {{ $additionalStats['total_instructors'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Instructors</h4>
                    </div>
                    <div class="card-body">
                        {{ $additionalStats['pending_instructors'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Students</h4>
                    </div>
                    <div class="card-body">
                        {{ $additionalStats['total_students'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $additionalStats['total_reviews'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
