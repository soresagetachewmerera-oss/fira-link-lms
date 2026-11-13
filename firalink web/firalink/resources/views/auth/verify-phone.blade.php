@extends('layouts.app')

@section('title', 'Verify Phone Number - ' . site_name())

@section('content')
    <!-- Phone Verification Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover pt-6 pb-6"
        style="background-image: url('{{ asset('assets/front/img/home-1/hero/hero-bg.jpg') }}'); min-height: 85vh;">
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
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-6 col-md-8 col-sm-11">
                    <div class="mhq-register-wrapper bg-white shadow-lg rounded-4 p-4 p-md-5 wow fadeInUp" data-wow-delay=".3s"
                        style="border-radius: 20px;">
                        
                        <div class="mhq-register-header text-center mb-4">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="register-logo mb-3 d-inline-block">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}"
                                    alt="{{ $siteName }} logo" style="max-height: 48px;">
                            </a>
                            <div class="mb-3">
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill" style="font-size: 13px;">
                                    <i class="fas fa-shield-alt mr-1"></i> SMS OTP Verification Required
                                </span>
                            </div>
                            <h2 class="fw-bold mb-2">Verify Your Phone</h2>
                            <p class="text-muted mb-0">
                                We sent a <strong>{{ $codeLength }}-digit verification code</strong> via SMS to:
                            </p>
                            <div class="fs-5 fw-bold text-dark mt-1">
                                <code>{{ $maskedPhone }}</code>
                            </div>
                            <small class="text-muted d-block mt-1">Valid for {{ $expiryMinutes }} minutes.</small>
                        </div>

                        <!-- OTP Verification Form -->
                        <form action="{{ route('verification.phone.verify') }}" method="POST" class="mb-4">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="code" class="form-label text-muted small fw-bold">Enter Verification Code</label>
                                <div class="form-clt">
                                    <input type="text" name="code" id="code"
                                        class="form-control text-center fw-bold fs-4 py-3 @error('code') is-invalid @enderror"
                                        placeholder="{{ str_repeat('• ', $codeLength) }}"
                                        maxlength="{{ $codeLength }}"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        required
                                        autofocus
                                        style="letter-spacing: 8px; font-size: 24px; border-radius: 12px;">
                                </div>
                                @error('code')
                                    <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="theme-btn w-100 py-3 fw-bold text-white shadow-sm"
                                style="border-radius: 12px; font-size: 16px;">
                                <i class="fas fa-check-circle mr-1"></i> Verify & Complete Registration
                            </button>
                        </form>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mb-3 small">
                            <span class="text-muted">Didn't receive the SMS?</span>
                            <form action="{{ route('verification.phone.resend') }}" method="POST" id="resendForm" class="d-inline">
                                @csrf
                                <button type="submit" id="resendBtn" class="btn btn-link text-primary p-0 fw-bold"
                                    {{ $cooldownRemaining > 0 ? 'disabled' : '' }}>
                                    <span id="resendText">Resend SMS Code</span>
                                    <span id="countdownSpan" class="{{ $cooldownRemaining > 0 ? '' : 'd-none' }}">
                                        (<span id="cooldownSeconds">{{ $cooldownRemaining }}</span>s)
                                    </span>
                                </button>
                            </form>
                        </div>

                        <!-- Correction Section -->
                        <div class="accordion accordion-flush" id="phoneAccordion">
                            <div class="accordion-item border-0">
                                <p class="text-center mb-0">
                                    <a class="text-secondary small text-decoration-underline" data-bs-toggle="collapse"
                                        href="#changePhoneCollapse" role="button" aria-expanded="false">
                                        <i class="fas fa-edit mr-1"></i> Mistyped your phone number? Change it here
                                    </a>
                                </p>
                                <div class="collapse mt-3 @error('phone') show @enderror" id="changePhoneCollapse">
                                    <div class="card card-body bg-light border-0 rounded-3 p-3">
                                        <form action="{{ route('verification.phone.change') }}" method="POST">
                                            @csrf
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold text-dark">Correct Ethiopian Phone Number</label>
                                                <input type="tel" name="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror"
                                                    placeholder="0911234567 or +251911234567" required>
                                                @error('phone')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                                Update Phone & Dispatch New Code
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 pt-2 border-top">
                            <small class="text-muted">
                                <i class="fas fa-lock text-success mr-1"></i> Powered by SMS Ethiopia & Fira Link Security
                            </small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let cooldown = parseInt("{{ $cooldownRemaining }}", 10) || 0;
            const resendBtn = document.getElementById('resendBtn');
            const countdownSpan = document.getElementById('countdownSpan');
            const cooldownSeconds = document.getElementById('cooldownSeconds');

            if (cooldown > 0 && resendBtn && cooldownSeconds) {
                const interval = setInterval(function () {
                    cooldown--;
                    cooldownSeconds.textContent = cooldown;
                    if (cooldown <= 0) {
                        clearInterval(interval);
                        resendBtn.removeAttribute('disabled');
                        if (countdownSpan) countdownSpan.classList.add('d-none');
                    }
                }, 1000);
            }
        });
    </script>
@endsection
