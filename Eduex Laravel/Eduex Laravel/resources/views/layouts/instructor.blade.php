<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<!--<< Header Area >>-->

<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="mhquickdev">
    <meta name="description" content="{{ $metaDescription ?? site_name() . ' - ' . site_tagline() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- ======== Page title ============ -->
    <title>{{ (!empty($pageTitle) && $pageTitle !== site_name()) ? $pageTitle . ' | ' . site_name() : site_name() . ' - ' . site_tagline() }}</title>

    @stack('meta')

    <!--<< Favcion >>-->
    @php($favicon = branding_asset(['favicon_path', 'logos.favicon', 'favicon'], 'assets/front/img/favicon.svg'))
    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="shortcut icon" href="{{ $favicon }}">

    <!--<< CSS Styles >>-->
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/meanmenu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/instructor/style.css') }}">

    {!! ToastMagic::styles() !!}

    @stack('styles')
    @include('partials.dynamic-styles')
</head>

<body>
    <!-- GT Back To Top Start -->
    <button id="back-top" class="back-to-top show">
        <i class="fa-regular fa-arrow-up"></i>
    </button>

    <!-- GT MouseCursor Start -->
    <div class="mouseCursor cursor-outer"></div>
    <div class="mouseCursor cursor-inner"></div>

    @include('partials.mobile-nav')
    @include('partials.header-top')
    @include('partials.header')

    @include('partials.impersonation-banner')

    @yield('breadcrumb')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @include('cookie-consent::index')

    <!--<< All JS Plugins >>-->
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
    <script src="{{ asset('assets/front/js/instructor/script.js') }}"></script>
    <script src="{{ asset('assets/front/js/sweetalert2@11.js') }}"></script>

    {!! ToastMagic::scripts() !!}

    @stack('scripts')
</body>

</html>
