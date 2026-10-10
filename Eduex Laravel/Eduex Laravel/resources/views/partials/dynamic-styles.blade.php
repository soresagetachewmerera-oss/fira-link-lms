@php
    $primaryColor = branding_settings('primary_color', '#6777ef');
    $secondaryColor = branding_settings('secondary_color', '#f59e0b');
@endphp
<style>
    :root {
        --theme: {{ $primaryColor }};
        --theme-2: {{ $secondaryColor }};
        --v2-primary: {{ $primaryColor }};
        --v2-secondary: {{ $secondaryColor }};
    }

    /* Admin Overrides */
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
