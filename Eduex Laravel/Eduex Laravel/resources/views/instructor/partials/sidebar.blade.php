<!-- Sidebar Start -->
@php $user = $user ?? auth()->user(); @endphp
<aside class="dashboard-sidebar">
    <div class="dashboard-profile">
        <div class="dashboard-profile-image">
            <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}">
        </div>
        <h4>{{ $user->name }}</h4>
        <p>{{ __('instructor.senior_instructor') }}</p>
        <span class="status-badge">✓ {{ __('instructor.verified') }}</span>
    </div>

    <button class="dashboard-menu-toggle" type="button" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
        <span>{{ __('instructor.menu') }}</span>
    </button>

    <nav class="dashboard-nav-wrapper">
        <ul class="dashboard-nav">
            <li>
                <a href="{{ route('instructor.dashboard') }}"
                    class="{{ request()->routeIs('instructor.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge"></i>
                    <span>{{ __('instructor.dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.courses') }}"
                    class="{{ request()->routeIs('instructor.courses') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open"></i>
                    <span>{{ __('instructor.my_courses') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.bundles.index') }}"
                    class="{{ request()->routeIs('instructor.bundles.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Bundles</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.assignments.index') }}"
                    class="{{ request()->routeIs('instructor.assignments*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>{{ __('instructor.assignments') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.quizzes.index') }}"
                    class="{{ request()->routeIs('instructor.quizzes*') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-question"></i>
                    <span>{{ __('instructor.quiz_attempts') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.events.index') }}"
                    class="{{ request()->routeIs('instructor.events*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>{{ __('instructor.events') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.live-classes.index') }}"
                    class="{{ request()->routeIs('instructor.live-classes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-video"></i>
                    <span>Live Classes</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.students') }}"
                    class="{{ request()->routeIs('instructor.students*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>{{ __('instructor.students') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.messages.index') }}"
                    class="{{ request()->routeIs('instructor.messages.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge bg-danger rounded-pill ms-auto instructor-chat-badge" style="display: none; font-size: 10px;"></span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.analytics') }}"
                    class="{{ request()->routeIs('instructor.analytics') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>{{ __('instructor.analytics') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.reviews') }}"
                    class="{{ request()->routeIs('instructor.reviews') ? 'active' : '' }}">
                    <i class="fa-solid fa-star"></i>
                    <span>{{ __('instructor.reviews') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.discussions.index') }}"
                    class="{{ request()->routeIs('instructor.discussions.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments"></i>
                    <span>Course Forums</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.earnings') }}"
                    class="{{ request()->routeIs('instructor.earnings') || request()->routeIs('instructor.withdrawals') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet"></i>
                    <span>{{ __('instructor.earnings') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.subscription-sales') }}"
                    class="{{ request()->routeIs('instructor.subscription-sales') ? 'active' : '' }}">
                    <i class="fa-solid fa-repeat"></i>
                    <span>Subscription Sales</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.coupons.index') }}"
                    class="{{ request()->routeIs('instructor.coupons.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-tags"></i>
                    <span>Coupons</span>
                </a>
            </li>
            <li>
                <a href="{{ route('instructor.settings') }}"
                    class="{{ request()->routeIs('instructor.settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    <span>{{ __('instructor.settings') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('logout') }}" class="">

                    <i class="fa-solid fa-arrow-up-right"></i>{{ __('instructor.logout') }}
                </a>
            </li>
        </ul>
    </nav>
</aside>
<!-- Sidebar End -->
