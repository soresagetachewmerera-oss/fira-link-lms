@extends('layouts.app')

@section('title', 'Server Error - Fira Link')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Server Error',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-error-section fix section-padding">
        <div class="container">
            <div class="error-items text-center py-5">
                <div class="thumb wow fadeInUp" data-wow-delay=".3s">
                    <img src="{{ asset('assets/front/img/404-img.png') }}" alt="Error illustration" style="max-height: 220px; object-fit: contain;">
                </div>
                <h2 class="mt-4">Something went wrong</h2>
                <p class="mb-4 text-muted">We encountered an unexpected error while processing your request. Please try again or return to the homepage.</p>
                <a href="{{ route('home') }}" class="theme-btn wow fadeInUp" data-wow-delay=".4s">
                    Go To Homepage
                    <i class="far fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
@endsection
