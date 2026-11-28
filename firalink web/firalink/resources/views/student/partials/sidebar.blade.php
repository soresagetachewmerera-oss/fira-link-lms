<!-- Sidebar Start -->
@php
    $user = $user ?? Auth::user();
    $org = $user?->primaryOrganization();

    // Determine dynamic professional role title
    if ($user?->isAdmin()) {
        $roleTitle = 'System Administrator';
    } elseif ($user?->isVerifiedExporter()) {
        $roleTitle = 'Verified Exporter';
    } elseif ($user?->isVerifiedBuyer()) {
        $roleTitle = 'Verified Coffee Buyer';
    } elseif ($user?->isVerifiedSupplier()) {
        $roleTitle = 'Coffee Supplier';
    } elseif ($user?->isVerifiedBroker()) {
        $roleTitle = 'Coffee Broker';
    } elseif ($user?->sellerProfile) {
        $roleTitle = 'Coffee Trader';
    } elseif ($user?->isInstructor()) {
        $roleTitle = 'Academy Instructor';
    } elseif ($org && $org->name) {
        $roleTitle = \Illuminate\Support\Str::limit($org->name, 22);
    } else {
        $rawRole = $user?->role ?? 'student';
        $roleTitle = match($rawRole) {
            'student' => 'Trading & Academy Member',
            'trader' => 'Coffee Trader',
            'exporter' => 'Coffee Exporter',
            'buyer' => 'Coffee Buyer',
            default => ucfirst($rawRole),
        };
    }

    $isVerified = $user?->isVerifiedSeller() || ($org && $org->hasActiveBadge());
