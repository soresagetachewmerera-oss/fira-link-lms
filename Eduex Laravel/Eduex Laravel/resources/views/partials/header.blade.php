        <!-- Header Section Start -->
        <header id="header-sticky" class="header-1">
            <div class="logo-shape">
                <img src="{{ asset('assets/front/img/inner/logo-shape.png') }}" alt="img">
            </div>
            <div class="container-fluid">
                <div class="mega-menu-wrapper">
                    <div class="header-main">
                        <div class="logo">
                            <?php $siteName = site_name(); ?>
                            <a href="{{ route('home') }}" class="header-logo">
                                <img src="{{ branding_asset(['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'], 'assets/front/img/logo/black-logo.svg') }}"
                                    alt="{{ $siteName }} logo">
                            </a>
                            <a href="{{ route('home') }}" class="header-logo2">
                                <img src="{{ branding_asset(['primary_logo_path', 'logos.primary'], 'assets/front/img/logo/white-logo.svg') }}"
                                    alt="{{ $siteName }} logo">
                            </a>
                        </div>
                        <div class="header-right-items">
                            <div class="mean__menu-wrapper">
                                <div class="main-menu">
                                    <nav id="mobile-menu">
                                        <ul>
                                            <?php $mainMenu = $frontendMenus['main-navigation'] ?? []; ?>
                                            @if (!empty($mainMenu))
                                                @include('partials.menu-items', [
                                                    'items' => $mainMenu,
                                                    'isRoot' => true,
                                                ])
                                            @else
                                                <li><a href="{{ route('home') }}">{{ __('frontend.home') }}</a></li>
                                            @endif
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <div class="header-right d-flex justify-content-end align-items-center">
                                <a href="#" class="main-header__search search-toggler">
                                    <i class="fa-regular fa-magnifying-glass"></i>
                                </a>

                                @if(Auth::check())
                                    <?php $currentUser = Auth::user(); ?>
                                    @include('partials.notification-dropdown', ['currentUser' => $currentUser])

                                    @php
                                        $userRole = 'guest';
                                        if($currentUser->isAdmin()) $userRole = 'admin';
                                        elseif($currentUser->isInstructor()) $userRole = 'instructor';
                                        elseif($currentUser->isStudent()) $userRole = 'student';
                                        
                                        $dashboardRoute = route('home');
                                        $dashboardText = __('frontend.dashboard');
                                        
                                        if($userRole === 'admin') { 
                                            $dashboardRoute = route('admin.dashboard'); 
                                            $dashboardText = __('frontend.admin_dashboard'); 
                                        } elseif($userRole === 'instructor') { 
                                            $dashboardRoute = route('instructor.dashboard'); 
                                            $dashboardText = __('frontend.instructor_dashboard'); 
                                        } elseif($userRole === 'student') { 
                                            $dashboardRoute = route('student.dashboard'); 
                                            $dashboardText = __('frontend.my_dashboard'); 
                                        }
                                    @endphp

                                    <a href="{{ $dashboardRoute }}" class="theme-btn">
                                        {{ $dashboardText }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="theme-btn">
                                        {{ __('frontend.login') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                @endif

                                <div class="header__hamburger d-xl-none my-auto">
                                    <div class="sidebar__toggle">
                                        <i class="fas fa-bars"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @include('partials.search')
