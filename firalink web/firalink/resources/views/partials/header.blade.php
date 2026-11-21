@php
    $dashboardUrl = Auth::check()
        ? (Auth::user()->isAdmin()
            ? route('admin.dashboard')
            : (Auth::user()->isInstructor() ? route('instructor.dashboard') : route('student.dashboard')))
        : route('login');
    $marketplaceActive = request()->routeIs('marketplace*');
    $currentLocale = app()->getLocale();
@endphp

<header id="header-sticky" class="fira-site-header" data-site-header>
    <div class="fira-header-container">
        <div class="fira-header-bar">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="fira-header-logo" aria-label="Fira Link Platform home">
                <img src="{{ asset('assets/front/img/logo/fira_header_logo.png') }}"
                     alt="Fira Link Platform"
                     width="130"
                     height="42">
            </a>

            <!-- Desktop Primary Navigation -->
            <nav class="fira-desktop-nav" aria-label="Primary navigation">
                <ul class="fira-nav-list">
                    <!-- Dropdown: Coffee B2B -->
                    <li class="fira-nav-dropdown">
                        <button class="fira-nav-link fira-dropdown-trigger {{ $marketplaceActive ? 'is-active' : '' }}"
                                type="button"
                                aria-expanded="false"
                                aria-controls="marketplace-menu">
                            Coffee B2B
                            <svg class="fira-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div id="marketplace-menu" class="fira-dropdown-menu">
                            <div class="fira-dropdown-heading">
                                <span class="fira-dropdown-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                                </span>
                                <div>
                                    <strong>Coffee B2B Marketplace</strong>
                                    <small>Direct trade &amp; verified micro-lots</small>
                                </div>
                            </div>
                            <ul>
                                <li><a href="{{ route('marketplace.index') }}"><span>Browse Coffee Lots</span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></a></li>
                                <li><a href="{{ route('marketplace.suppliers') }}"><span>Exporters &amp; Suppliers</span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></a></li>
                                <li><a href="{{ route('marketplace.pricing') }}"><span>Pricing &amp; Plans</span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></a></li>
                                <li><a href="{{ route('marketplace.dashboard') }}"><span>My Marketplace</span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></a></li>
                            </ul>
                        </div>
                    </li>

                    <li><a href="{{ route('market-prices.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('market-prices*')]) @if(request()->routeIs('market-prices*')) aria-current="page" @endif>Market Prices</a></li>
                    <li><a href="{{ route('courses') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('courses*')]) @if(request()->routeIs('courses*')) aria-current="page" @endif>Courses</a></li>
                    <li><a href="{{ route('tours.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('tours*')]) @if(request()->routeIs('tours*')) aria-current="page" @endif>Tours</a></li>
                    <li><a href="{{ route('events.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('events*')]) @if(request()->routeIs('events*')) aria-current="page" @endif>Events</a></li>
                    <li><a href="{{ route('free-tools.index') }}" @class(['fira-nav-link', 'is-active' => request()->routeIs('free-tools*')]) @if(request()->routeIs('free-tools*')) aria-current="page" @endif>Free Tools</a></li>
                </ul>
            </nav>

            <!-- Actions (Language Switcher & Auth) -->
            <div class="fira-header-actions">
                <!-- Clean Language Switcher -->
                <div class="fira-lang-switcher">
                    @if($currentLocale === 'am')
                        <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="fira-lang-btn" title="Switch to English" aria-label="Switch language to English">EN</a>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(['lang' => 'am']) }}" class="fira-lang-btn" title="ወደ አማርኛ ቀይር" aria-label="ቋንቋውን ወደ አማርኛ ቀይር">አማ</a>
                    @endif
                </div>

                @auth
                    <!-- Signed-in User Menu Dropdown -->
                    <div class="fira-user-menu-dropdown">
                        <button type="button" class="fira-user-profile-btn" aria-expanded="false" aria-haspopup="true">
                            <span class="fira-user-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                            <span class="fira-user-name">{{ Str::limit(Auth::user()->name ?? 'Account', 11) }}</span>
                            <svg class="fira-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="fira-user-dropdown-panel">
                            <div class="fira-user-dropdown-header">
                                <strong>{{ Auth::user()->name }}</strong>
                                <span style="font-size: 11px; color: var(--fira-header-brand); font-weight: 600; text-transform: uppercase;">
                                    {{ ucfirst(Auth::user()->role ?? (Auth::user()->isAdmin() ? 'Admin' : (Auth::user()->isInstructor() ? 'Instructor' : 'Trader'))) }}
                                </span>
                            </div>
                            <ul class="fira-user-dropdown-links">
                                <li>
                                    <a href="{{ $dashboardUrl }}">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('marketplace.dashboard') }}">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                                        My Marketplace
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('student.my-courses') }}">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                        My Courses
                                    </a>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="fira-dropdown-logout-btn">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                            Sign out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @else
                    <!-- Signed-Out Actions: Secondary Outline "Sign in" + ONE Primary "Explore lots" -->
                    <a href="{{ route('login') }}" class="fira-header-btn-secondary">Sign in</a>
                    <a href="{{ route('marketplace.index') }}" class="fira-header-btn-primary">Explore lots</a>
                @endauth

                <!-- Mobile Menu Toggle Button -->
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
