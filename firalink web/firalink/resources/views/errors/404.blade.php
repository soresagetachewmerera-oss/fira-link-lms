@extends('layouts.app')

@php
    $isPrivate = true;
    $pageTitle = 'Page Not Found';
    $metaDescription = 'The requested page was not found on Fira Link Academy. Explore our trade programs, certified courses, or return to the homepage.';
@endphp

@section('title', 'Page Not Found | Fira Link Academy')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Page Not Found',
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
                <p class="mb-4">It might have been moved, renamed, or deleted. Use the links below to continue exploring Fira Link Academy:</p>
                <div class="d-flex justify-content-center flex-wrap gap-3 mb-4">
                    <a href="{{ route('home') }}" class="theme-btn">
                        Go To Homepage
                        <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                    <a href="{{ route('programs') }}" class="theme-btn style-2">
                        View Programs
                        <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                    <a href="{{ route('free-tools.index') }}" class="theme-btn style-2">
                        Free Trade Tools
                        <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
