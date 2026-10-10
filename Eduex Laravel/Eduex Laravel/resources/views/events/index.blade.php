@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.events'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-event-section-2 section-padding fix">
        <div class="container">
            @if ($events->count() === 0)
                <div class="row">
                    <div class="col-12">
                        <div class="dashboard-empty-state text-center py-5 card-box"
                            style="background: transparent; border: 2px dashed rgba(0,0,0,0.05);">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <h3>{{ __('frontend.no_events_yet') }}</h3>
                            <p>{{ __('frontend.no_events_desc') }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($events as $event)
                        <div class="col-xl-6 col-lg-6 col-md-6 wow fadeInUp"
                            data-wow-delay="{{ $loop->iteration % 2 === 0 ? '.2s' : '' }}">
                            <div class="mhq-event-box-items-2 mt-0">
                                <div class="mhq-image">
                                    @php
                                        $fallbackImages = [
                                            asset('assets/front/img/inner/event/01.jpg'),
                                            asset('assets/front/img/inner/event/02.jpg'),
                                            asset('assets/front/img/inner/event/03.jpg'),
                                            asset('assets/front/img/inner/event/04.jpg'),
                                        ];
                                        $image = $event->featured_image
                                            ? asset('storage/' . $event->featured_image)
                                            : $fallbackImages[$loop->index % count($fallbackImages)];
                                    @endphp
                                    <img src="{{ $image }}" alt="{{ $event->title }}">
                                </div>
                                <div class="mhq-content">
                                    <h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h3>
                                    <ul class="list-box">
                                        <li>
                                            <i class="fa-solid fa-location-dot"></i>
                                            {{ $event->location ?? __('frontend.to_be_announced') }}
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-clock"></i>
                                            {{ optional($event->start_date)->format('M d, Y') ?? __('frontend.date_tbd') }}
                                        </li>
                                    </ul>
                                    <a href="{{ route('events.show', $event->slug) }}" class="theme-btn">
                                        {{ __('frontend.view_details') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </section>

@endsection
