@extends('layouts.admin')

@section('title', $pageTitle ?? 'All Enrollments')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        @if (isset($breadcrumbItems))
            @foreach ($breadcrumbItems as $item)
                @if ($item['route'])
                    <div class="breadcrumb-item {{ $loop->last ? '' : 'active' }}">
                        <a href="{{ route($item['route']) }}">{{ $item['name'] }}</a>
                    </div>
                @else
                    <div class="breadcrumb-item">{{ $item['name'] }}</div>
                @endif
            @endforeach
        @else
            <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Enrollments</div>
        @endif
    </div>
@endsection

@section('main-content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Enrollments</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalEnrollments }}
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
                        <h4>Pending</h4>
                    </div>
                    <div class="card-body">
                        {{ $pendingEnrollments }}
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
                        <h4>Completed</h4>
                    </div>
                    <div class="card-body">
                        {{ $completedEnrollments }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($totalRevenue) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filters and Search -->
                    <form method="GET" action="{{ route('admin.enrollments.index') }}" id="filterForm">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search enrollments..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="status"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                        Approved</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                        Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="course_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ Str::limit($course->title, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="instructor_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Instructors</option>
                                    @foreach ($instructors as $instructor)
                                        <option value="{{ $instructor->id }}"
                                            {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                            {{ $instructor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="payment_status"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Payments</option>
                                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid
                                    </option>
                                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>
                                        Unpaid</option>
                                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>
                                        Pending</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                @if (request()->anyFilled(['search', 'status', 'course_id', 'instructor_id', 'payment_status', 'date_from', 'date_to']))
                                    <a href="{{ route('admin.enrollments.index') }}" class="btn btn-secondary btn-block"
                                        title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_from" placeholder="From Date"
                                    value="{{ request('date_from') }}"
                                    onchange="document.getElementById('filterForm').submit()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_to" placeholder="To Date"
                                    value="{{ request('date_to') }}"
                                    onchange="document.getElementById('filterForm').submit()">
                            </div>
                            <div class="col-md-6 text-right">
                                <span class="badge badge-info">Total: {{ $enrollments->total() }} enrollments</span>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped" id="enrollments-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Payment</th>
                                    <th>Enrolled Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($enrollments as $enrollment)
                                    <tr>
                                        <td>#{{ $enrollment->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($enrollment->user->profile_photo)
                                                    <img src="{{ asset('storage/' . $enrollment->user->profile_photo) }}"
                                                        alt="{{ $enrollment->user->name }}"
                                                        class="mr-3 rounded-circle avatar-sm">
                                                @else
                                                    <div
                                                        class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center avatar-placeholder-sm">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $enrollment->user->name }}</h6>
                                                    <small class="text-muted">{{ $enrollment->user->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($enrollment->course && $enrollment->course->featured_image)
                                                    <img src="{{ asset('storage/' . $enrollment->course->featured_image) }}"
                                                        alt="{{ $enrollment->course->title }}"
                                                        class="mr-3 rounded course-thumb-xs">
                                                @else
                                                    <div
                                                        class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center course-thumb-xs-placeholder">
                                                        <i class="fas fa-book text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">
                                                        {{ Str::limit($enrollment->course->title ?? 'N/A', 30) }}</h6>
                                                    <small
                                                        class="text-muted">{{ $enrollment->course->instructor->name ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm status-select status-select-inline"
                                                data-enrollment-id="{{ $enrollment->id }}">
                                                <option value="pending"
                                                    {{ $enrollment->status == 'pending' ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="approved"
                                                    {{ $enrollment->status == 'approved' ? 'selected' : '' }}>Approved
                                                </option>
                                                <option value="completed"
                                                    {{ $enrollment->status == 'completed' ? 'selected' : '' }}>Completed
                                                </option>
                                                <option value="cancelled"
                                                    {{ $enrollment->status == 'cancelled' ? 'selected' : '' }}>Cancelled
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            @if (isset($enrollment->progress_percentage))
                                                <div class="progress progress-vertical-md">
                                                    <div class="progress-bar" role="progressbar"
                                                        style="width: {{ $enrollment->progress_percentage }}%;"
                                                        aria-valuenow="{{ $enrollment->progress_percentage }}"
                                                        aria-valuemin="0" aria-valuemax="100">
                                                        {{ $enrollment->progress_percentage }}%
                                                    </div>
                                                </div>
                                                <small
                                                    class="text-muted">{{ $enrollment->completed_lessons ?? 0 }}/{{ $enrollment->total_lessons ?? 0 }}
                                                    lessons</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($enrollment->payment)
                                                <strong>{{ currency_format($enrollment->payment->amount) }}</strong>
                                                <br><small
                                                    class="text-muted">{{ ucfirst($enrollment->payment->payment_method) }}</small>
                                                <br>
                                                @if ($enrollment->payment->status == 'completed')
                                                    <span class="badge badge-success">Paid</span>
                                                @elseif($enrollment->payment->status == 'pending')
                                                    <span class="badge badge-warning">Pending</span>
                                                @else
                                                    <span
                                                        class="badge badge-danger">{{ ucfirst($enrollment->payment->status) }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Free</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('M d, Y') : $enrollment->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.enrollments.show', $enrollment->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No enrollments found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $enrollments->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('change', '.status-select', function() {
                const enrollmentId = $(this).data('enrollment-id');
                const newStatus = $(this).val();
                const $select = $(this);
                const oldStatus = $select.find('option[selected]').val() || $select.val();

                Swal.fire({
                    title: 'Update Enrollment Status',
                    html: `Are you sure you want to change the status to <strong>${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ route('admin.enrollments.update-status', ':id') }}'
                                .replace(':id', enrollmentId)
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': 'status',
                            'value': newStatus
                        }));

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'PUT'
                        }));

                        $select.find('option:selected').data('old-value', oldStatus);

                        $('body').append(form);
                        form.submit();
                    } else {
                        $select.val(oldStatus);
                    }
                });
            });
        });
    </script>
@endpush
