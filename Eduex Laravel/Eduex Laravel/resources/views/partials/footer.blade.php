@include('partials.marquee')
<!-- Footer Section Start -->
<footer class="mhq-footer-section fix footer-bg-2 mt-0">
    <div class="container">
        <div class="mhq-footer-wrapper style-2">
            <div class="row justify-content-between">
                <div class="col-xl-3 col-lg-4 col-lg-4 col-md-8 wow fadeInUp" data-wow-delay=".2s">
                    <div class="mhq-footer-widget-items">
                        <div class="mhq-widget-head">
                            @php($siteName = site_name())
                            <a href="{{ route('home') }}" class="footer-logo">
                                <img src="{{ branding_asset(['footer_logo_path', 'dark_logo_path', 'primary_logo_path', 'logos.footer', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}"
                                    alt="{{ $siteName }} logo">
                            </a>
                        </div>
                        <div class="mhq-footer-content">
                            <p>
                                {{ $footerBranding['about_text'] ?? 'Discover quality courses, expert instructors, and a community that supports lifelong learning.' }}
                            </p>
                            @if (!empty($footerSocialLinks))
                                <div class="social-icon">
                                    @foreach ($footerSocialLinks as $social)
                                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener">
                                            <i class="{{ $social['icon'] ?? 'fab fa-link' }}"></i>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-lg-4 col-md-4 wow fadeInUp" data-wow-delay=".4s">
                    <div class="mhq-footer-widget-items ml-40">
                        <div class="mhq-widget-head">
                            <h3>{{ __('frontend.quick_links') }}</h3>
                        </div>
                        <ul class="list-items">
                            @php($quickLinks = $frontendMenus['quick-links'] ?? [])
                            @forelse($quickLinks as $link)
                                <li><a href="{{ $link['url'] }}"
                                        target="{{ $link['target'] }}">{{ $link['title'] }}</a></li>
                                @if (!empty($link['children']))
                                    @foreach ($link['children'] as $child)
                                        <li class="pl-3"><a href="{{ $child['url'] }}"
                                                target="{{ $child['target'] }}">{{ $child['title'] }}</a></li>
                                    @endforeach
                                @endif
                            @empty
                                <li><a href="{{ url('/courses') }}">{{ __('frontend.courses') }}</a></li>
                                <li><a href="{{ url('/blog') }}">{{ __('frontend.blog') }}</a></li>
                            @endforelse
                            <li><a href="{{ route('verify-certificate') }}">{{ __('frontend.verify_certificate') ?? 'Verify Certificate' }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 ps-lg-5 wow fadeInUp" data-wow-delay=".6s">
                    <div class="mhq-footer-widget-items">
                        <div class="mhq-widget-head">
                            <h3>{{ __('frontend.resources') }}</h3>
                        </div>
                        <ul class="list-items">
                            @php($footerLinks = $frontendMenus['footer-links'] ?? [])
                            @forelse($footerLinks as $link)
                                <li><a href="{{ $link['url'] }}"
                                        target="{{ $link['target'] }}">{{ $link['title'] }}</a></li>
                                @if (!empty($link['children']))
                                    @foreach ($link['children'] as $child)
                                        <li class="pl-3"><a href="{{ $child['url'] }}"
                                                target="{{ $child['target'] }}">{{ $child['title'] }}</a></li>
                                    @endforeach
                                @endif
                            @empty
                                <li><a href="{{ route('home') }}">{{ __('frontend.home') }}</a></li>
                                <li><a href="{{ url('/contact') }}">{{ __('frontend.contact') }}</a></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-lg-4 col-md-6 wow fadeInUp" data-wow-delay=".8s">
                    <div class="mhq-footer-widget-items">
                        <div class="mhq-widget-head">
                            <h3>{{ __('frontend.contact_us') }}</h3>
                        </div>
                        <ul class="contact-info">
                            @if (!empty($footerContact['address']))
                                <li>{{ $footerContact['address'] }}</li>
                            @endif
                            @if (!empty($footerContact['email']))
                                <li>
                                    <a href="mailto:{{ $footerContact['email'] }}"
                                        class="link">{{ $footerContact['email'] }}</a>
                                </li>
                            @endif
                            @if (!empty($footerContact['phone']))
                                <li>
                                    <a
                                        href="tel:{{ preg_replace('/[^\d\+]/', '', $footerContact['phone']) }}">{{ $footerContact['phone'] }}</a>
                                </li>
                            @endif
                            @if (!empty($footerContact['hours']))
                                <li><span class="text-muted">{{ __('frontend.hours') }}:</span> {{ $footerContact['hours'] }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mhq-footer-bottom style-2 footer-bg-2 wow fadeInUp" data-wow-delay=".9s">
        <div class="container">
            <div class="mhq-footer-bottom-wrapper justify-content-end">
                <p class="text-color-2">
                    {{ $footerBranding['copyright'] ?? '© ' . date('Y') . ' ' . site_name() . '. All rights reserved.' }}
                </p>
            </div>
        </div>
    </div>
    {{-- <div class="footer-name wow img-custom-anim-left">
        <h2>
            {{ $siteName }}
        </h2>
    </div> --}}
</footer>
