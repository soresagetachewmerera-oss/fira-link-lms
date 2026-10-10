@extends('layouts.app')

@section('content')
    <!-- Forgot Password Section Start -->
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
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">Forgot Password</h2>
                            <p class="wow fadeInUp" data-wow-delay=".3s">Enter your email to receive a password reset link
                            </p>
                        </div>

                        <form action="{{ route('password.email') }}" method="POST" class="mhq-login-form">
                            @csrf


                            <div class="row">
                                <div class="col-12 wow fadeInUp" data-wow-delay=".4s">
                                    <div class="form-clt">
                                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                                            placeholder="Your Email" class="@error('email') is-invalid @enderror" required
                                            autocomplete="email" autofocus>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <x-recaptcha-widget class="mb-4 wow fadeInUp" data-wow-delay=".45s" action="password_reset" />

                            <button type="submit" class="theme-btn w-100 mb-3 wow fadeInUp" data-wow-delay=".5s">
                                Send Reset Link
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </button>

                            <div class="text-center wow fadeInUp" data-wow-delay=".6s">
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
@endsection