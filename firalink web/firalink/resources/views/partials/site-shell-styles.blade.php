{{-- Styles for the shared site header and mobile menu (partials.header, partials.mobile-nav).
     Every layout that includes those partials must include this one, or the header renders as a plain list. --}}
<link rel="stylesheet" href="{{ asset('assets/front/css/header-premium.css') }}?v={{ file_exists(public_path('assets/front/css/header-premium.css')) ? filemtime(public_path('assets/front/css/header-premium.css')) : '1.0.0' }}">
