@php
    $dashboardUrl = Auth::check()
        ? (Auth::user()->isAdmin()
            ? route('admin.dashboard')
            : (Auth::user()->isInstructor() ? route('instructor.dashboard') : route('student.dashboard')))
        : route('login');
    $marketplaceActive = request()->routeIs('marketplace*');
@endphp

<header id="header-sticky" class="fira-site-header" data-site-header>
    <div class="fira-header-container">
        <div class="fira-header-bar">
            <a href="{{ route('home') }}" class="fira-header-logo" aria-label="Fira Link Academy home">
                <img src="{{ asset('assets/front/img/logo/fira_header_logo.png') }}"
                     alt="Fira Link Academy"
                     width="130"
                     height="42">
            </a>

            <nav class="fira-desktop-nav" aria-label="Primary navigation">
                <ul class="fira-nav-list">
                    <li><a href="{{ route('home') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif>Home</a></li>
                    <li><a href="{{ route('courses') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('courses*')]) @if(request()->routeIs('courses*')) aria-current="page" @endif>Courses</a></li>
                    <li class="fira-nav-dropdown">
                        <button class="fira-nav-link fira-dropdown-trigger {{ $marketplaceActive ? 'is-active' : '' }}"
                                type="button"
                                aria-expanded="false"
                                aria-controls="marketplace-menu">
                            Coffee B2B
                            <i class="fa-regular fa-angle-down" aria-hidden="true"></i>
                        </button>
                        <div id="marketplace-menu" class="fira-dropdown-menu">
                            <div class="fira-dropdown-heading">
                                <span class="fira-dropdown-icon"><i class="fa-regular fa-mug-hot" aria-hidden="true"></i></span>
                                <span><strong>Coffee marketplace</strong><small>Source and sell with confidence</small></span>
                            </div>
                            <ul>
                                <li><a href="{{ route('marketplace.index') }}"><span>Browse Coffee Lots</span><i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                                <li><a href="{{ route('marketplace.suppliers') }}"><span>Exporters &amp; Suppliers</span><i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                                <li><a href="{{ route('marketplace.rfqs.create') }}"><span>Request Coffee (RFQ)</span><i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                                <li><a href="{{ route('marketplace.rfqs') }}"><span>Sourcing Tenders</span><i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                                <li><a href="{{ route('marketplace.pricing') }}"><span>Pricing &amp; Plans</span><i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                                <li><a href="{{ route('marketplace.dashboard') }}"><span>My Marketplace</span><i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                            </ul>
                        </div>
                    </li>
                    <li><a href="{{ route('market-prices.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('market-prices*')]) @if(request()->routeIs('market-prices*')) aria-current="page" @endif>Market Prices</a></li>
                    <li><a href="{{ route('tours.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('tours*')]) @if(request()->routeIs('tours*')) aria-current="page" @endif>Tours</a></li>
                    <li><a href="{{ route('events.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('events*')]) @if(request()->routeIs('events*')) aria-current="page" @endif>Events</a></li>
                    <li><a href="{{ route('free-tools.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('free-tools*')]) @if(request()->routeIs('free-tools*')) aria-current="page" @endif>Free Tools</a></li>
                </ul>
            </nav>

            <div class="fira-header-actions">
                <button type="button" class="fira-header-icon search-toggler" aria-label="Search">
                    <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                </button>
                <a href="{{ $dashboardUrl }}" class="fira-header-icon" aria-label="{{ Auth::check() ? 'Open your account' : 'Log in to your account' }}">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                </a>
                <a href="{{ route('courses') }}" class="fira-header-cta">
                    Explore Training
                    <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                </a>
                <button type="button"
                        class="fira-menu-toggle sidebar__toggle"
                        aria-label="Open navigation menu"
                        aria-controls="fira-mobile-panel"
                        aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </div>
</header>
