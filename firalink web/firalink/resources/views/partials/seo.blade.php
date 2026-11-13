@php
    $seoService = app(\App\Services\SeoService::class);
    $canonical = $seoService->canonicalUrl($customCanonical ?? null);
    $isHome = request()->routeIs('home') || request()->path() === '/';

    // Route-based indexing guard
    $isPrivate = ($isPrivate ?? false) || request()->is('admin*', 'student*', 'instructor*', 'login*', 'register*', 'password*', 'checkout*', 'payment*', 'cron*') || http_response_code() === 404;

    // Title formulation (50-60 characters ideal for SERP)
    $siteBrand = config('seo.brand_name', 'Fira Link Academy');
    if ($isHome) {
        $finalTitle = 'Fira Link Academy | Learn Import & Export Business in Ethiopia';
    } elseif (!empty($pageTitle)) {
        $cleanTitle = trim($pageTitle);
        $finalTitle = str_contains($cleanTitle, $siteBrand) ? $cleanTitle : "{$cleanTitle} | {$siteBrand}";
    } else {
        $finalTitle = "{$siteBrand} | Learn Import & Export Business in Ethiopia";
    }

    // Description formulation (140-160 characters ideal)
    $finalDesc = !empty($metaDescription) 
        ? trim($metaDescription) 
        : config('seo.default_description', 'Master practical import-export, customs clearance, port logistics, and global trade in Ethiopia and East Africa with Fira Link Academy. Explore programs today.');

    // OG Image
    $ogImage = !empty($metaImage) ? $seoService->normalizeUrl($metaImage) : url(config('seo.default_og_image', '/og-image.png'));
    $ogType = $ogType ?? ($isHome ? 'website' : 'article');
@endphp

<!-- ========== Primary SEO Meta Tags ========== -->
<title>{{ $finalTitle }}</title>
<meta name="description" content="{{ $finalDesc }}">
<link rel="canonical" href="{{ $canonical }}">

@if($isPrivate)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@endif

<!-- ========== Brand & Mobile Identity ========== -->
<meta name="application-name" content="{{ $siteBrand }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteBrand }}">
<meta name="theme-color" content="{{ config('seo.primary_color', '#6D1A95') }}">
<meta name="msapplication-TileColor" content="{{ config('seo.primary_color', '#6D1A95') }}">

<!-- ========== Favicon & Touch Icons ========== -->
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="48x48 32x32 16x16">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icon-512.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">

<!-- ========== Open Graph / Facebook ========== -->
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="{{ $siteBrand }}">
<meta property="og:title" content="{{ $finalTitle }}">
<meta property="og:description" content="{{ $finalDesc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $finalTitle }}">
<meta property="og:locale" content="en_US">

<!-- ========== Twitter Cards ========== -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $finalTitle }}">
<meta name="twitter:description" content="{{ $finalDesc }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $finalTitle }}">

<!-- ========== Search Console Verification Hook ========== -->
@if($gsv = config('seo.google_site_verification'))
<meta name="google-site-verification" content="{{ $gsv }}">
@endif

<!-- ========== Core Web Vitals Preloads ========== -->
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style">
@if($isHome)
<link rel="preload" href="{{ asset('assets/front/img/logo/fira_header_logo.png') }}" as="image" type="image/png">
@endif

<!-- ========== Server-Rendered JSON-LD Structured Data ========== -->
@if($isHome)
    {{-- WebSite Schema (Controls brand name & search bar in Google) --}}
    <script type="application/ld+json">
        {!! json_encode($seoService->webSiteSchema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- EducationalOrganization Schema --}}
    <script type="application/ld+json">
        {!! json_encode($seoService->organizationSchema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Sitelinks Navigation Schema --}}
    <script type="application/ld+json">
        {!! json_encode($seoService->siteNavigationSchema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif

{{-- BreadcrumbList Schema for Inner Pages --}}
@if(!$isHome && !empty($breadcrumbs))
    <script type="application/ld+json">
        {!! json_encode($seoService->breadcrumbSchema($breadcrumbs), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif

{{-- Course Schema --}}
@if(isset($course) && $course instanceof \App\Models\Course)
    <script type="application/ld+json">
        {!! json_encode($seoService->courseSchema($course), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif

{{-- FAQPage Schema --}}
@if(!empty($schemaFaqs))
    <script type="application/ld+json">
        {!! json_encode($seoService->faqSchema($schemaFaqs), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif

{{-- Article Schema --}}
@if(isset($blogPost) && $blogPost instanceof \App\Models\BlogPost)
    <script type="application/ld+json">
        {!! json_encode($seoService->articleSchema($blogPost), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif

<!-- ========== Analytics / GA4 Hook (Deferred) ========== -->
@if($gaId = config('seo.ga_measurement_id'))
<script>
    window.addEventListener('load', function() {
        var script = document.createElement('script');
        script.src = 'https://www.googletagmanager.com/gtag/js?id={{ $gaId }}';
        script.async = true;
        document.head.appendChild(script);

        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}', { 'anonymize_ip': true });
    });
</script>
@endif
