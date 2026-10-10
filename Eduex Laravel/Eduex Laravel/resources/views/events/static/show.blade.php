@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Event Details',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-event-details-section section-padding">
        <div class="container">
            <div class="mhq-event-details-area">
                <div class="mhq-image wow fadeInUp" data-wow-delay=".3s">
                    <img src="{{ asset('assets/img/inner/event-details/01.jpg') }}" alt="Event image">
                </div>
                <div class="mhq-event-details-wrapper">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="details-content">
                                <h2>Creative Learning Workshops</h2>
                                <div class="thumb wow fadeInUp" data-wow-delay=".2s">
                                    <img src="{{ asset('assets/img/inner/event-details/details-2.jpg') }}" alt="image">
                                </div>
                                <p class="mt-4">Join us for an immersive and interactive experience where young minds are
                                    encouraged to explore, create, and learn through hands-on activities. This event is
                                    designed to nurture creativity, boost problem-solving skills, and inspire a love for
                                    learning in a fun-filled environment.</p>

                                <h3 class="mt-5">Event Highlights</h3>
                                <ul class="offer-list mt-3">
                                    <li><i class="fa-solid fa-check"></i> Hands-on workshops tailored for different age
                                        groups.</li>
                                    <li><i class="fa-solid fa-check"></i> Art, science, and technology activity zones.</li>
                                    <li><i class="fa-solid fa-check"></i> Award ceremony for participants.</li>
                                </ul>

                                <h3 class="mt-5">Location &amp; Facilities</h3>
                                <div class="location mt-3">
                                    <ul>
                                        <li><i class="fa-solid fa-location-dot"></i> 221B Baker Street, London</li>
                                        <li><i class="fa-solid fa-clock"></i> 9:00 AM – 4:00 PM</li>
                                        <li><i class="fa-solid fa-phone"></i> +44 20 7946 0958</li>
                                    </ul>
                                </div>

                                <div class="map-items mt-4">
                                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2414.9212906525893!2d-1.8240063222832516!3d52.48947194126047!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4870bc9bf198c25d%3A0xbdfe2b4d5ed558c7!2sUK!5e0!3m2!1sen!2sbd!4v1642347223679!5m2!1sen!2sbd"
                                        width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="event-details-information sticky-style">
                                <ul class="information-list">
                                    <li>
                                        <span><i class="fa-solid fa-calendar-day"></i>Date</span>
                                        <div class="text">09 May, 2025</div>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-clock"></i>Time</span>
                                        <div class="text">9:00 AM – 4:00 PM</div>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-map-marker-alt"></i>Location</span>
                                        <div class="text">London, UK</div>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-phone"></i>Contact</span>
                                        <div class="text">+44 20 7946 0958</div>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-envelope"></i>Email</span>
                                        <div class="text">events@example.com</div>
                                    </li>
                                </ul>
                                <a href="{{ route('events.index') }}" class="theme-btn w-100">
                                    Back to Events
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>

                                <div class="coming-soon-timer mt-4">
                                    <h3 class="bg-1"><span>45</span> Days</h3>
                                    <h3 class="bg-2"><span>10</span> Hours</h3>
                                    <h3 class="bg-3"><span>24</span> Minutes</h3>
                                    <h3 class="bg-4"><span>58</span> Seconds</h3>
                                    <p>Until the event starts</p>
                                </div>

                                <div class="share-btn mt-4">
                                    <span>Share:</span>
                                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
