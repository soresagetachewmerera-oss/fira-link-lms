@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.contact_us'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-contact-section section-padding fix">
        <div class="container">
            <div class="contact-wrapper">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mhq-contact-content">
                            <div class="section-title mb-0">
                                <h6 class="wow fadeInUp">{{ $heroSettings['eyebrow'] ?? __('frontend.need_any_help') }}</h6>
                                <h2 class="wow fadeInUp" data-wow-delay=".2s">{{ $heroSettings['heading'] ?? __('frontend.get_in_touch') }}</h2>
                            </div>
                            @if (!empty($heroSettings['description']))
                                <p class="contact-text wow fadeInUp">{{ $heroSettings['description'] }}</p>
                            @endif
                            @if ($contactMethods->isNotEmpty())
                                <ul class="wow fadeInUp" data-wow-delay=".4s">
                                    @foreach ($contactMethods as $method)
                                        <li>
                                            <div class="icon">
                                                <i class="{{ $method['icon'] }}"></i>
                                            </div>
                                            <div class="content">
                                                <h5>{{ $method['label'] }}</h5>
                                                @if (!empty($method['link']))
                                                    <a href="{{ $method['link'] }}">{{ $method['value'] }}</a>
                                                @else
                                                    <span>{{ $method['value'] }}</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mhq-contact-items">
                            <div class="contact-form-box">
                                @if (session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('contact.submit') }}" method="POST">
                                    @csrf
                                    <div class="row g-4 align-items-center">
                                        <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".3s">
                                            <div class="form-clt">
                                                <input type="text" name="name" id="name" placeholder="{{ __('frontend.your_name') }}" value="{{ old('name', optional(auth()->user())->name) }}" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".5s">
                                            <div class="form-clt">
                                                <input type="email" name="email" id="email" placeholder="{{ __('frontend.your_email') }}" value="{{ old('email', optional(auth()->user())->email) }}" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".6s">
                                            <div class="form-clt">
                                                <input type="text" name="phone" id="phone" placeholder="{{ __('frontend.phone_number') }}" value="{{ old('phone') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-delay=".7s">
                                            <div class="form-clt">
                                                <input type="text" name="subject" id="subject" placeholder="{{ __('frontend.subject') }}" value="{{ old('subject') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 wow fadeInUp" data-wow-delay=".8s">
                                            <div class="form-clt">
                                                <textarea name="message" id="message" placeholder="{{ __('frontend.message_here') }}" rows="4" required>{{ old('message') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 wow fadeInUp" data-wow-delay=".9s">
                                            <button type="submit" class="theme-btn">
                                                {{ $formSettings['submit_label'] ?? __('frontend.send_message') }}
                                                <i class="far fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                @if (!empty($formSettings['description']))
                                    <p class="mt-3 text-muted small">{{ $formSettings['description'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if (!empty($mapSettings['embed_url']))
        <div class="map-area">
            <iframe src="{{ $mapSettings['embed_url'] }}" style="border:0;" allowfullscreen loading="lazy"></iframe>
            @if (!empty($mapSettings['caption']))
                <p class="text-center text-muted mt-2">{{ $mapSettings['caption'] }}</p>
            @endif
        </div>
    @endif
@endsection
