@extends('layouts.app')

@section('content')
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
                <div class="col-lg-5 col-md-8">
                    <div class="mhq-login-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-login-header text-center mb-4">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="login-logo mb-3">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}" alt="{{ $siteName }} logo">
                            </a>
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">Verify Your Login</h2>
                            <p class="wow fadeInUp" data-wow-delay=".3s">We sent a 6-digit code to
                                <strong>{{ $email }}</strong>
                            </p>
                        </div>

                        <form action="{{ route('login.otp.verify') }}" method="POST" class="mhq-login-form">
                            @csrf

                            <div class="form-clt wow fadeInUp" data-wow-delay=".4s">
                                <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                                    placeholder="Enter 6-digit code" class="@error('code') is-invalid @enderror" required
                                    autofocus>
                            </div>
                            @error('code')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror

                            <button type="submit" class="theme-btn w-100 mb-3 wow fadeInUp" data-wow-delay=".5s">
                                Verify & Continue
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </button>
                        </form>

                        <form action="{{ route('login.otp.resend') }}" method="POST" class="text-center">
                            @csrf
                            <button type="submit" class="btn btn-link p-0 wow fadeInUp" data-wow-delay=".6s">
                                Didn’t get the code? Resend
                            </button>
                        </form>

                        <div class="text-center wow fadeInUp" data-wow-delay=".7s">
                            <a href="{{ route('login') }}" class="back-btn">Back to sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection