@extends('layouts.admin')

@section('title', 'Payment Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></div>
        <div class="breadcrumb-item">Payment Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Payment Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-icon btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Payments
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Payment Header -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h3>Payment #{{ $payment->id }}</h3>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Amount:</strong> {{ currency_format($payment->amount) }}</p>
                                    @if($payment->discount_amount > 0)
                                        <p class="mb-2"><strong>Discount:</strong> <span class="text-success">-{{ currency_format($payment->discount_amount) }}</span></p>
                                        @if($payment->coupon)
                                            <p class="mb-2"><strong>Coupon Code:</strong> <span class="badge badge-light" style="border: 1px solid #cbd5e1; font-weight: 500;">{{ $payment->coupon->code }}</span></p>
                                        @endif
                                    @endif
                                    <p class="mb-2"><strong>Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Status:</strong>
                                        <span
                                            class="badge badge-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'pending' ? 'warning' : ($payment->status == 'failed' ? 'danger' : 'secondary')) }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </p>
                                    <p class="mb-2"><strong>Payment Date:</strong>
                                        {{ $payment->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div class="col-md-3">
                                    @if ($payment->enrollment && $payment->enrollment->user)
                                        <p class="mb-2"><strong>Student:</strong> {{ $payment->enrollment->user->name }}
                                        </p>
                                        <p class="mb-2"><strong>Email:</strong> {{ $payment->enrollment->user->email }}
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    @if ($payment->enrollment && $payment->enrollment->course)
                                        <p class="mb-2"><strong>Course:</strong>
                                            {{ $payment->enrollment->course->title }}</p>
                                        <p class="mb-2"><strong>Instructor:</strong>
                                            {{ $payment->enrollment->course->instructor->name ?? 'N/A' }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div
                                    class="card-icon bg-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'pending' ? 'warning' : ($payment->status == 'failed' ? 'danger' : 'secondary')) }}">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Status</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ ucfirst($payment->status) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Amount</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ currency_format($payment->amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-info">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Method</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ ucfirst($payment->payment_method) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-secondary">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Date</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $payment->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-dark">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Platform Commission</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ currency_format($payment->platform_commission ?? 0) }}
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
                                        <h4>Instructor Earning</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ currency_format($payment->instructor_earning ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-secondary">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Commission Status</h4>
                                    </div>
                                    <div class="card-body">
                                        {{ $payment->commission_processed ? 'Processed' : 'Pending' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    @if ($payment->status == 'pending')
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="btn-group" role="group">
                                    <form action="{{ route('admin.payments.approve', $payment->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Approve Payment
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger reject-payment-btn"
                                        data-payment-id="{{ $payment->id }}"
                                        data-amount="{{ currency_format($payment->amount) }}">
                                        <i class="fas fa-times"></i> Reject Payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Status Update Form -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <form action="{{ route('admin.payments.update-status', $payment->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label>Update Status</label>
                                    <select class="form-control" name="status" onchange="this.form.submit()">
                                        <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="completed" {{ $payment->status == 'completed' ? 'selected' : '' }}>
                                            Completed</option>
                                        <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>Failed
                                        </option>
                                        <option value="refunded" {{ $payment->status == 'refunded' ? 'selected' : '' }}>
                                            Refunded</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview"
                                role="tab">Overview</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="transaction-tab" data-toggle="tab" href="#transaction"
                                role="tab">Transaction Details</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="enrollment-tab" data-toggle="tab" href="#enrollment"
                                role="tab">Enrollment Details</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="paymentTabsContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="mt-4">
                                <h5>Payment Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Payment ID</th>
                                                <td>#{{ $payment->id }}</td>
                                            </tr>
                                            <tr>
                                                <th>Amount</th>
                                                <td><strong>{{ currency_format($payment->amount) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <th>Payment Method</th>
                                                <td>{{ ucfirst($payment->payment_method) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'pending' ? 'warning' : ($payment->status == 'failed' ? 'danger' : 'secondary')) }}">
                                                        {{ ucfirst($payment->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @if ($payment->transaction_id !== null)
                                                <tr>
                                                    <th>Transaction ID</th>
                                                    <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th>Payment Date</th>
                                                <td>{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Updated At</th>
                                                <td>{{ $payment->updated_at->format('M d, Y h:i A') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Platform Commission</th>
                                                <td>{{ currency_format($payment->platform_commission ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Instructor Earning</th>
                                                <td>{{ currency_format($payment->instructor_earning ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Commission Processed</th>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $payment->commission_processed ? 'success' : 'warning' }}">
                                                        {{ $payment->commission_processed ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        @if ($payment->receipt_file)
                                            <h6>Receipt</h6>
                                            <div class="mb-3">
                                                <img src="{{ asset('storage/' . $payment->receipt_file) }}"
                                                    alt="Receipt" class="img-fluid rounded receipt-image">
                                            </div>
                                            <a href="{{ asset('storage/' . $payment->receipt_file) }}" target="_blank"
                                                class="btn btn-primary">
                                                <i class="fas fa-download"></i> Download Receipt
                                            </a>
                                        @else
                                            <div class="alert alert-info">No receipt uploaded.</div>
                                        @endif

                                        @if ($payment->notes)
                                            <div class="mt-3">
                                                <h6>Notes</h6>
                                                <p class="text-muted">{{ $payment->notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Details Tab -->
                        <div class="tab-pane fade" id="transaction" role="tabpanel">
                            <div class="mt-4">
                                <h5>Transaction Details</h5>
                                <table class="table table-bordered">
                                    @if ($payment->transaction_id !== null)
                                        <tr>
                                            <th width="40%">Transaction ID</th>
                                            <td>{{ $payment->transaction_id }}</td>
                                        </tr>
                                    @endif
                                    @if ($payment->payment_method == 'razorpay')
                                        <tr>
                                            <th>Razorpay Order ID</th>
                                            <td>{{ $payment->razorpay_order_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Razorpay Payment ID</th>
                                            <td>{{ $payment->razorpay_payment_id ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'stripe')
                                        <tr>
                                            <th>Stripe Payment Intent ID</th>
                                            <td>{{ $payment->stripe_payment_intent_id ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'paystack')
                                        <tr>
                                            <th>Paystack Reference</th>
                                            <td>{{ $payment->paystack_reference ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Paystack Access Code</th>
                                            <td>{{ $payment->paystack_access_code ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'flutterwave')
                                        <tr>
                                            <th>Flutterwave Transaction ID</th>
                                            <td>{{ $payment->flutterwave_transaction_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Flutterwave TX Ref</th>
                                            <td>{{ $payment->flutterwave_tx_ref ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'paypal')
                                        <tr>
                                            <th>PayPal Order ID</th>
                                            <td>{{ $payment->paypal_order_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>PayPal Payer ID</th>
                                            <td>{{ $payment->paypal_payer_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>PayPal Payment ID</th>
                                            <td>{{ $payment->paypal_payment_id ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'sslcommerz')
                                        <tr>
                                            <th>SSLCommerz Transaction ID</th>
                                            <td>{{ $payment->sslcommerz_tran_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>SSLCommerz Session Key</th>
                                            <td>{{ $payment->sslcommerz_session_key ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>SSLCommerz Validation ID</th>
                                            <td>{{ $payment->sslcommerz_val_id ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'mollie')
                                        <tr>
                                            <th>Mollie Payment ID</th>
                                            <td>{{ $payment->mollie_payment_id ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'bkash')
                                        <tr>
                                            <th>bKash Payment ID</th>
                                            <td>{{ $payment->bkash_payment_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>bKash Transaction ID (TrxID)</th>
                                            <td>{{ $payment->bkash_trx_id ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($payment->payment_method == 'offline')
                                        <tr>
                                            <th>Receipt File</th>
                                            <td>
                                                @if ($payment->receipt_file)
                                                    <a href="{{ asset('storage/' . $payment->receipt_file) }}"
                                                        target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i> View Receipt
                                                    </a>
                                                @else
                                                    <span class="text-muted">No receipt uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @elseif($payment->payment_method == 'chapa')
                                        <tr>
                                            <th>Chapa Transaction Reference (tx_ref)</th>
                                            <td><code>{{ $payment->chapa_tx_ref ?? 'N/A' }}</code></td>
                                        </tr>
                                        <tr>
                                            <th>Chapa Gateway Reference</th>
                                            <td><code>{{ $payment->chapa_reference ?? 'N/A' }}</code></td>
                                        </tr>
                                        <tr>
                                            <th>Currency</th>
                                            <td><span class="badge badge-light border">{{ $payment->currency ?? 'ETB' }}</span></td>
                                        </tr>
                                    @endif
                                    @if ($payment->notes)
                                        <tr>
                                            <th>Notes</th>
                                            <td>{{ $payment->notes }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        <!-- Enrollment Details Tab -->
                        <div class="tab-pane fade" id="enrollment" role="tabpanel">
                            <div class="mt-4">
                                <h5>Enrollment Information</h5>
                                @if ($payment->enrollment)
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th width="40%">Enrollment ID</th>
                                                    <td>#{{ $payment->enrollment->id }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Enrollment Status</th>
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ $payment->enrollment->status == 'completed' ? 'success' : ($payment->enrollment->status == 'approved' ? 'primary' : ($payment->enrollment->status == 'pending' ? 'warning' : 'danger')) }}">
                                                            {{ ucfirst($payment->enrollment->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Enrolled Date</th>
                                                    <td>{{ $payment->enrollment->enrolled_at ? $payment->enrollment->enrolled_at->format('M d, Y h:i A') : $payment->enrollment->created_at->format('M d, Y h:i A') }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            @if ($payment->enrollment->course)
                                                <h6>Course Information</h6>
                                                <p><strong>Course:</strong> {{ $payment->enrollment->course->title }}</p>
                                                <p><strong>Instructor:</strong>
                                                    {{ $payment->enrollment->course->instructor->name ?? 'N/A' }}</p>
                                                <a href="{{ route('admin.courses.show', $payment->enrollment->course_id) }}"
                                                    class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View Course
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($payment->enrollment->user)
                                        <div class="mt-4">
                                            <h6>Student Information</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Name:</strong> {{ $payment->enrollment->user->name }}</p>
                                                    <p><strong>Email:</strong> {{ $payment->enrollment->user->email }}</p>
                                                    @if ($payment->enrollment->user->phone)
                                                        <p><strong>Phone:</strong> {{ $payment->enrollment->user->phone }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <a href="{{ route('admin.students.show', $payment->enrollment->user_id) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-user"></i> View Student Profile
                                                    </a>
                                                    <a href="{{ route('admin.enrollments.show', $payment->enrollment_id) }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="fas fa-clipboard-list"></i> View Enrollment Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-warning">No enrollment associated with this payment.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
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
