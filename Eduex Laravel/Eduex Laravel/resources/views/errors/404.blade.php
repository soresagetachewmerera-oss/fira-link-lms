@extends('layouts.app')

@section('title', $title ?? 'Page Not Found')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? 'Page Not Found',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-error-section fix section-padding">
        <div class="container">
            <div class="error-items text-center">
                <div class="thumb wow fadeInUp" data-wow-delay=".3s">
                    <img src="{{ asset('assets/front/img/404-img.png') }}" alt="404 illustration">
                </div>
                <h2 class="mt-4">Oops! The page you were looking for doesn’t exist.</h2>
                <p class="mb-4">It might have been moved or deleted. Try going back to the homepage.</p>
                <a href="{{ route('home') }}" class="theme-btn wow fadeInUp" data-wow-delay=".4s">
                    Go To Homepage
                    <i class="far fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
@endsection
