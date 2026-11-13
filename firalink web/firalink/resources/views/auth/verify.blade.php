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
                <div class="col-lg-6 col-md-8">
                    <div class="mhq-login-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-login-header text-center mb-4">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="login-logo mb-3">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}" alt="{{ $siteName }} logo">
                            </a>
                            <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('Verify Your Email Address') }}</h2>
                            <p class="wow fadeInUp" data-wow-delay=".3s">
                                {{ __('Before proceeding, please check your email for a verification link.') }}
                            </p>
                        </div>

                        @if (session('resent'))
                            <div class="alert alert-success text-center mb-4" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        <div class="text-center mb-4">
                            <p class="text-muted">
                                {{ __('If you did not receive the email, click the button below to request another.') }}
                            </p>

                            <form action="{{ route('verification.resend') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="theme-btn mb-3">
                                    {{ __('Click here to request another') }}
                                    <i class="fa-solid fa-arrow-rotate-right"></i>
                                </button>
                            </form>
                        </div>

                        <div class="text-center pt-3 border-top">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link text-muted p-0">
                                    <i class="fas fa-sign-out-alt mr-1"></i> {{ __('Log Out') }}
                                </button>
                            </form>
                            <span class="mx-2 text-muted">|</span>
                            <a href="{{ route('home') }}" class="text-muted">
                                <i class="fas fa-home mr-1"></i> {{ __('Return to Homepage') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

