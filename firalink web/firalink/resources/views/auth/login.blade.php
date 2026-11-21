@extends('layouts.app')

@section('content')
    <!-- Login Section Start -->
    <section class="mhq-hero-section fix mhq-hero-1 pt-0" style="background: #F9F7FA; min-height: 85vh; display: flex; align-items: center;">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-6 col-md-8">
                    <div class="mhq-login-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-login-header text-center mb-4">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="login-logo mb-3">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}" alt="{{ $siteName }} logo">
                            </a>
                            <h1 class="wow fadeInUp" data-wow-delay=".2s" style="font-size: 1.75rem; font-weight: 700; color: #1E1B4B;">{{ __('auth.welcome_back') }}</h1>
                            <p class="wow fadeInUp" data-wow-delay=".3s">{{ __('auth.sign_in_subtitle') }}</p>
                        </div>

                        <form action="{{ route('login') }}" method="POST" class="mhq-login-form">
                            @csrf



                            <div class="row">
                                <div class="col-12 wow fadeInUp" data-wow-delay=".4s">
                                    <div class="form-clt">
                                        <label class="visually-hidden" for="email">{{ __('auth.your_email') }}</label>
                                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                                            placeholder="{{ __('auth.your_email') }}" class="@error('email') is-invalid @enderror" required
                                            autocomplete="email" autofocus>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 wow fadeInUp" data-wow-delay=".5s">
                                    <div class="form-clt position-relative">
                                        <label class="visually-hidden" for="password">{{ __('auth.your_password') }}</label>
                                        <input type="password" name="password" id="password" placeholder="{{ __('auth.your_password') }}"
                                            class="@error('password') is-invalid @enderror" required
                                            autocomplete="current-password">
                                        <button type="button" class="toggle-password position-absolute" aria-label="Show password" aria-pressed="false">
                                            <i class="fa-regular fa-eye" id="togglePassword"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4 wow fadeInUp"
                                data-wow-delay=".6s">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">
                                        {{ __('auth.remember_me') }}
                                    </label>
                                </div>
                                <a href="{{ route('password.request') }}" class="text-primary">{{ __('auth.forgot_password') }}</a>
                            </div>

                            <x-recaptcha-widget class="mb-4 wow fadeInUp" data-wow-delay=".65s" action="login" />

                            <button type="submit" class="theme-btn w-100 mb-3 wow fadeInUp" data-wow-delay=".7s"
                                id="loginSubmitBtn">
                                {{ __('auth.sign_in') }}
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </button>

                            <div class="text-center wow fadeInUp" data-wow-delay=".8s">
                                <p class="mb-0">
                                    {{ __('auth.dont_have_account') }}
                                    <a href="{{ route('register') }}" class="text-primary fw-bold">{{ __('auth.create_account') }}</a>
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
            document.addEventListener('DOMContentLoaded', function () {
                const togglePassword = document.getElementById('togglePassword');
                const password = document.getElementById('password');
                const loginForm = document.querySelector('.mhq-login-form');
                const submitBtn = document.getElementById('loginSubmitBtn');

                if (togglePassword) {
                    togglePassword.addEventListener('click', function () {
                        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                        password.setAttribute('type', type);
                        this.setAttribute('aria-pressed', String(type === 'text'));
                        this.setAttribute('aria-label', type === 'text' ? 'Hide password' : 'Show password');
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                if (loginForm && submitBtn) {
                    loginForm.addEventListener('submit', function () {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '{{ __('auth.signing_in') }} <i class="fa fa-spinner fa-spin"></i>';
                    });
                }
            });
        </script>
    @endpush
@endsection
