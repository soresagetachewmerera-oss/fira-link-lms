@php($mobileMenu = $frontendMenus['mobile-navigation'] ?? ($frontendMenus['main-navigation'] ?? []))
@php($siteName = site_name())
@php($mobileLogo = branding_asset(['dark_logo_path', 'primary_logo_path', 'logos.secondary', 'logos.primary'], 'assets/front/img/logo/black-logo.svg'))
<!-- Offcanvas Area Start -->
<div class="fix-area">
    <div class="offcanvas__info">
        <div class="offcanvas__wrapper">
            <div class="offcanvas__content">
                <div class="offcanvas__top mb-5 d-flex justify-content-between align-items-center">
                    <div class="offcanvas__logo">
                        <a href="{{ route('home') }}">
                            <img src="{{ $mobileLogo }}" alt="{{ $siteName }} logo">
                        </a>
                    </div>
                    <div class="offcanvas__close">
                        <button>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <h3 class="offcanvas-title">{{ __('frontend.welcome') }}</h3>
                <p>{{ $footerBranding['tagline'] ?? 'Empowering businesses with trade and learning intelligence.' }}</p>

                <nav class="mobile-menu fix mt-3">
                    <ul class="d-none" data-fallback-menu>
                        @foreach ($mobileMenu as $item)
                            <li><a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] ?? 'Menu Item' }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                @if (!empty($footerSocialLinks))
                    <div class="social-icon d-flex align-items-center">
                        @foreach ($footerSocialLinks as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener">
                                <i class="{{ $social['icon'] ?? 'fab fa-link' }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="offcanvas__contact">
                    <h3>{{ __('frontend.information') }}</h3>
                    <ul class="contact-list">
                        @if (!empty($footerContact['address']))
                            <li><span>{{ __('frontend.address') }}:</span> {{ $footerContact['address'] }}</li>
                        @endif
                        @if (!empty($footerContact['phone']))
                            <li><span>{{ __('frontend.call_us') }}:</span> <a
                                    href="tel:{{ preg_replace('/[^\d\+]/', '', $footerContact['phone']) }}">{{ $footerContact['phone'] }}</a>
                            </li>
                        @endif
                        @if (!empty($footerContact['email']))
                            <li><span>{{ __('frontend.email_label') }}:</span> <a
                                    href="mailto:{{ $footerContact['email'] }}">{{ $footerContact['email'] }}</a></li>
                        @endif
                    </ul>
                </div>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="theme-btn">
                            {{ __('frontend.admin_dashboard') }}
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                    @elseif(auth()->user()->isInstructor())
                        <a href="{{ route('instructor.dashboard') }}" class="theme-btn">
                            {{ __('frontend.instructor_dashboard') }}
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                    @elseif(auth()->user()->isStudent())
                        <a href="{{ route('student.dashboard') }}" class="theme-btn">
                            {{ __('frontend.my_dashboard') }}
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                    @else
                        <a href="{{ route('home') }}" class="theme-btn">
                            {{ __('frontend.dashboard') }}
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="theme-btn">
                        {{ __('frontend.login') }}
                        <i class="fa-solid fa-arrow-up-right"></i>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>
<div class="offcanvas__overlay"></div>