@endphp
<aside class="dashboard-sidebar">
    <div class="dashboard-profile">
        <div class="dashboard-profile-image">
            @if ($user && $user->hasProfilePhoto())
                <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}">
            @else
                <div class="d-flex align-items-center justify-content-center text-white fw-bold fs-4 shadow-sm" style="width: 80px; height: 80px; background: linear-gradient(135deg, #6D1A95, #3B0764); border-radius: 50%; margin: 0 auto;">
                    {{ $user ? $user->initials : 'FL' }}
                </div>
            @endif
        </div>
        @if ($user && !$user->hasProfilePhoto())
            <div class="text-center mb-2">
                <a href="{{ route('student.settings') }}" class="badge rounded-pill text-decoration-none" style="background: #F3E8FF; color: #6D1A95; font-size: 11px; padding: 4px 10px; border: 1px solid #D8B4FE;">
                    <i class="fa-solid fa-camera me-1"></i> Add Photo
                </a>
            </div>
        @endif
        <h4>{{ $user->name ?? 'Account' }}</h4>
        <p class="user-role-label">{{ $roleTitle }}</p>
        @if ($isVerified)
            <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25);">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Verified Entity
            </span>
        @else
            <span class="status-badge">
                <i class="fa-regular fa-circle-check" aria-hidden="true"></i> {{ __('student.active') }}
            </span>
        @endif
    </div>

    <!-- Responsive Mobile Toggle Button -->
    <button class="dashboard-menu-toggle" type="button" aria-label="Toggle dashboard menu">
        <i class="fa-solid fa-bars"></i>
        <span>Dashboard Navigation</span>
    </button>

    <nav class="dashboard-nav-wrapper">
        <!-- 1. OVERVIEW -->
        <div class="dashboard-nav-section">
            <div class="dashboard-nav-section-title">Overview</div>
            <ul class="dashboard-nav">
                <li>
                    <a href="{{ route('student.dashboard') }}"
                        class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
                        title="Overview Dashboard">
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('marketplace.dashboard') }}"
                        class="{{ request()->routeIs('marketplace.dashboard') ? 'active' : '' }}"
                        title="Marketplace Trade Desk">
                        <i class="fa-solid fa-shop"></i>
                        <span>Trade Desk</span>
                        <span class="badge rounded-pill ms-auto" style="background: #F3E8FF; color: #6D1A95; font-size: 10px; padding: 2px 6px; font-weight: 600;">B2B</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('market-prices.index') }}"
                        class="{{ request()->routeIs('market-prices.*') ? 'active' : '' }}"
                        title="Live Real-time Coffee Market Prices">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Market Prices</span>
                        <span class="badge rounded-pill ms-auto" style="background: rgba(16, 185, 129, 0.12); color: #059669; font-size: 10px; padding: 2px 6px; font-weight: 600;">LIVE</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 2. COFFEE TRADE & TOOLS -->
        <div class="dashboard-nav-section">
            <div class="dashboard-nav-section-title">Coffee Trade &amp; Tools</div>
            <ul class="dashboard-nav">
                <li>
                    <a href="{{ route('student.coffee-toolkit.index') }}"
                        class="{{ request()->routeIs('student.coffee-toolkit.*') ? 'active' : '' }}"
                        title="Coffee Export Price Calculator, Offer Sheets & Readiness">
                        <i class="fa-solid fa-mug-hot"></i>
                        <span>Coffee Toolkit</span>
                        <span class="badge rounded-pill ms-auto" style="background: #6D1A95; color: #fff; font-size: 10px; padding: 2px 7px;">PRO</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('marketplace.verification.center') }}"
                        class="{{ request()->routeIs('marketplace.verification.*') ? 'active' : '' }}"
                        title="Legal Documents & ECTA Verification Vault">
                        <i class="fa-solid fa-shield-check"></i>
                        <span>Verification Vault</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.ai-chat.index') }}"
                        class="{{ request()->routeIs('student.ai-chat.*') ? 'active' : '' }}"
                        title="Fira AI Trade Advisor & Document OCR">
                        <i class="fa-solid fa-robot"></i>
                        <span>Fira AI Advisor</span>
                        <span class="badge rounded-pill ms-auto" style="background: #3B0764; color: #F3E8FF; font-size: 10px; padding: 2px 7px;">AI</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 3. ACADEMY & TRAINING -->
        <div class="dashboard-nav-section">
            <div class="dashboard-nav-section-title">Academy &amp; Training</div>
            <ul class="dashboard-nav">
                <li>
                    <a href="{{ route('student.my-courses') }}"
                        class="{{ request()->routeIs('student.my-courses') ? 'active' : '' }}"
                        title="Enrolled Training Courses">
                        <i class="fa-solid fa-book-open"></i>
                        <span>{{ __('student.my_courses') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.live-classes.index') }}"
                        class="{{ request()->routeIs('student.live-classes.*') ? 'active' : '' }}"
                        title="Live Interactive Masterclasses">
                        <i class="fa-solid fa-video"></i>
                        <span>Live Masterclasses</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.certificates') }}"
                        class="{{ request()->routeIs('student.certificates') ? 'active' : '' }}"
                        title="Verified Certificates of Completion">
                        <i class="fa-solid fa-certificate"></i>
                        <span>{{ __('student.certificates') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.assignments') }}"
                        class="{{ request()->routeIs('student.assignments') || request()->routeIs('student.quiz-attempts') ? 'active' : '' }}"
                        title="Assignments & Quizzes">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>Assignments &amp; Quizzes</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 4. NETWORK & EVENTS -->
        <div class="dashboard-nav-section">
            <div class="dashboard-nav-section-title">Network &amp; Events</div>
            <ul class="dashboard-nav">
                <li>
                    <a href="{{ route('student.community.index') }}"
                        class="{{ request()->routeIs('student.community.*') ? 'active' : '' }}"
                        title="Community Forum & Exporter Discussions">
                        <i class="fa-solid fa-users"></i>
                        <span>{{ __('student.community') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.events.bookings') }}"
                        class="{{ request()->routeIs('student.events.bookings') ? 'active' : '' }}"
                        title="Cuppings, Trade Tours & Event Bookings">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>{{ __('student.my_event_bookings') }}</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 5. ACCOUNT & SETTINGS -->
        <div class="dashboard-nav-section">
            <div class="dashboard-nav-section-title">Account &amp; Settings</div>
            <ul class="dashboard-nav">
                <li>
                    <a href="{{ route('student.messages.index') }}"
                        class="{{ request()->routeIs('student.messages.*') ? 'active' : '' }}"
                        title="Direct Messages & Trade Inquiries">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Messages</span>
                        <span class="badge bg-danger rounded-pill ms-auto student-chat-badge" style="display: none; font-size: 10px;"></span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.notifications.index') }}"
                        class="{{ request()->routeIs('student.notifications.*') ? 'active' : '' }}"
                        title="System Notifications & Alerts">
                        <i class="fa-solid fa-bell"></i>
                        <span>{{ __('student.notifications') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.payments') }}"
                        class="{{ request()->routeIs('student.payments') ? 'active' : '' }}"
                        title="Invoices & Payment History">
                        <i class="fa-solid fa-receipt"></i>
                        <span>{{ __('student.payment_history') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('student.settings') }}"
                        class="{{ request()->routeIs('student.settings') ? 'active' : '' }}"
                        title="Profile & Security Settings">
                        <i class="fa-solid fa-gear"></i>
                        <span>{{ __('student.settings') }}</span>
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" id="sidebarUnifiedLogoutForm" style="display: none;">
                        @csrf
                    </form>
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('sidebarUnifiedLogoutForm').submit();"
                        class="text-danger"
                        title="Sign out of your account">
                        <i class="fa-solid fa-arrow-right-from-bracket" style="color: #ef4444 !important;"></i>
                        <span style="color: #ef4444;">{{ __('student.logout') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
<!-- Sidebar End -->
