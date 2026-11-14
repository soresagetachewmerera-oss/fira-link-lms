@php
    $siteName = site_name();
    $dashboardUrl = Auth::check()
        ? (Auth::user()->isAdmin()
            ? route('admin.dashboard')
            : (Auth::user()->isInstructor() ? route('instructor.dashboard') : route('student.dashboard')))
        : route('login');
@endphp

<div class="fix-area">
    <aside id="fira-mobile-panel" class="offcanvas__info fira-mobile-panel" aria-label="Mobile navigation" aria-hidden="true">
        <div class="offcanvas__wrapper">
            <div class="offcanvas__content">
                <div class="fira-mobile-top">
                    <a href="{{ route('home') }}" class="fira-mobile-logo" aria-label="Fira Link Academy home">
                        <img src="{{ asset('assets/front/img/logo/fira_header_logo.png') }}" alt="{{ $siteName }}" width="130" height="42">
                    </a>
                    <div class="offcanvas__close">
                        <button type="button" aria-label="Close navigation menu">
                            <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <nav class="fira-mobile-nav" aria-label="Mobile navigation">
                    <ul>
                        <li><a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a></li>
                        <li><a href="{{ route('courses') }}" @if(request()->routeIs('courses*')) aria-current="page" @endif>Courses</a></li>
                        <li class="fira-mobile-dropdown">
                            <button type="button" aria-expanded="false" aria-controls="mobile-marketplace-menu">
                                <span>Coffee B2B</span><i class="fa-regular fa-angle-down" aria-hidden="true"></i>
                            </button>
                            <ul id="mobile-marketplace-menu" class="fira-mobile-submenu">
                                <li><a href="{{ route('marketplace.index') }}">Browse Coffee Lots</a></li>
                                <li><a href="{{ route('marketplace.suppliers') }}">Exporters &amp; Suppliers</a></li>
                                <li><a href="{{ route('marketplace.pricing') }}">Pricing &amp; Plans</a></li>
                                <li><a href="{{ route('marketplace.dashboard') }}">My Marketplace</a></li>
                            </ul>
                        </li>
                        <li><a href="{{ route('market-prices.index') }}" @if(request()->routeIs('market-prices*')) aria-current="page" @endif>Market Prices</a></li>
                        <li><a href="{{ route('tours.index') }}" @if(request()->routeIs('tours*')) aria-current="page" @endif>Tours</a></li>
                        <li><a href="{{ route('events.index') }}" @if(request()->routeIs('events*')) aria-current="page" @endif>Events</a></li>
                        <li><a href="{{ route('free-tools.index') }}" @if(request()->routeIs('free-tools*')) aria-current="page" @endif>Free Tools</a></li>
                        <li><a href="{{ route('contact') }}" @if(request()->routeIs('contact*')) aria-current="page" @endif>Contact</a></li>
                    </ul>
                </nav>

                <div class="fira-mobile-actions">
                    <a href="{{ route('courses') }}" class="fira-mobile-primary">Explore Training <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i></a>
                    <a href="{{ $dashboardUrl }}" class="fira-mobile-secondary">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        {{ Auth::check() ? 'My Dashboard' : 'Log in' }}
                    </a>
                </div>
            </div>
        </div>
    </aside>
</div>
<div class="offcanvas__overlay" aria-hidden="true"></div>
