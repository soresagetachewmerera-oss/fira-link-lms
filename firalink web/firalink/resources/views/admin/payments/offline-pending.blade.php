@extends('layouts.admin')

@section('title', 'Offline Pending Payment Review')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></div>
        <div class="breadcrumb-item">Offline Pending Payments</div>
    </div>
@endsection

@section('main-content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Reviews</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalOfflinePending }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Amount</h4>
                    </div>
                    <div class="card-body">
                        {{ currency_format($totalOfflinePendingAmount) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Offline Pending Payment Review</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to All Payments
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <form method="GET" action="{{ route('admin.payments.offline-pending') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search by payment ID, transaction ID, student name, email, or course title..."
                                        value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                @if (request()->has('search'))
                                    <a href="{{ route('admin.payments.offline-pending') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear Search
                                    </a>
                                @endif
                                <span class="badge badge-warning ml-2">Total: {{ $payments->total() }} payments</span>
                            </div>
                        </div>
                    </form>

                    @if ($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped" id="offline-payments-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Amount</th>
                                        <th>Transaction ID</th>
                                        <th>Receipt</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payments as $payment)
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
                                                            @if ($payment->enrollment->user->phone)
                                                                <br><small
                                                                    class="text-muted">{{ $payment->enrollment->user->phone }}</small>
                                                            @endif
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
                                                                class="mr-3 rounded course-thumb-xs">
                                                        @else
                                                            <div
                                                                class="mr-3 bg-secondary rounded d-flex align-items-center justify-content-center course-thumb-xs-placeholder">
                                                                <i class="fas fa-book text-white"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <h6 class="mb-0">
                                                                {{ Str::limit($payment->enrollment->course->title, 30) }}
                                                            </h6>
                                                            <small
                                                                class="text-muted">{{ $payment->enrollment->course->instructor->name ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong
                                                    class="text-success">{{ currency_format($payment->amount) }}</strong>
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
                                                @if ($payment->transaction_id)
                                                    <small>{{ Str::limit($payment->transaction_id, 20) }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($payment->receipt_file)
                                                    <button type="button" class="btn btn-sm btn-info view-receipt-btn"
                                                        data-receipt-url="{{ asset('storage/' . $payment->receipt_file) }}"
                                                        data-payment-id="{{ $payment->id }}">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <a href="{{ asset('storage/' . $payment->receipt_file) }}"
                                                        target="_blank" class="btn btn-sm btn-secondary" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @else
                                                    <span class="badge badge-warning">No Receipt</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $payment->created_at->format('M d, Y h:i A') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">Pending Review</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button"
                                                        class="btn btn-sm btn-success approve-offline-payment-btn"
                                                        data-payment-id="{{ $payment->id }}"
                                                        data-amount="{{ currency_format($payment->amount) }}"
                                                        data-student="{{ $payment->enrollment->user->name ?? 'N/A' }}"
                                                        data-course="{{ $payment->enrollment->course->title ?? 'N/A' }}"
                                                        title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger reject-offline-payment-btn"
                                                        data-payment-id="{{ $payment->id }}"
                                                        data-amount="{{ currency_format($payment->amount) }}"
                                                        data-student="{{ $payment->enrollment->user->name ?? 'N/A' }}"
                                                        data-course="{{ $payment->enrollment->course->title ?? 'N/A' }}"
                                                        title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <a href="{{ route('admin.payments.show', $payment->id) }}"
                                                        class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            {{ $payments->links('vendor.pagination.stisla-default') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>No Pending Offline Payments</h4>
                            <p class="text-muted">All offline payments have been reviewed.</p>
                            <a href="{{ route('admin.payments.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to All Payments
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Payment Receipt - Payment #<span
                            id="receipt-payment-id"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="receipt-image" src="" alt="Receipt" class="img-fluid receipt-image-lg">
                </div>
                <div class="modal-footer">
                    <a id="receipt-download-link" href="#" target="_blank" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download Receipt
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.view-receipt-btn', function() {
                const receiptUrl = $(this).data('receipt-url');
                const paymentId = $(this).data('payment-id');

                $('#receipt-payment-id').text(paymentId);
                $('#receipt-image').attr('src', receiptUrl);
                $('#receipt-download-link').attr('href', receiptUrl);
                $('#receiptModal').modal('show');
            });

            $(document).on('click', '.approve-offline-payment-btn', function() {
                const paymentId = $(this).data('payment-id');
                const amount = $(this).data('amount');
                const student = $(this).data('student');
                const course = $(this).data('course');

                Swal.fire({
                    title: 'Approve Offline Payment?',
                    html: `
                <div class="text-left">
                    <p><strong>Are you sure you want to approve this payment?</strong></p>
                    <hr>
                    <p><strong>Student:</strong> ${student}</p>
                    <p><strong>Course:</strong> ${course}</p>
                    <p><strong>Amount:</strong> ${amount}</p>
                    <hr>
                    <p class="text-success"><i class="fas fa-info-circle"></i> This will:</p>
                    <ul class="text-left text-success">
                        <li>Mark the payment as completed</li>
                        <li>Approve the enrollment</li>
                        <li>Activate the course access for the student</li>
                    </ul>
                </div>
            `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check"></i> Yes, Approve',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    reverseButtons: true
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

            $(document).on('click', '.reject-offline-payment-btn', function() {
                const paymentId = $(this).data('payment-id');
                const amount = $(this).data('amount');
                const student = $(this).data('student');
                const course = $(this).data('course');

                Swal.fire({
                    title: 'Reject Offline Payment?',
                    html: `
                <div class="text-left">
                    <p><strong>Are you sure you want to reject this payment?</strong></p>
                    <hr>
                    <p><strong>Student:</strong> ${student}</p>
                    <p><strong>Course:</strong> ${course}</p>
                    <p><strong>Amount:</strong> ${amount}</p>
                    <hr>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This will:</p>
                    <ul class="text-left text-danger">
                        <li>Mark the payment as failed</li>
                        <li>Cancel the enrollment</li>
                        <li>Revoke course access for the student</li>
                    </ul>
                    <hr>
                    <textarea id="reject-reason" class="swal2-textarea swal-textarea" placeholder="Enter rejection reason (required)" required></textarea>
                </div>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times"></i> Yes, Reject',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    preConfirm: () => {
                        const reason = document.getElementById('reject-reason').value;
                        if (!reason || reason.trim() === '') {
                            Swal.showValidationMessage('Please provide a rejection reason');
                            return false;
                        }
                        return {
                            reason: reason
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

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': 'reason',
                            'value': reason
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
