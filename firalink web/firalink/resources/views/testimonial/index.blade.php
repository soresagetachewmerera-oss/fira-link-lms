@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? 'Testimonials',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-testimonial-section-2 mt-0 mb-0 style-inner section-padding section-bg">
        <div class="container">
            <div class="section-title text-center">
                <h6 class="wow fadeInUp">Testimonials</h6>
                <h2 class="wow fadeInUp" data-wow-delay=".2s">What Our Learners Are Saying</h2>
                @if ($averageRating)
                    <p class="wow fadeInUp" data-wow-delay=".3s">Average rating {{ number_format($averageRating, 1) }} out of
                        5 based on recent student reviews.</p>
                @endif
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-2 d-none d-xl-block">
                    <div class="array-button">
                        <button class="array-prev"><i class="fa-solid fa-chevron-left"></i></button>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="swiper-container fix testimonial-slider-content">
                        <div class="swiper-wrapper">
                            @forelse($reviews as $review)
                                <div class="swiper-slide">
                                    <div class="mhq-testimonial-box-2">
                                        <div class="star">
                                            @for ($i = 0; $i < 5; $i++)
                                                <i
                                                    class="fa-solid fa-star-sharp {{ $i < $review->rating ? 'text-warning' : '' }}"></i>
                                            @endfor
                                        </div>
                                        <h3>“{{ Str::limit($review->comment, 250) }}”</h3>
                                        <div class="mhq-info mt-4 d-flex align-items-center">
                                            <img src="{{ $review->user && $review->user->profile_photo ? asset('storage/' . $review->user->profile_photo) : asset('assets/front/img/home-2/testimonial/client-01.png') }}"
                                                alt="{{ $review->user->name ?? 'Student' }}" class="mr-3 rounded-circle"
                                                width="60" height="60" style="object-fit: cover;">
                                            <div>
                                                <h5 class="mb-0">{{ $review->user->name ?? 'Student' }}</h5>
                                                <small class="text-muted">{{ $review->course->title ?? 'Course' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="swiper-slide">
                                    <div class="mhq-testimonial-box-2">
                                        <h3>We do not have testimonials yet, but we are working hard to collect success
                                            stories from our learners.</h3>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @if ($reviews->count())
                        <div class="swiper-container testimonial-thumbs mx-auto">
                            <div class="swiper-wrapper">
                                @foreach ($reviews as $review)
                                    <div class="swiper-slide">
                                        <img src="{{ $review->user && $review->user->profile_photo ? asset('storage/' . $review->user->profile_photo) : asset('assets/front/img/home-2/testimonial/client-01.png') }}"
                                            alt="{{ $review->user->name ?? 'Student' }}">
                                        <div class="mhq-info">
                                            <h3>{{ $review->user->name ?? 'Student' }}</h3>
                                            <span>{{ $review->course->title ?? 'Course' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-lg-2 d-none d-xl-block">
                    <div class="array-button justify-content-end">
                        <button class="array-next"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
