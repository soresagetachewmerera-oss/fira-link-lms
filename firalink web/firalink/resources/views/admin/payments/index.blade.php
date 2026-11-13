@extends('layouts.admin')

@section('title', $pageTitle ?? 'All Payments')
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
            <div class="breadcrumb-item">Payments</div>
        @endif
    </div>
@endsection

@section('main-content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Payments</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalPayments }}
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
                        {{ $pendingPayments }}
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
                        {{ $completedPayments }}
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

    <!-- Revenue Statistics -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Today Revenue</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($todayRevenue) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>This Month</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($thisMonthRevenue) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>This Year</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($thisYearRevenue) }}
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
                    <form method="GET" action="{{ route('admin.payments.index') }}" id="filterForm">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search payments..." value="{{ request('search') }}">
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
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed
                                    </option>
                                    <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>
                                        Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="payment_method"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Methods</option>
                                    <option value="razorpay"
                                        {{ request('payment_method') == 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                                    <option value="stripe" {{ request('payment_method') == 'stripe' ? 'selected' : '' }}>
                                        Stripe</option>
                                    <option value="paystack"
                                        {{ request('payment_method') == 'paystack' ? 'selected' : '' }}>Paystack</option>
                                    <option value="flutterwave"
                                        {{ request('payment_method') == 'flutterwave' ? 'selected' : '' }}>Flutterwave
                                    </option>
                                    <option value="paypal" {{ request('payment_method') == 'paypal' ? 'selected' : '' }}>
                                        PayPal</option>
                                    <option value="sslcommerz"
                                        {{ request('payment_method') == 'sslcommerz' ? 'selected' : '' }}>SSLCommerz
                                    </option>
                                    <option value="mollie" {{ request('payment_method') == 'mollie' ? 'selected' : '' }}>
                                        Mollie</option>
                                    <option value="chapa" {{ request('payment_method') == 'chapa' ? 'selected' : '' }}>
                                        Chapa (Ethiopia)</option>
                                    <option value="offline" {{ request('payment_method') == 'offline' ? 'selected' : '' }}>
                                        Offline</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="course_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ Str::limit($course->title, 25) }}
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
                            <div class="col-md-1">
                                @if (request()->anyFilled([
                                        'search',
                                        'status',
                                        'payment_method',
                                        'course_id',
                                        'instructor_id',
                                        'student_id',
                                        'date_from',
                                        'date_to',
                                    ]))
                                    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary btn-block"
                                        title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <select class="form-control" name="student_id"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Students</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}"
                                            {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                            {{ $student->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_from" placeholder="From Date"
                                    value="{{ request('date_from') }}"
                                    onchange="document.getElementById('filterForm').submit()">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_to" placeholder="To Date"
                                    value="{{ request('date_to') }}"
                                    onchange="document.getElementById('filterForm').submit()">
                            </div>
                            <div class="col-md-6 text-right">
                                <span class="badge badge-info">Total: {{ $payments->total() }} payments</span>
                                @if ($pendingPayments > 0)
                                    <a href="{{ route('admin.payments.pending') }}" class="badge badge-warning ml-1">
                                        Pending: {{ $pendingPayments }}
                                    </a>
                                @endif
                                @if (isset($offlinePendingCount) && $offlinePendingCount > 0)
                                    <a href="{{ route('admin.payments.offline-pending') }}"
                                        class="badge badge-danger ml-1">
                                        <i class="fas fa-exclamation-circle"></i> Offline Pending:
                                        {{ $offlinePendingCount }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped" id="payments-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>#{{ $payment->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($payment->enrollment && $payment->enrollment->user)
                                                    @if ($payment->enrollment->user->profile_photo)
                                                        <img src="{{ asset('storage/' . $payment->enrollment->user->profile_photo) }}"
                                                            alt="{{ $payment->enrollment->user->name }}"
                                                            class="mr-3 rounded-circle avatar-sm">
                                                    @else
                                                        <div
                                                            class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center avatar-placeholder-sm">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ $payment->enrollment->user->name }}</h6>
                                                        <small
                                                            class="text-muted">{{ $payment->enrollment->user->email }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($payment->enrollment && $payment->enrollment->course)
                                                <div class="d-flex align-items-center">
                                                    @if ($payment->enrollment->course->featured_image)
                                                        <img src="{{ asset('storage/' . $payment->enrollment->course->featured_image) }}"
                                                            alt="{{ $payment->enrollment->course->title }}"
                                                            class="mr-3 rounded course-thumb-sm">
                                                    @else
                                                        <div
                                                            class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center course-thumb-placeholder">
                                                            <i class="fas fa-book text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">
                                                            {{ Str::limit($payment->enrollment->course->title, 30) }}</h6>
                                                        <small
                                                            class="text-muted">{{ $payment->enrollment->course->instructor->name ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ currency_format($payment->amount) }}</strong>
                                            @if($payment->discount_amount > 0)
                                                <div style="font-size: 11px; margin-top: 3px;">
                                                    <span class="text-success" title="Discount Applied">
                                                        <i class="fas fa-tag"></i> 
                                                        -{{ currency_format($payment->discount_amount) }}
                                                    </span>
                                                    @if($payment->coupon)
                                                        <span class="badge badge-light px-1 py-0" style="font-size: 9px; font-weight: 500; border: 1px solid #e2e8f0;" title="Coupon Code">
                                                            {{ $payment->coupon->code }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-secondary">{{ ucfirst($payment->payment_method) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusClasses = [
                                                    'pending' => 'status-select--pending',
                                                    'completed' => 'status-select--completed',
                                                    'failed' => 'status-select--failed',
                                                    'refunded' => 'status-select--refunded',
                                                ];
                                                $currentStatusClass =
                                                    $statusClasses[$payment->status] ?? 'status-select--refunded';
                                            @endphp
                                            <select
                                                class="form-control form-control-sm status-select status-select-inline status-select-colored {{ $currentStatusClass }}"
                                                data-payment-id="{{ $payment->id }}">
                                                <option value="pending"
                                                    {{ $payment->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="completed"
                                                    {{ $payment->status == 'completed' ? 'selected' : '' }}>Completed
                                                </option>
                                                <option value="failed"
                                                    {{ $payment->status == 'failed' ? 'selected' : '' }}>Failed</option>
                                                <option value="refunded"
                                                    {{ $payment->status == 'refunded' ? 'selected' : '' }}>Refunded
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <small>{{ $payment->created_at->format('M d, Y h:i A') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.payments.show', $payment->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if ($payment->status == 'pending')
                                                    <button type="button"
                                                        class="btn btn-sm btn-success approve-payment-btn"
                                                        data-payment-id="{{ $payment->id }}"
                                                        data-amount="{{ currency_format($payment->amount) }}"
                                                        title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger reject-payment-btn"
                                                        data-payment-id="{{ $payment->id }}"
                                                        data-amount="{{ currency_format($payment->amount) }}"
                                                        title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No payments found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $payments->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            function updateStatusSelectColor($select) {
                const status = $select.val();
                const colors = {
                    'pending': '#ffc107',
                    'completed': '#28a745',
                    'failed': '#dc3545',
                    'refunded': '#6c757d'
                };
                $select.css('background-color', colors[status] || '#6c757d');
            }

            $('.status-select').each(function() {
                updateStatusSelectColor($(this));
            });

            $(document).on('change', '.status-select', function() {
                const paymentId = $(this).data('payment-id');
                const newStatus = $(this).val();
                const $select = $(this);
                const oldStatus = $select.find('option[selected]').val() || $select.val();

                updateStatusSelectColor($select);

                Swal.fire({
                    title: 'Update Payment Status',
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
                            'action': '{{ route('admin.payments.update-status', ':id') }}'
                                .replace(':id', paymentId)
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

                        $('body').append(form);
                        form.submit();
                    } else {
                        $select.val(oldStatus);
                    }
                });
            });

            $(document).on('click', '.approve-payment-btn', function() {
                const paymentId = $(this).data('payment-id');
                const amount = $(this).data('amount');

                Swal.fire({
                    title: 'Approve Payment',
                    html: `
                <p>Are you sure you want to approve this payment?</p>
                <p><strong>Amount: ${amount}</strong></p>
                <p>This will also approve the associated enrollment.</p>
            `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check"></i> Approve',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ route('admin.payments.approve', ':id') }}'
                                .replace(':id', paymentId)
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.reject-payment-btn', function() {
                const paymentId = $(this).data('payment-id');
                const amount = $(this).data('amount');

                Swal.fire({
                    title: 'Reject Payment',
                    html: `
                <p>Are you sure you want to reject this payment?</p>
                <p><strong>Amount: ${amount}</strong></p>
                <p>This will also cancel the associated enrollment.</p>
                <textarea id="reject-reason" class="swal2-textarea swal-textarea" placeholder="Enter rejection reason (optional)"></textarea>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times"></i> Reject',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        return {
                            reason: document.getElementById('reject-reason').value
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reason = result.value.reason;

                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ route('admin.payments.reject', ':id') }}'
                                .replace(':id', paymentId)
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        if (reason) {
                            form.append($('<input>', {
                                'type': 'hidden',
                                'name': 'reason',
                                'value': reason
                            }));
                        }

                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
