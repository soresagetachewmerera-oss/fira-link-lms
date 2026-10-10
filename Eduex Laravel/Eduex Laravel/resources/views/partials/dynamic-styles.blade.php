@php
    $primaryColor = branding_settings('primary_color', '#6D1A95');
    $secondaryColor = branding_settings('secondary_color', '#50126f');
@endphp
<style>
    /* ==========================================================================
       FIRA LINK LMS — PROFESSIONAL TYPOGRAPHY & DESIGN SYSTEM
       ========================================================================== */
    :root {
        --theme: {{ $primaryColor }};
        --theme-2: {{ $secondaryColor }};
        --v2-primary: {{ $primaryColor }};
        --v2-secondary: {{ $secondaryColor }};

        /* Typography Font Tokens */
        --font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        --font-family-heading: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

        /* Standardized Type Scale */
        --text-xs: 0.75rem;     /* 12px */
        --text-sm: 0.8125rem;   /* 13px */
        --text-base: 0.9375rem; /* 15px */
        --text-md: 1rem;        /* 16px */
        --text-lg: 1.125rem;    /* 18px */
        --text-xl: 1.25rem;     /* 20px */
        --text-2xl: 1.5rem;     /* 24px */
        --text-3xl: 1.75rem;    /* 28px */
        --text-4xl: 2.125rem;   /* 34px */
    }

    /* Global Body Typography */
    body, html, input, button, select, textarea {
        font-family: var(--font-family-base) !important;
        font-size: 15px;
        line-height: 1.6;
        color: #334155;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Professional Headings Hierarchy */
    h1, .h1 {
        font-family: var(--font-family-heading) !important;
        font-size: 34px !important;
        font-weight: 700 !important;
        line-height: 1.25 !important;
        letter-spacing: -0.025em !important;
        color: #0f172a !important;
    }

    h2, .h2 {
        font-family: var(--font-family-heading) !important;
        font-size: 26px !important;
        font-weight: 700 !important;
        line-height: 1.3 !important;
        letter-spacing: -0.02em !important;
        color: #0f172a !important;
    }

    h3, .h3 {
        font-family: var(--font-family-heading) !important;
        font-size: 20px !important;
        font-weight: 600 !important;
        line-height: 1.35 !important;
        letter-spacing: -0.015em !important;
        color: #0f172a !important;
    }

    h4, .h4 {
        font-family: var(--font-family-heading) !important;
        font-size: 17px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        letter-spacing: -0.01em !important;
        color: #0f172a !important;
    }

    h5, .h5 {
        font-family: var(--font-family-heading) !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        color: #0f172a !important;
    }

    h6, .h6 {
        font-family: var(--font-family-heading) !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        color: #334155 !important;
    }

    /* Hero Section Refinement */
    .mhq-hero-1 .mhq-hero-content h1,
    .mhq-hero-2 .mhq-hero-content h1 {
        font-size: 40px !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        letter-spacing: -0.03em !important;
        color: #0f172a !important;
        margin-top: 12px !important;
        margin-bottom: 14px !important;
    }

    .mhq-hero-1 .mhq-hero-content span,
    .mhq-hero-2 .mhq-hero-content span {
        font-size: 12px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
        padding: 5px 14px !important;
        border-radius: 20px !important;
    }

    .mhq-hero-1 .mhq-hero-content p,
    .mhq-hero-2 .mhq-hero-content p {
        font-size: 15.5px !important;
        line-height: 1.65 !important;
        color: #475569 !important;
    }

    /* Breadcrumbs & Page Headers */
    .breadcrumb-wrapper .page-heading h1 {
        font-size: 30px !important;
        font-weight: 700 !important;
        line-height: 1.25 !important;
        letter-spacing: -0.02em !important;
    }

    .breadcrumb-wrapper .page-heading .breadcrumb-items {
        margin-top: 14px !important;
        gap: 8px !important;
    }

    .breadcrumb-wrapper .page-heading .breadcrumb-items li {
        font-size: 13px !important;
        font-weight: 500 !important;
    }

    /* Section Headings & Badges */
    .section-title {
        margin-bottom: 24px !important;
    }

    .section-title h6 {
        font-size: 12px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
        padding: 5px 14px !important;
        border-radius: 20px !important;
        margin-bottom: 10px !important;
        line-height: 1.2 !important;
    }

    .section-title h2 {
        font-size: 26px !important;
        font-weight: 700 !important;
        line-height: 1.3 !important;
        letter-spacing: -0.02em !important;
    }

    .section-title p {
        font-size: 14.5px !important;
        color: #64748b !important;
        line-height: 1.6 !important;
    }

    /* Course Cards (Catalog & Homepage) — Compact & Balanced */
    .mhq-courses-box-items {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        overflow: hidden !important;
    }

    .mhq-courses-box-items:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06), 0 10px 20px -3px rgba(0,0,0,0.08) !important;
    }

    .mhq-courses-box-items .mhq-courses-content {
        padding: 18px 20px !important;
    }

    .mhq-courses-box-items .mhq-courses-content h3,
    .mhq-courses-box-items .mhq-courses-content h3 a {
        font-size: 15.5px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        letter-spacing: -0.01em !important;
        color: #0f172a !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        min-height: 43px !important;
        max-height: 43px !important;
        margin-top: 6px !important;
        margin-bottom: 6px !important;
    }

    .mhq-courses-box-items .mhq-courses-content h3 a:hover {
        color: var(--theme) !important;
    }

    .mhq-courses-box-items .mhq-courses-content p {
        font-size: 13px !important;
        line-height: 1.5 !important;
        color: #64748b !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        min-height: 39px !important;
        max-height: 39px !important;
        margin-bottom: 8px !important;
    }

    .mhq-courses-box-items .mhq-courses-content .post-date {
        margin-top: 10px !important;
        margin-bottom: 12px !important;
        padding-bottom: 10px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        gap: 16px !important;
    }

    .mhq-courses-box-items .mhq-courses-content .post-date li {
        font-size: 12.5px !important;
        color: #64748b !important;
        font-weight: 500 !important;
        gap: 6px !important;
    }

    .mhq-courses-box-items .mhq-courses-content .post-date li .icon {
        width: 26px !important;
        height: 26px !important;
        line-height: 26px !important;
        font-size: 11px !important;
        background: #f8fafc !important;
        color: var(--theme) !important;
    }

    /* Course Card Price & Instructor Row */
    .mhq-courses-box-items .mhq-courses-content .client-info-area {
        margin-top: 4px !important;
        align-items: center !important;
    }

    .mhq-courses-box-items .mhq-courses-content .client-info-area .client-info {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #334155 !important;
        gap: 8px !important;
    }

    .mhq-courses-box-items .mhq-courses-content .client-info-area .client-info .img img {
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
    }

    .mhq-courses-box-items .mhq-courses-content .client-info-area h2 {
        font-size: 17px !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        color: var(--theme) !important;
        margin: 0 !important;
        letter-spacing: -0.01em !important;
    }

    .mhq-courses-image .post-box {
        font-size: 11px !important;
        padding: 4px 10px !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        letter-spacing: 0.03em !important;
        text-transform: uppercase !important;
    }

    /* Course Details Page Typography */
    .mhq-courses-details-area .mhq-courses-details-wrapper .mhq-left-content h3 {
        font-size: 24px !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
        letter-spacing: -0.02em !important;
        color: #0f172a !important;
        margin-top: 14px !important;
        margin-bottom: 12px !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .mhq-left-content .list-date {
        gap: 16px !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .mhq-left-content .list-date li {
        font-size: 13px !important;
        font-weight: 500 !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .mhq-left-content .list-date li h6 {
        font-size: 12px !important;
        padding: 4px 12px !important;
        border-radius: 6px !important;
        font-weight: 600 !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .courses-details-content .nav {
        margin-bottom: 30px !important;
        padding: 6px 16px !important;
        gap: 32px !important;
        border-radius: 10px !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .courses-details-content .nav .nav-item .nav-link {
        font-size: 14px !important;
        font-weight: 600 !important;
        padding: 8px 12px !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .courses-details-content .description-content h3,
    .mhq-courses-details-area .mhq-courses-details-wrapper .courses-details-content .course-curriculum-items h3 {
        font-size: 18px !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em !important;
        margin-bottom: 16px !important;
        color: #0f172a !important;
    }

    .mhq-courses-details-area .mhq-courses-details-wrapper .courses-details-content .description-content p,
    .mhq-courses-details-area .mhq-courses-details-wrapper .courses-details-content .description-content .list-item li {
        font-size: 14.5px !important;
        line-height: 1.65 !important;
        color: #334155 !important;
    }

    /* Course Sidebar Widget & Pricing */
    .course-details-info li,
    .widget-course-info li {
        font-size: 13.5px !important;
        padding: 10px 0 !important;
    }

    .theme-btn.bg-color-4 {
        font-size: 16px !important;
        font-weight: 700 !important;
        padding: 10px 18px !important;
        border-radius: 8px !important;
        letter-spacing: -0.01em !important;
    }

    /* Buttons & Interactive Elements */
    .theme-btn, .btn-primary, .btn-secondary {
        font-size: 13.5px !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em !important;
        border-radius: 8px !important;
    }

    .theme-btn {
        padding: 12px 24px !important;
    }

    .badge {
        font-size: 11.5px !important;
        font-weight: 600 !important;
        letter-spacing: 0.02em !important;
        padding: 4px 8px !important;
    }

    /* Dashboard & Admin Typography Balancing */
    .card-title {
        font-size: 16px !important;
        font-weight: 600 !important;
        color: #0f172a !important;
    }

    .card-header h4 {
        font-size: 16px !important;
        font-weight: 600 !important;
    }

    .table th {
        font-size: 11.5px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.04em !important;
        color: #64748b !important;
    }

    .table td {
        font-size: 13.5px !important;
        color: #334155 !important;
    }

    /* Forms & Inputs */
    .form-label, label {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #334155 !important;
        margin-bottom: 6px !important;
    }

    .form-control, .form-select {
        font-size: 14px !important;
        line-height: 1.5 !important;
        border-radius: 8px !important;
    }

    /* Responsive Type Adjustments */
    @media (max-width: 991px) {
        h1, .h1 { font-size: 28px !important; }
        h2, .h2 { font-size: 22px !important; }
        .mhq-hero-1 .mhq-hero-content h1,
        .mhq-hero-2 .mhq-hero-content h1 { font-size: 30px !important; }
        .breadcrumb-wrapper .page-heading h1 { font-size: 24px !important; }
    }

    @media (max-width: 575px) {
        h1, .h1 { font-size: 24px !important; }
        h2, .h2 { font-size: 20px !important; }
        .mhq-hero-1 .mhq-hero-content h1,
        .mhq-hero-2 .mhq-hero-content h1 { font-size: 24px !important; }
        .breadcrumb-wrapper .page-heading h1 { font-size: 20px !important; }
        .mhq-courses-box-items .mhq-courses-content h3,
        .mhq-courses-box-items .mhq-courses-content h3 a { font-size: 14.5px !important; }
        .mhq-courses-box-items .mhq-courses-content .client-info-area h2 { font-size: 16px !important; }
    }

    /* Admin & Primary Overrides */
    .bg-primary { background-color: {{ $primaryColor }} !important; }
    .text-primary, .text-primary-all *, .text-primary-all *:before, .text-primary-all *:after { color: {{ $primaryColor }} !important; }
    .btn-primary { background-color: {{ $primaryColor }} !important; border-color: {{ $primaryColor }} !important; }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active { background-color: {{ $secondaryColor }} !important; border-color: {{ $secondaryColor }} !important; }
    
    .nav-pills .nav-item .nav-link.active {
        background-color: {{ $primaryColor }};
    }
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: {{ $primaryColor }} !important;
        border-color: {{ $primaryColor }} !important;
    }
    .page-item.active .page-link {
        background-color: {{ $primaryColor }};
        border-color: {{ $primaryColor }};
    }
    .main-sidebar .sidebar-menu li.active a {
        color: {{ $primaryColor }};
    }
    .main-sidebar .sidebar-menu li.active a i {
        color: {{ $primaryColor }};
    }
    .main-sidebar .sidebar-menu li ul.dropdown-menu li.active > a {
        color: {{ $primaryColor }};
    }
    .main-sidebar .sidebar-menu li a:hover {
        color: {{ $primaryColor }};
    }
</style>
