@extends('layouts.app')

@php
    $pageTitle = $pageTitle ?? __('frontend.faqs');
    $faqItems = collect($faqItems ?? []);
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.faqs'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <!-- Faq Section Start -->
    <section class="mhq-faq-section-3 mt-0 section-padding section-bg">
        <div class="container">
            <div class="mhq-faq-wrapper-3">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay=".3s">
                        <div class="mhq-faq-images-items">
                            <div class="mhq-counter-item">
                                <h2><span class="count">25</span>+</h2>
                                <p>{{ __('frontend.year_of') }} <br> {{ __('frontend.experience') }}</p>
                            </div>
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-6 col-md-6 col-sm-6">
                                    <div class="mhq-image">
                                        <img src="{{ asset('assets/front/img/home-3/faq/faq-01.jpg') }}"
                                            alt="FAQ illustration">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-6">
                                    <div class="mhq-image-2">
                                        <img src="{{ asset('assets/front/img/home-3/faq/faq-02.jpg') }}"
                                            alt="FAQ illustration">
                                    </div>
                                    <div class="mhq-image-3">
                                        <img src="{{ asset('assets/front/img/home-3/faq/faq-03.jpg') }}"
                                            alt="FAQ illustration">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="faq-content">
                            <div class="section-title mb-0">
                                <h6 class="wow fadeInUp">{{ __('frontend.most_asked_question') }}</h6>
                                <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ __('frontend.helping_navigate') }}</h2>
                            </div>
                            <div class="faq-accordion mt-4">
                                @if ($faqItems->isNotEmpty())
                                    <div class="accordion" id="faqAccordion">
                                        @foreach ($faqItems as $index => $faq)
                                            @php
                                                $headingId = 'faqHeading' . $index;
                                                $collapseId = 'faqCollapse' . $index;
                                                $delay = 0.3 + $loop->iteration * 0.1;
                                                $number = str_pad($loop->iteration, 2, '0', STR_PAD_LEFT);
                                            @endphp
                                            <div class="accordion-item wow fadeInUp"
                                                data-wow-delay="{{ number_format($delay, 1) }}s">
                                                <h5 class="accordion-header" id="{{ $headingId }}">
                                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $collapseId }}"
                                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                        aria-controls="{{ $collapseId }}">
                                                        {{ $number }}. {{ $faq['question'] ?? __('frontend.untitled_question') }}
                                                    </button>
                                                </h5>
                                                <div id="{{ $collapseId }}"
                                                    class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                                    aria-labelledby="{{ $headingId }}" data-bs-parent="#faqAccordion">
                                                    <div class="accordion-body">
                                                        {!! $faq['answer'] ?? '' !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="card border-0 shadow-sm wow fadeInUp" data-wow-delay=".3s">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-info-circle fa-2x text-theme mb-3"></i>
                                            <h5 class="mb-2">{{ __('frontend.faq_coming_soon') }}</h5>
                                            <p class="mb-0 text-muted">{{ __('frontend.faq_coming_soon_desc') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
