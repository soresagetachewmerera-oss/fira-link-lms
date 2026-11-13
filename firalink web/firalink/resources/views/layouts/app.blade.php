<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<!--<< Header Area >>-->

<head>
    <!-- ========== Meta Tags & Production SEO ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')

    @stack('meta')
    <!--<< Fonts >>-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!--<< CSS Styles >>-->
    @if(active_theme() && request()->routeIs('home'))
        {!! theme_styles() !!}
        @stack('theme-styles')
    @else
        <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/all.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/magnific-popup.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/meanmenu.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/swiper-bundle.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/main.css') }}?v={{ file_exists(public_path('assets/front/css/main.css')) ? filemtime(public_path('assets/front/css/main.css')) : '2.0.1' }}">
        <link rel="stylesheet" href="{{ asset('assets/front/css/fira_pixel_perfect.css') }}?v={{ file_exists(public_path('assets/front/css/fira_pixel_perfect.css')) ? filemtime(public_path('assets/front/css/fira_pixel_perfect.css')) : '2.0.1' }}">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/front/css/header-premium.css') }}?v={{ file_exists(public_path('assets/front/css/header-premium.css')) ? filemtime(public_path('assets/front/css/header-premium.css')) : '1.0.0' }}">

    {!! ToastMagic::styles() !!}

    @if(request()->routeIs('marketplace.*'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @stack('styles')
    @include('partials.dynamic-styles')

    @if($customCss = custom_scripts('custom_css'))
        <style>{!! $customCss !!}</style>
    @endif

    @if($headerScript = custom_scripts('header_script'))
        {!! $headerScript !!}
    @endif
    <link rel="stylesheet" href="{{ asset('assets/front/css/fira-design-system.css') }}?v={{ file_exists(public_path('assets/front/css/fira-design-system.css')) ? filemtime(public_path('assets/front/css/fira-design-system.css')) : '1.0.0' }}">
</head>

<body class="{{ request()->routeIs('home') ? 'home-page' : '' }}">
    <!-- GT Back To Top Start -->
    <button id="back-top" class="back-to-top show">
        <i class="fa-regular fa-arrow-up"></i>
    </button>

    <!-- GT MouseCursor Start -->
    <div class="mouseCursor cursor-outer"></div>
    <div class="mouseCursor cursor-inner"></div>

    @if(active_theme() && request()->routeIs('home') && view()->exists("home." . active_theme() . ".partials.header"))
        @include("home." . active_theme() . ".partials.header")
        @include('partials.mobile-nav')
    @else
        @include('partials.mobile-nav')
        @if(!request()->routeIs('home'))
            @include('partials.header-top')
        @endif
        @include('partials.header')
    @endif

    @yield('breadcrumb')

    <main>
        @yield('content')
    </main>

    @if(active_theme() && request()->routeIs('home') && view()->exists("home." . active_theme() . ".partials.footer"))
        @include("home." . active_theme() . ".partials.footer")
    @else
        @include('partials.footer')
    @endif

    @include('partials.floating-ai')

    @if(!request()->routeIs('home'))
        @include('cookie-consent::index')
    @endif

    <!--<< All JS Plugins >>-->
    @if(active_theme() && request()->routeIs('home'))
        {!! theme_scripts() !!}
        @stack('theme-scripts')
    @else
        <script src="{{ asset('assets/front/js/jquery-3.7.1.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/viewport.jquery.js') }}"></script>
        <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.nice-select.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.waypoints.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.counterup.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/swiper-bundle.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.meanmenu.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/parallaxie.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.magnific-popup.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/wow.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/main.js') }}"></script>
        <script src="{{ asset('assets/front/js/sweetalert2@11.js') }}"></script>
    @endif
    <script src="{{ asset('assets/front/js/header-premium.js') }}?v={{ file_exists(public_path('assets/front/js/header-premium.js')) ? filemtime(public_path('assets/front/js/header-premium.js')) : '1.0.0' }}"></script>
    {!! ToastMagic::scripts() !!}

    @if($customJs = custom_scripts('custom_js'))
        <script>{!! $customJs !!}</script>
    @endif

    @if($footerScript = custom_scripts('footer_script'))
        {!! $footerScript !!}
    @endif

    @stack('scripts')
</body>

</html>
