@if(!request()->routeIs('free-tools*'))
    @include('partials.marquee')
@endif
<!-- Footer Section Start -->
<footer class="mhq-footer-section fix footer-bg-2 mt-0">
    <div class="container">
        <div class="mhq-footer-wrapper style-2">
            @if(request()->routeIs('free-tools*'))
                <!-- Simplified 3-Column Clean Footer for Tools Platform -->
                <div class="row g-4 justify-content-between">
                    <div class="col-lg-5 col-md-12">
                        <div class="mhq-footer-widget-items">
                            <div class="mhq-widget-head">
                                @php($siteName = site_name())
                                <a href="{{ route('home') }}" class="footer-logo">
                                    <img src="{{ branding_asset(['footer_logo_path', 'dark_logo_path', 'primary_logo_path', 'logos.footer', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}"
                                        alt="{{ $siteName }} logo">
                                </a>
                            </div>
                            <div class="mhq-footer-content">
                                <p style="max-width: 420px; font-size: 13.5px; line-height: 1.6; color: #94A3B8;">
                                    {{ $footerBranding['about_text'] ?? 'Fira Link Business Solution is Ethiopia’s premier import-export training and business development hub, dedicated to building global trade capabilities and empowering entrepreneurs.' }}
                                </p>
                                @if (!empty($footerSocialLinks))
                                    <div class="social-icon mt-3">
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

                    <div class="col-lg-3 col-md-6">
                        <div class="mhq-footer-widget-items">
                            <div class="mhq-widget-head">
                                <h3 style="font-size: 16px; font-weight: 700;">Trade Resources</h3>
                            </div>
                            <ul class="list-items" style="font-size: 13.5px;">
                                <li><a href="{{ route('free-tools.index') }}">Coffee Export Terminal</a></li>
                                <li><a href="{{ url('/courses') }}">Specialty Export Courses</a></li>
                                <li><a href="{{ route('verify-certificate') }}">Verify Certificates</a></li>
                                <li><a href="{{ url('/contact') }}">Contact Advisory Team</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="mhq-footer-widget-items">
                            <div class="mhq-widget-head">
                                <h3 style="font-size: 16px; font-weight: 700;">Official Inquiries</h3>
                            </div>
                            <ul class="contact-info" style="font-size: 13.5px; line-height: 1.8;">
                                @if (!empty($footerContact['address']))
                                    <li><i class="fa-solid fa-location-dot me-2 text-muted"></i>{{ $footerContact['address'] }}</li>
                                @endif
                                @if (!empty($footerContact['email']))
                                    <li>
                                        <i class="fa-solid fa-envelope me-2 text-muted"></i><a href="mailto:{{ $footerContact['email'] }}" class="link">{{ $footerContact['email'] }}</a>
                                    </li>
                                @endif
                                @if (!empty($footerContact['phone']) && !str_contains($footerContact['phone'], '123456'))
                                    <li>
                                        <i class="fa-solid fa-phone me-2 text-muted"></i><a href="tel:{{ preg_replace('/[^\d\+]/', '', $footerContact['phone']) }}">{{ $footerContact['phone'] }}</a>
                                    </li>
                                @endif
                                @if (!empty($footerContact['hours']))
                                    <li><i class="fa-solid fa-clock me-2 text-muted"></i><span class="text-muted">{{ __('frontend.hours') }}:</span> {{ $footerContact['hours'] }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            @else
                <div class="row justify-content-between">
                    <div class="col-xl-3 col-lg-4 col-lg-4 col-md-8 wow fadeInUp" data-wow-delay=".2s">
                        <div class="mhq-footer-widget-items">
                            <div class="mhq-widget-head">
                                <a href="{{ route('home') }}" class="footer-logo">
                                    <img src="{{ asset('assets/front/img/logo/fira_header_logo.png') }}"
                                        alt="Fira Link Academy - Fira Link Business Solution" width="140" height="45">
                                </a>
                            </div>
                            <div class="mhq-footer-content">
                                <p>
                                    Fira Link Academy is the digital learning and trade capacity initiative of Fira Link Business Solution, providing practical import-export, customs tariff compliance, and logistics training in Addis Ababa, Ethiopia.
                                </p>
                                <div class="social-icon">
                                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                                    <a href="https://t.me/firalink" target="_blank" rel="noopener noreferrer" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
                                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-4 wow fadeInUp" data-wow-delay=".4s">
                        <div class="mhq-footer-widget-items ml-40">
                            <div class="mhq-widget-head">
                                <h3>Programs &amp; Tools</h3>
                            </div>
                            <ul class="list-items">
                                <li><a href="{{ route('programs') }}">Programs</a></li>
                                <li><a href="{{ route('courses') }}">Courses</a></li>
                                <li><a href="{{ route('free-tools.index') }}">Free Tools</a></li>
                                <li><a href="{{ route('pricing') }}">Pricing</a></li>
                                <li><a href="{{ route('verify-certificate') }}">Verify Certificate</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 ps-lg-5 wow fadeInUp" data-wow-delay=".6s">
                        <div class="mhq-footer-widget-items">
                            <div class="mhq-widget-head">
                                <h3>Company &amp; Legal</h3>
                            </div>
                            <ul class="list-items">
                                <li><a href="{{ route('about') }}">About</a></li>
                                <li><a href="{{ route('blog.index') }}">Blog</a></li>
                                <li><a href="{{ route('faq') }}">FAQ</a></li>
                                <li><a href="{{ route('contact') }}">Contact</a></li>
                                <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                                <li><a href="{{ route('terms') }}">Terms of Service</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp" data-wow-delay=".8s">
                        <div class="mhq-footer-widget-items">
                            <div class="mhq-widget-head">
                                <h3>{{ __('frontend.contact_us') }}</h3>
                            </div>
                            <ul class="contact-info">
                                <li>Bole Sub-City, Addis Ababa, Ethiopia</li>
                                <li>
                                    <a href="mailto:info@firalink.com" class="link">info@firalink.com</a>
                                </li>
                                <li>
                                    <a href="tel:+251911123456">+251 911 123456</a>
                                </li>
                                <li><span class="text-muted">Hours:</span> Mon - Sat: 8:30am - 5:30pm (EAT)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="mhq-footer-bottom style-2 footer-bg-2 wow fadeInUp" data-wow-delay=".9s">
        <div class="container">
            <div class="mhq-footer-bottom-wrapper d-flex justify-content-between align-items-center flex-wrap gap-2">
                <p class="text-color-2 mb-0">
                    &copy; {{ date('Y') }} Fira Link Academy. All rights reserved. A digital learning platform of Fira Link Business Solution.
                </p>
                <div class="footer-legal-links">
                    <a href="{{ route('privacy') }}" class="text-muted me-3">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="text-muted">Terms of Service</a>
                </div>
            </div>
        </div>
    </div>
    {{-- <div class="footer-name wow img-custom-anim-left">
        <h2>
            {{ $siteName }}
        </h2>
    </div> --}}
</footer>
