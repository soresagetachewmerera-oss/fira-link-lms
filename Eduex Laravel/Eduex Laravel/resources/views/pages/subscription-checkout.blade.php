@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => __('frontend.subscription_checkout'),
        'base_url' => route('subscriptions.index'),
    ])
@endsection

@section('content')
    <!-- Checkout Section Start -->
    <section class="mhq-checkout-section section-padding fix">
        <div class="container">
            <form action="{{ route('subscriptions.process-checkout', $plan->id) }}" method="POST" enctype="multipart/form-data" id="subscription-checkout-form">
                @csrf
                <div class="row g-4">
                    <!-- Left: Payment Method Selection -->
                    <div class="col-lg-7">
                        <div class="card-box p-4" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                            <h4 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-credit-card text-primary me-2"></i> {{ __('frontend.select_payment_gateway') }}</h4>

                            <div class="payment-methods-list">
                                @forelse($onlineGateways as $gateway)
                                    <div class="form-check payment-method-item border p-3 rounded-3 mb-3 cursor-pointer" style="border-color: #cbd5e1 !important; transition: all 0.2s;">
                                        <input class="form-check-input me-3" type="radio" name="payment_method" id="gateway_{{ $gateway->identifier }}" value="{{ $gateway->identifier }}" {{ $loop->first ? 'checked' : '' }}>
                                        <label class="form-check-label w-100 cursor-pointer" for="gateway_{{ $gateway->identifier }}">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <strong class="d-block text-capitalize fs-6 text-dark">{{ $gateway->name }}</strong>
                                                    <small class="text-muted">{{ $gatewayDescriptions[$gateway->identifier] ?? 'Pay securely via ' . $gateway->name }}</small>
                                                </div>
                                                <i class="fa-solid fa-lock text-primary fs-4"></i>
                                            </div>
                                        </label>
                                    </div>
                                @empty
                                @endforelse

                                @if($offlineGateway && $offlineGateway->is_enabled)
                                    <div class="form-check payment-method-item border p-3 rounded-3 mb-3 cursor-pointer" style="border-color: #cbd5e1 !important;">
                                        <input class="form-check-input me-3" type="radio" name="payment_method" id="gateway_offline" value="offline" {{ $onlineGateways->isEmpty() ? 'checked' : '' }}>
                                        <label class="form-check-label w-100 cursor-pointer" for="gateway_offline">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <strong class="d-block fs-6 text-dark">{{ __('frontend.offline_bank_transfer') }}</strong>
                                                    <small class="text-muted">{{ __('frontend.upload_receipt_proof') }}</small>
                                                </div>
                                                <i class="fa-solid fa-building-columns text-secondary fs-4"></i>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Offline Details Box -->
                                    <div id="offline-details-container" class="border rounded-3 p-3 bg-light mb-3" style="display: none; border-color: #cbd5e1 !important;">
                                        <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-circle-info text-info me-1"></i> Payment Instructions:</h6>
                                        <div class="small text-muted mb-3">{!! nl2br(e($offlineInstructions)) !!}</div>

                                        <div class="form-group mb-3">
                                            <label for="receipt_file" class="form-label fw-bold small">{{ __('frontend.upload_receipt_proof') }} <span class="text-danger">*</span></label>
                                            <input type="file" name="receipt_file" id="receipt_file" class="form-control" accept="image/*,.pdf">
                                            <small class="text-muted">Supported formats: JPG, PNG, PDF (Max 5MB)</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="notes" class="form-label fw-bold small">Transaction Notes / Reference</label>
                                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="e.g. Bank Ref #12345678"></textarea>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Order Summary Card -->
                    <div class="col-lg-5">
                        <div class="card-box p-4 position-sticky" style="top: 100px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                            <h4 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> {{ __('frontend.subscription_summary') }}</h4>
                            
                            <div class="border-bottom pb-3 mb-3" style="border-color: #f1f5f9 !important;">
                                <h5 class="fw-bold mb-1" style="color: #6C5CE7;">{{ $plan->name }}</h5>
                                <p class="text-muted small mb-0">{{ $plan->description }}</p>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">{{ __('frontend.billing_cycle') }}</span>
                                <strong class="text-capitalize text-dark">{{ str_replace('_', ' ', $plan->billing_period) }} ({{ $plan->duration_days }} {{ __('frontend.days') }})</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">{{ __('frontend.included_courses') }}</span>
                                <strong class="text-dark">{{ $plan->courses()->count() }} {{ __('frontend.courses_and_live_classes') }}</strong>
                            </div>

                            @if($plan->bundles()->count() > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('frontend.included_bundles') }}</span>
                                    <strong class="text-dark">{{ $plan->bundles()->count() }} {{ __('frontend.course_bundles') }}</strong>
                                </div>
                            @endif

                            <hr style="border-color: #f1f5f9;" class="my-3">

                            @if($plan->has_discount)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('frontend.regular_price') }}</span>
                                    <span class="text-decoration-line-through text-muted">{{ currency_symbol() }}{{ number_format($plan->price, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('frontend.discount_savings') }}</span>
                                    <span class="text-success font-weight-bold">-{{ currency_symbol() }}{{ number_format($plan->price - $plan->discount_price, 2) }}</span>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center my-3 pt-2 border-top" style="border-color: #f1f5f9 !important;">
                                <span class="fs-5 fw-bold text-dark">{{ __('frontend.total_amount') }}</span>
                                <span class="display-6 fw-bold" style="color: #6C5CE7;">{{ currency_symbol() }}{{ number_format($plan->effective_price, 2) }}</span>
                            </div>

                            <button type="submit" id="submit-subscription-btn" class="theme-btn w-100 text-center justify-content-center mt-2" style="border-radius: 8px;">
                                <i class="fa-solid fa-lock me-2"></i> {{ __('frontend.complete_subscription') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <!-- Checkout Section End -->

    @push('scripts')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const offlineRadio = document.getElementById('gateway_offline');
                const offlineContainer = document.getElementById('offline-details-container');
                const allPaymentRadios = document.querySelectorAll('input[name="payment_method"]');
                const form = document.getElementById('subscription-checkout-form');
                const submitBtn = document.getElementById('submit-subscription-btn');

                function toggleOfflineContainer() {
                    if (offlineRadio && offlineRadio.checked) {
                        if (offlineContainer) offlineContainer.style.display = 'block';
                    } else if (offlineContainer) {
                        offlineContainer.style.display = 'none';
                    }
                }

                allPaymentRadios.forEach(radio => {
                    radio.addEventListener('change', toggleOfflineContainer);
                });

                toggleOfflineContainer();

                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
                        if (!selectedRadio) {
                            alert('Please select a payment method.');
                            return;
                        }

                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Processing...';

                        const formData = new FormData(form);

                        fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.checkout_url) {
                                    window.location.href = data.checkout_url;
                                } else if (data.authorization_url) {
                                    window.location.href = data.authorization_url;
                                } else if (data.redirect_url) {
                                    window.location.href = data.redirect_url;
                                } else if (data.order_id && data.key) {
                                    // Razorpay Popup
                                    const options = {
                                        key: data.key,
                                        amount: data.amount,
                                        currency: 'INR',
                                        name: data.name || 'Subscription',
                                        description: data.description || 'Package Plan',
                                        order_id: data.order_id,
                                        prefill: data.prefill || {},
                                        handler: function(razorpayResponse) {
                                            const cbForm = document.createElement('form');
                                            cbForm.method = 'POST';
                                            cbForm.action = data.callback_url;

                                            const fields = ['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'];
                                            fields.forEach(f => {
                                                const inp = document.createElement('input');
                                                inp.type = 'hidden';
                                                inp.name = f;
                                                inp.value = razorpayResponse[f];
                                                cbForm.appendChild(inp);
                                            });

                                            const csrfInp = document.createElement('input');
                                            csrfInp.type = 'hidden';
                                            csrfInp.name = '_token';
                                            csrfInp.value = '{{ csrf_token() }}';
                                            cbForm.appendChild(csrfInp);

                                            document.body.appendChild(cbForm);
                                            cbForm.submit();
                                        },
                                        modal: {
                                            ondismiss: function() {
                                                submitBtn.disabled = false;
                                                submitBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> {{ __("frontend.complete_subscription") }}';
                                            }
                                        }
                                    };
                                    const rzp = new Razorpay(options);
                                    rzp.open();
                                } else {
                                    window.location.href = "{{ route('student.subscription') }}";
                                }
                            } else {
                                alert(data.message || 'An error occurred during payment processing.');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> {{ __("frontend.complete_subscription") }}';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Payment processing error. Please try again.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> {{ __("frontend.complete_subscription") }}';
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
