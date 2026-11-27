@php
    $siteName = site_name();
    $dashboardUrl = Auth::check()
        ? (Auth::user()->isAdmin()
            ? route('admin.dashboard')
            : (Auth::user()->isInstructor() ? route('instructor.dashboard') : route('student.dashboard')))
        : route('login');
    $currentLocale = app()->getLocale();
@endphp

<div class="fix-area">
    <aside id="fira-mobile-panel" class="offcanvas__info fira-mobile-panel" aria-label="Mobile navigation" aria-hidden="true">
        <div class="offcanvas__wrapper">
            <div class="offcanvas__content">
                <!-- Top Brand & Close -->
                <div class="fira-mobile-top">
                    <a href="{{ route('home') }}" class="fira-mobile-logo" aria-label="Fira Link Platform home">
                        <img src="{{ asset('assets/front/img/logo/fira_header_logo.png') }}" alt="{{ $siteName }}" width="124" height="40">
                    </a>
                    <div class="offcanvas__close">
                        <button type="button" aria-label="Close navigation menu">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                </div>

                @auth
                    <!-- Signed-in user mobile info card -->
                    <div class="fira-mobile-user-card">
                        <div class="fira-user-avatar" style="width: 36px; height: 36px; font-size: 14px;">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div style="flex-grow: 1; min-width: 0;">
                            <strong style="display: block; font-size: 14px; color: var(--fira-header-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ Auth::user()->name }}</strong>
                            <span style="font-size: 11px; color: var(--fira-header-brand); font-weight: 600; text-transform: uppercase;">
                                {{ ucfirst(Auth::user()->role ?? 'Trader') }}
                            </span>
                        </div>
                    </div>
                @endauth

                <!-- Mobile Nav Links -->
                <nav class="fira-mobile-nav" aria-label="Mobile navigation">
                    <ul>
                        <li><a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a></li>
                        <li class="fira-mobile-dropdown">
                            <button type="button" aria-expanded="false" aria-controls="mobile-marketplace-menu">
                                <span>Coffee B2B Marketplace</span>
                                <svg class="fira-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <ul id="mobile-marketplace-menu" class="fira-mobile-submenu">
                                <li><a href="{{ route('marketplace.index') }}">Browse Coffee Lots</a></li>
                                <li><a href="{{ route('marketplace.suppliers') }}">Exporters &amp; Suppliers</a></li>
                                <li><a href="{{ route('marketplace.pricing') }}">Pricing &amp; Plans</a></li>
                                <li><a href="{{ route('marketplace.dashboard') }}">Trade Desk (Lots &amp; Quotes)</a></li>
                            </ul>
                        </li>
                        <li><a href="{{ route('market-prices.index') }}" @if(request()->routeIs('market-prices*')) aria-current="page" @endif>Market Prices</a></li>
                        <li><a href="{{ route('courses') }}" @if(request()->routeIs('courses*')) aria-current="page" @endif>Courses</a></li>
                        <li><a href="{{ route('tours.index') }}" @if(request()->routeIs('tours*')) aria-current="page" @endif>Tours</a></li>
                        <li><a href="{{ route('events.index') }}" @if(request()->routeIs('events*')) aria-current="page" @endif>Events</a></li>
                        <li><a href="{{ route('free-tools.index') }}" @if(request()->routeIs('free-tools*')) aria-current="page" @endif>Free Tools</a></li>
                        <li><a href="{{ route('contact') }}" @if(request()->routeIs('contact*')) aria-current="page" @endif>Contact</a></li>
                    </ul>

                    <!-- Language Switcher in Mobile Drawer -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 4px 0; margin-top: 12px; border-top: 1px solid var(--fira-header-line);">
                        <span style="font-size: 13.5px; font-weight: 600; color: var(--fira-header-muted);">Language / ቋንቋ:</span>
                        <div style="display: flex; gap: 8px;">
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}"
                               style="padding: 4px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; border: 1px solid {{ $currentLocale === 'en' ? 'var(--fira-header-brand)' : 'var(--fira-header-line)' }}; background: {{ $currentLocale === 'en' ? 'var(--fira-header-brand)' : '#FFFFFF' }}; color: {{ $currentLocale === 'en' ? '#FFFFFF' : 'var(--fira-header-ink)' }};">
                                English
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'am']) }}"
                               style="padding: 4px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; border: 1px solid {{ $currentLocale === 'am' ? 'var(--fira-header-brand)' : 'var(--fira-header-line)' }}; background: {{ $currentLocale === 'am' ? 'var(--fira-header-brand)' : '#FFFFFF' }}; color: {{ $currentLocale === 'am' ? '#FFFFFF' : 'var(--fira-header-ink)' }};">
                                አማርኛ
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- Action CTAs -->
                <div class="fira-mobile-actions">
                    <a href="{{ route('marketplace.index') }}" class="fira-btn fira-btn-primary">
                        Explore Coffee Lots
                    </a>
                    @auth
                        <a href="{{ $dashboardUrl }}" class="fira-btn fira-btn-secondary">
                            My Account Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="fira-btn fira-btn-destructive" style="width: 100%;">
                                Sign Out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="fira-btn fira-btn-secondary">
                            Sign in to Account
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </aside>
</div>
<div class="offcanvas__overlay" aria-hidden="true"></div>
