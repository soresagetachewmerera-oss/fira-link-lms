@extends('layouts.app')

@section('content')
    <!-- Reset Password Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover pt-0"
        style="background-image: url('{{ asset('assets/front/img/home-1/hero/hero-bg.jpg') }}');">
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
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-6 col-md-8">
                    <div class="mhq-login-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-login-header text-center mb-4">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="login-logo mb-3">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}" alt="{{ $siteName }} logo">
                            </a>
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">Reset Password</h2>
                            <p class="wow fadeInUp" data-wow-delay=".3s">Enter your new password below</p>
                        </div>

                        <form action="{{ route('password.update') }}" method="POST" class="mhq-login-form">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token ?? request()->token }}">

                            <div class="row">
                                <div class="col-12 wow fadeInUp" data-wow-delay=".4s">
                                    <div class="form-clt">
                                        <input type="email" name="email" id="email"
                                            value="{{ old('email', request()->email) }}" placeholder="Your Email"
                                            class="@error('email') is-invalid @enderror" required autocomplete="email"
                                            autofocus>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 wow fadeInUp" data-wow-delay=".5s">
                                    <div class="form-clt position-relative">
                                        <input type="password" name="password" id="password" placeholder="New Password"
                                            class="@error('password') is-invalid @enderror" required
                                            autocomplete="new-password">
                                        <span class="toggle-password position-absolute">
                                            <i class="fa-regular fa-eye" id="togglePassword"></i>
                                        </span>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 wow fadeInUp" data-wow-delay=".6s">
                                    <div class="form-clt position-relative">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            placeholder="Confirm New Password" required autocomplete="new-password">
                                        <span class="toggle-password-confirm position-absolute">
                                            <i class="fa-regular fa-eye" id="togglePasswordConfirm"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="theme-btn w-100 mb-3 wow fadeInUp" data-wow-delay=".7s">
                                Reset Password
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </button>

                            <div class="text-center wow fadeInUp" data-wow-delay=".8s">
                                <p class="mb-0">
                                    Remember your password?
                                    <a href="{{ route('login') }}" class="text-primary fw-bold">Sign In</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const togglePassword = document.getElementById('togglePassword');
                const password = document.getElementById('password');

                const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
                const passwordConfirm = document.getElementById('password_confirmation');

                if (togglePassword) {
                    togglePassword.addEventListener('click', function() {
                        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                        password.setAttribute('type', type);
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                if (togglePasswordConfirm) {
                    togglePasswordConfirm.addEventListener('click', function() {
                        const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordConfirm.setAttribute('type', type);
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }
            });
        </script>
    @endpush
@endsection
