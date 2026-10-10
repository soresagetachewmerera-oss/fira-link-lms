@extends('layouts.app')

@section('breadcrumb')
    @php
        $page_title = __('frontend.checkout');
        $base_url = route('home');
    @endphp
    @include('partials.breadcrumb')
@endsection

@section('content')
    <!-- Checkout Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover py-4 hero-bg-default">
        <div class="hero-shape-1 float-bob-x">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-01.png') }}" alt="img">
        </div>
        <div class="hero-shape-2 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-02.png') }}" alt="img">
        </div>
        <div class="hero-shape-3 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-03.png') }}" alt="img">
        </div>
        <div class="container">
            <div class="row justify-content-center align-items-center py-5">
                <div class="col-lg-10">
                    <div class="checkout-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="checkout-header text-center mb-4">
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.complete_enrollment') }}</h2>
                            <p class="wow fadeInUp" data-wow-delay=".3s">{{ __('frontend.review_details_proceed') }}
                            </p>
                        </div>

                        <div class="row">
                            <div class="col-lg-8">
                                  @php
                                      $isSubscription = isset($isSubscription) && $isSubscription;
                                      $isBundle = isset($isBundle) && $isBundle;
                                      $itemTitle = $isSubscription ? ($plan->name ?? '') : ($course->title ?? '');
                                      $itemImage = !$isSubscription && isset($course->featured_image) ? asset('storage/' . $course->featured_image) : asset('assets/front/img/inner/courses-details/courses-details.jpg');
                                  @endphp
                                  <form id="checkoutForm"
                                     action="{{ $isSubscription ? route('subscriptions.process-checkout', $plan->id) : ($isBundle ? route('bundles.checkout.process', $course->id) : route('courses.checkout.process', $course->id)) }}"
                                     method="POST" enctype="multipart/form-data">
                                     @csrf
                                     @if ($isSubscription)
                                         <input type="hidden" name="type" value="subscription">
                                     @elseif ($isBundle)
                                         <input type="hidden" name="type" value="bundle">
                                     @endif
                                     <input type="hidden" name="coupon_code" id="applied_coupon_code" value="">

                                     <div class="course-summary mb-4 wow fadeInUp" data-wow-delay=".4s">
                                         <h4 class="mb-3">{{ $isSubscription ? __('frontend.subscription_summary') : __('frontend.course_summary') }}</h4>
                                         <div class="d-flex align-items-start">
                                             <img src="{{ $itemImage }}" alt="{{ $itemTitle }}" class="me-3 course-thumb-lg">
                                             <div>
                                                 <h5>{{ $itemTitle }}</h5>
                                                 @if ($isSubscription)
                                                     <p class="mb-1"><small>{{ __('frontend.billing_cycle') }} {{ str_replace('_', ' ', $plan->billing_period) }} ({{ $plan->duration_days }} {{ __('frontend.days') }})</small></p>
                                                     <p class="mb-1"><small>{{ __('frontend.included_courses') }} {{ $plan->courses()->count() }} {{ __('frontend.courses_and_live_classes') }}</small></p>
                                                 @elseif (isset($course->instructor))
                                                     <p class="mb-1"><small>{{ __('frontend.instructor') }}: {{ $course->instructor->name }}</small></p>
                                                 @endif
                                                 <p class="mb-0"><strong>{{ __('frontend.price') }}: {{ $price > 0 ? currency_format($price) : __('frontend.free') }}</strong></p>
                                             </div>
                                         </div>
                                     </div>

                                    @if ($price > 0)
                                        <div class="payment-method mb-4 wow fadeInUp" data-wow-delay=".5s">
                                            <h4 class="mb-3">{{ __('frontend.select_payment_method') }}</h4>
                                            @php($first = true)
                                            @foreach ($onlineGateways as $gateway)
                                                @php($identifier = $gateway->identifier)
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="payment_method"
                                                        id="{{ $identifier }}" value="{{ $identifier }}"
                                                        {{ $first ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="{{ $identifier }}">
                                                        <strong>{{ $gateway->name }} ({{ __('frontend.online_payment') }})</strong>
                                                        <p class="mb-0 small text-muted">
                                                            {{ $gatewayDescriptions[$identifier] ?? __('frontend.pay_securely') }}
                                                        </p>
                                                    </label>
                                                </div>
                                                @php($first = false)
                                            @endforeach
                                            @if ($offlineGateway && $offlineGateway->is_enabled)
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="payment_method"
                                                        id="offline" value="offline" {{ $first ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="offline">
                                                        <strong>{{ __('frontend.offline_payment') }}</strong>
                                                        <p class="mb-0 small text-muted">{{ __('frontend.offline_payment_desc') }}</p>
                                                    </label>
                                                </div>
                                            @endif
                                            @error('payment_method')
                                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        @if ($offlineGateway && $offlineGateway->is_enabled)
                                            <div id="offlinePaymentSection"
                                                class="offline-payment-section mb-4 wow fadeInUp {{ !$onlineGateways->count() ? 'is-visible' : '' }}"
                                                data-wow-delay=".6s">
                                                <h5 class="mb-3">{{ __('frontend.payment_details') }}</h5>
                                                @if (!empty($offlineInstructions))
                                                    <div class="alert alert-info alert-preline" role="alert">
                                                        {{ $offlineInstructions }}
                                                    </div>
                                                @endif
                                                <div class="mb-3">
                                                    <label for="transaction_id" class="form-label">{{ __('frontend.transaction_id') }} <span
                                                            class="text-danger">*</span></label>
                                                    <div class="form-clt">
                                                        <input type="text" name="transaction_id" id="transaction_id"
                                                            value="{{ old('transaction_id') }}"
                                                            placeholder="{{ __('frontend.transaction_id') }}"
                                                            class="@error('transaction_id') is-invalid @enderror">
                                                    </div>
                                                    @error('transaction_id')
                                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label for="receipt_file" class="form-label">{{ __('frontend.upload_receipt') }} <span
                                                            class="text-danger">*</span></label>
                                                    <div class="form-clt">
                                                        <input type="file" name="receipt_file" id="receipt_file"
                                                            accept=".pdf,.jpg,.jpeg,.png"
                                                            class="@error('receipt_file') is-invalid @enderror">
                                                    </div>
                                                    <small class="text-muted">{{ __('frontend.accepted_formats') }}</small>
                                                    @error('receipt_file')
                                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    <div class="form-actions wow fadeInUp" data-wow-delay=".7s">
                                        <button type="submit" class="theme-btn w-100" id="submitBtn">
                                            @if ($price > 0)
                                                {{ __('frontend.proceed_to_payment') }}
                                            @else
                                                {{ __('frontend.complete_enrollment') }}
                                            @endif
                                            <i class="fa-solid fa-arrow-up-right"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-lg-4">
                                <div class="checkout-summary bg-light p-4 rounded wow fadeInUp" data-wow-delay=".5s">
                                    <h5 class="mb-3">{{ __('frontend.order_summary') }}</h5>
                                     <div class="d-flex justify-content-between mb-2">
                                         <span>{{ $isSubscription ? __('frontend.subscriptions') : ($isBundle ? __('frontend.bundle') : __('frontend.course')) }}:</span>
                                         <span>{{ $itemTitle }}</span>
                                     </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('frontend.price') }}:</span>
                                        <span id="summary-original-price"><strong>{{ $price > 0 ? currency_format($price) : __('frontend.free') }}</strong></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 d-none" id="summary-discount-row">
                                        <span>{{ __('frontend.discount') }}:</span>
                                        <span id="summary-discount-val" class="text-success"><strong>-</strong></span>
                                    </div>
                                    @if(!$isBundle && $price > 0)
                                        <hr>
                                        <div class="coupon-section mb-3">
                                            <label for="coupon_code_input" class="form-label font-weight-bold" style="font-size: 14px;">Have a coupon?</label>
                                            <div class="input-group d-flex" style="gap: 5px;">
                                                <input type="text" id="coupon_code_input" class="form-control" placeholder="Enter code" style="border-radius: 4px; padding: 8px 12px; border: 1px solid #cbd5e1; flex: 1;">
                                                <button type="button" id="apply_coupon_btn" class="btn btn-primary" style="border-radius: 4px; font-size: 13px; padding: 8px 15px; margin: 0; background-color: #6C5CE7; border-color: #6C5CE7; color: white;">Apply</button>
                                            </div>
                                            <div id="coupon-message" class="mt-2" style="font-size: 13px;"></div>
                                        </div>
                                    @endif
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span><strong>{{ __('frontend.total') }}:</strong></span>
                                        <span id="summary-total-price"><strong>{{ $price > 0 ? currency_format($price) : __('frontend.free') }}</strong></span>
                                    </div>
                                    
                                        <button type="submit" form="checkoutForm" class="theme-btn style-2 w-100 text-center">
                                            @if ($price > 0)
                                                {{ __('frontend.proceed_to_payment') }}
                                            @else
                                                {{ __('frontend.complete_enrollment') }}
                                            @endif <i class="fa-solid fa-arrow-up-right"></i>
                                        </button>
                                        <a href="{{ route('home') }}" class="text-center d-block mt-3 text-muted">
                                            <i class="fa-regular fa-arrow-left me-1"></i> Back to Home
                                        </a>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            $(document).ready(function() {

                $('#apply_coupon_btn').on('click', function(e) {
                    e.preventDefault();
                    var code = $('#coupon_code_input').val().trim();
                    var courseId = '{{ $course->id }}';
                    var $message = $('#coupon-message');
                    var $btn = $(this);

                    if (!code) {
                        $message.html('<span class="text-danger">Please enter a coupon code.</span>');
                        return;
                    }

                    $btn.prop('disabled', true).html('Checking...');
                    $message.html('');

                    $.ajax({
                        url: '{{ route('coupons.validate') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            code: code,
                            course_id: courseId
                        },
                        success: function(response) {
                            $btn.prop('disabled', false).html('Apply');
                            if (response.success) {
                                $message.html('<span class="text-success">Coupon applied successfully!</span>');
                                $('#applied_coupon_code').val(response.coupon.code);
                                $('#summary-discount-row').removeClass('d-none');
                                $('#summary-discount-val').html('<strong>-' + response.discount_amount_formatted + '</strong>');
                                $('#summary-total-price').html('<strong>' + response.final_price_formatted + '</strong>');
                                
                                if (response.final_price === 0) {
                                    $('.payment-method').slideUp();
                                    $('#offlinePaymentSection').slideUp();
                                    $('#submitBtn, button[form="checkoutForm"]').html('Complete Enrollment <i class="fa-solid fa-arrow-up-right"></i>');
                                } else {
                                    $('.payment-method').slideDown();
                                    $('#submitBtn, button[form="checkoutForm"]').html('Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                }
                            } else {
                                $message.html('<span class="text-danger">' + (response.message || 'Invalid coupon code.') + '</span>');
                                resetCoupon();
                            }
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false).html('Apply');
                            var msg = 'Invalid coupon code.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            $message.html('<span class="text-danger">' + msg + '</span>');
                            resetCoupon();
                        }
                    });
                });

                function resetCoupon() {
                    $('#applied_coupon_code').val('');
                    $('#summary-discount-row').addClass('d-none');
                    $('#summary-discount-val').html('<strong>-</strong>');
                    $('#summary-total-price').html('<strong>{{ $price > 0 ? currency_format($price) : __('frontend.free') }}</strong>');
                    $('.payment-method').slideDown();
                }

                $('input[name="payment_method"]').on('change', function() {
                    if ($(this).val() === 'offline') {
                        $('#offlinePaymentSection').slideDown().addClass('is-visible');
                        $('#transaction_id').prop('required', true);
                        $('#receipt_file').prop('required', true);
                    } else {
                        $('#offlinePaymentSection').slideUp().removeClass('is-visible');
                        $('#transaction_id').prop('required', false);
                        $('#receipt_file').prop('required', false);
                    }
                });

                $('input[name="payment_method"]:checked').trigger('change');

                $('#checkoutForm').on('submit', function(e) {
                    const paymentMethod = $('input[name="payment_method"]:checked').val();
                    const price = {{ $price }};

                    if (price > 0 && paymentMethod === 'razorpay') {
                        e.preventDefault();
                        handleRazorpayPayment();
                    } else if (price > 0 && paymentMethod === 'stripe') {
                        e.preventDefault();
                        handleStripePayment();
                    } else if (price > 0 && paymentMethod === 'paystack') {
                        e.preventDefault();
                        handlePaystackPayment();
                    } else if (price > 0 && paymentMethod === 'flutterwave') {
                        e.preventDefault();
                        handleFlutterwavePayment();
                    } else if (price > 0 && paymentMethod === 'paypal') {
                        e.preventDefault();
                        handlePaypalPayment();
                    } else if (price > 0 && paymentMethod === 'sslcommerz') {
                        e.preventDefault();
                        handleSslcommerzPayment();
                    } else if (price > 0 && paymentMethod === 'mollie') {
                        e.preventDefault();
                        handleMolliePayment();
                    } else if (price > 0 && paymentMethod === 'bkash') {
                        e.preventDefault();
                        handleBkashPayment();
                    } else if (price > 0 && paymentMethod === 'xpay') {
                        e.preventDefault();
                        handleXpayPayment();
                    } else if (price > 0 && paymentMethod === 'chapa') {
                        e.preventDefault();
                        handleChapaPayment();
                    }
                });

                function handleRazorpayPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                const options = {
                                    key: response.key,
                                    amount: response.amount,
                                    currency: 'INR',
                                    name: response.name,
                                    description: response.description,
                                    order_id: response.order_id,
                                    prefill: response.prefill,
                                    handler: function(razorpayResponse) {
                                        const form = $('<form>', {
                                            method: 'POST',
                                            action: response.callback_url
                                        });

                                        form.append($('<input>', {
                                            type: 'hidden',
                                            name: 'razorpay_order_id',
                                            value: razorpayResponse.razorpay_order_id
                                        }));

                                        form.append($('<input>', {
                                            type: 'hidden',
                                            name: 'razorpay_payment_id',
                                            value: razorpayResponse.razorpay_payment_id
                                        }));

                                        form.append($('<input>', {
                                            type: 'hidden',
                                            name: 'razorpay_signature',
                                            value: razorpayResponse.razorpay_signature
                                        }));

                                        form.append($('<input>', {
                                            type: 'hidden',
                                            name: 'enrollment_id',
                                            value: response.enrollment_id
                                        }));

                                        form.append($('<input>', {
                                            type: 'hidden',
                                            name: '_token',
                                            value: '{{ csrf_token() }}'
                                        }));

                                        $('body').append(form);
                                        form.submit();
                                    },
                                    modal: {
                                        ondismiss: function() {
                                            $('#submitBtn').prop('disabled', false).html(
                                                '{{ __('frontend.proceed_to_payment') }} <i class="fa-solid fa-arrow-up-right"></i>'
                                                );
                                        }
                                    }
                                };

                                const razorpay = new Razorpay(options);
                                razorpay.open();
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        }
                    });
                }

                function handleStripePayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        }
                    });
                }

                function handlePaystackPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.authorization_url) {
                                window.location.href = response.authorization_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        }
                    });
                }

                function handleFlutterwavePayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        }
                    });
                }

                function handlePaypalPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        }
                    });
                }

                function handleSslcommerzPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert(response.message || 'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            let errorMessage = 'An error occurred. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    if (errorData.message) {
                                        errorMessage = errorData.message;
                                    }
                                } catch (e) {
                                        
                                }
                            }
                            alert(errorMessage);
                        }
                    });
                }

                function handleMolliePayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert(response.message || 'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            let errorMessage = 'An error occurred. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    if (errorData.message) {
                                        errorMessage = errorData.message;
                                    }
                                } catch (e) {
                                    
                                }
                            }
                            alert(errorMessage);
                        }
                    });
                }

                function handleBkashPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert(response.message || 'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            let errorMessage = 'An error occurred. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    if (errorData.message) {
                                        errorMessage = errorData.message;
                                    }
                                } catch (e) {
                                    
                                }
                            }
                            alert(errorMessage);
                        }
                    });
                }

                function handleXpayPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert(response.message || 'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            let errorMessage = 'An error occurred. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            alert(errorMessage);
                        }
                    });
                }

                function handleChapaPayment() {
                    $('#submitBtn').prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $('#submitBtn').prop('disabled', false).html(
                                    'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                                alert(response.message || 'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $('#submitBtn').prop('disabled', false).html(
                                'Proceed to Payment <i class="fa-solid fa-arrow-up-right"></i>');
                            let errorMessage = 'An error occurred. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            alert(errorMessage);
                        }
                    });
                }
            });
        </script>
    @endpush

@endsection
