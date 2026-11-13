@extends('layouts.app')

@section('breadcrumb')
    @php
        $page_title = 'Our Event';
        $base_url = route('home');
    @endphp
    @include('partials.breadcrumb')
@endsection

@section('content')
    <!-- Event Section Start -->
    <section class="mhq-event-section-2 section-padding fix">
        <div class="container">
            <div class="row g-4">
                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp">
                    <div class="mhq-event-box-items-2 mt-0">
                        <div class="mhq-image">
                            <img src="{{ asset('assets/img/inner/event/01.jpg') }}" alt="img">
                        </div>
                        <div class="mhq-content">
                            <h3><a
                                    href="{{ route('events.show', ['slug' => 'teaching-students-to-think-beyond-the-textbook']) }}">Teaching
                                    Students to Think Beyond the Textbook</a></h3>
                            <ul class="list-box">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    England
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    09 May, 2025
                                </li>
                            </ul>
                            <a href="{{ route('events.show', ['slug' => 'teaching-students-to-think-beyond-the-textbook']) }}"
                                class="theme-btn">
                                View Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".2s">
                    <div class="mhq-event-box-items-2 mt-0">
                        <div class="mhq-image">
                            <img src="{{ asset('assets/img/inner/event/02.jpg') }}" alt="img">
                        </div>
                        <div class="mhq-content">
                            <h3><a href="{{ route('events.show', ['slug' => 'exciting-day-for-kids-and-parents']) }}">An
                                    exciting day for kids and parents to enjoy together.</a></h3>
                            <ul class="list-box">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    England
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    09 May, 2025
                                </li>
                            </ul>
                            <a href="{{ route('events.show', ['slug' => 'exciting-day-for-kids-and-parents']) }}"
                                class="theme-btn">
                                View Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".4s">
                    <div class="mhq-event-box-items-2 mt-0">
                        <div class="mhq-image">
                            <img src="{{ asset('assets/img/inner/event/03.jpg') }}" alt="img">
                        </div>
                        <div class="mhq-content">
                            <h3><a href="{{ route('events.show', ['slug' => 'dress-up-as-your-favorite-characters']) }}">Dress-Up
                                    as Your Favorite Characters</a></h3>
                            <ul class="list-box">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    England
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    09 May, 2025
                                </li>
                            </ul>
                            <a href="{{ route('events.show', ['slug' => 'dress-up-as-your-favorite-characters']) }}"
                                class="theme-btn">
                                View Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".6s">
                    <div class="mhq-event-box-items-2 mt-0">
                        <div class="mhq-image">
                            <img src="{{ asset('assets/img/inner/event/04.jpg') }}" alt="img">
                        </div>
                        <div class="mhq-content">
                            <h3><a href="{{ route('events.show', ['slug' => 'nature-play-and-outdoor-adventures']) }}">Nature
                                    Play and Outdoor Adventures</a></h3>
                            <ul class="list-box">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    England
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    09 May, 2025
                                </li>
                            </ul>
                            <a href="{{ route('events.show', ['slug' => 'nature-play-and-outdoor-adventures']) }}"
                                class="theme-btn">
                                View Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".8s">
                    <div class="mhq-event-box-items-2 mt-0">
                        <div class="mhq-image">
                            <img src="{{ asset('assets/img/inner/event/05.jpg') }}" alt="img">
                        </div>
                        <div class="mhq-content">
                            <h3><a href="{{ route('events.show', ['slug' => 'creative-learning-workshops']) }}">Nature Play
                                    and Outdoor Adventures</a></h3>
                            <ul class="list-box">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    England
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    09 May, 2025
                                </li>
                            </ul>
                            <a href="{{ route('events.show', ['slug' => 'creative-learning-workshops']) }}"
                                class="theme-btn">
                                View Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".9s">
                    <div class="mhq-event-box-items-2 mt-0">
                        <div class="mhq-image">
                            <img src="{{ asset('assets/img/inner/event/06.jpg') }}" alt="img">
                        </div>
                        <div class="mhq-content">
                            <h3><a href="{{ route('events.show', ['slug' => 'family-fun-day']) }}">Nature Play and Outdoor
                                    Adventures</a></h3>
                            <ul class="list-box">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    England
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    09 May, 2025
                                </li>
                            </ul>
                            <a href="{{ route('events.show', ['slug' => 'family-fun-day']) }}" class="theme-btn">
                                View Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="page-nav-wrap wow fadeInUp text-center" data-wow-delay=".3s">
                    <ul>
                        <li><a class="page-numbers" href="#"><i class="fa-solid fa-chevron-left"></i></a></li>
                        <li class="active"><a class="page-numbers" href="#">01</a></li>
                        <li><a class="page-numbers" href="#">02</a></li>
                        <li><a class="page-numbers" href="#">03</a></li>
                        <li><a class="page-numbers" href="#"><i class="fa-solid fa-chevron-right"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
