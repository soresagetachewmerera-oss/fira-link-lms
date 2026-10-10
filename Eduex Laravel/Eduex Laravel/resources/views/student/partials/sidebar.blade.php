<!-- Sidebar Start -->
@php($user = $user ?? Auth::user())
<aside class="dashboard-sidebar">
    <div class="dashboard-profile">
        <div class="dashboard-profile-image">
            @if ($user->profile_photo)
                <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}">
            @else
                <img src="{{ asset('assets/front/img/home-1/courses/client-01.png') }}" alt="{{ $user->name }}">
            @endif
        </div>
        <h4>{{ $user->name }}</h4>
        <p>{{ __('student.student') }}</p>
        <span class="status-badge">✓ {{ __('student.active') }}</span>
    </div>

    <button class="dashboard-menu-toggle" type="button" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
        <span>{{ __('student.menu') }}</span>
    </button>

    <nav class="dashboard-nav-wrapper">
        <ul class="dashboard-nav">
            <li>
                <a href="{{ route('student.dashboard') }}"
                    class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge"></i>
                    <span>{{ __('student.dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.community.index') }}"
                    class="{{ request()->routeIs('student.community.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>{{ __('student.community') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.notifications.index') }}"
                    class="{{ request()->routeIs('student.notifications.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bell"></i>
                    <span>{{ __('student.notifications') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.ai-chat.index') }}"
                    class="{{ request()->routeIs('student.ai-chat.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-robot"></i>
                    <span>{{ __('student.ai_assistant') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.messages.index') }}"
                    class="{{ request()->routeIs('student.messages.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge bg-danger rounded-pill ms-auto student-chat-badge" style="display: none; font-size: 10px;"></span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.my-courses') }}"
                    class="{{ request()->routeIs('student.my-courses') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open"></i>
                    <span>{{ __('student.my_courses') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.assignments') }}"
                    class="{{ request()->routeIs('student.assignments') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>{{ __('student.my_assignments') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.quiz-attempts') }}"
                    class="{{ request()->routeIs('student.quiz-attempts') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-question"></i>
                    <span>{{ __('student.my_quiz_attempts') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.events.bookings') }}"
                    class="{{ request()->routeIs('student.events.bookings') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>{{ __('student.my_event_bookings') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.live-classes.index') }}"
                    class="{{ request()->routeIs('student.live-classes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-video"></i>
                    <span>Live Classes</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.certificates') }}"
                    class="{{ request()->routeIs('student.certificates') ? 'active' : '' }}">
                    <i class="fa-solid fa-certificate"></i>
                    <span>{{ __('student.certificates') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.payments') }}"
                    class="{{ request()->routeIs('student.payments') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i>
                    <span>{{ __('student.payment_history') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.subscription') }}"
                    class="{{ request()->routeIs('student.subscription') ? 'active' : '' }}">
                    <i class="fa-solid fa-id-card"></i>
                    <span>{{ __('student.my_subscription') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('student.settings') }}"
                    class="{{ request()->routeIs('student.settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    <span>{{ __('student.settings') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('logout') }}" class="">

                    <i class="fa-solid fa-arrow-up-right"></i>{{ __('student.logout') }}
                </a>
            </li>
        </ul>
    </nav>
</aside>
<!-- Sidebar End -->