<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title') - Admin Dashboard</title>
  <meta name="description" content="Admin Dashboard">
  @php($favicon = branding_asset(['favicon_path', 'logos.favicon', 'favicon'], 'assets/front/img/favicon.svg'))
  <link rel="icon" type="image/png" href="{{ $favicon }}">
  <link rel="shortcut icon" href="{{ $favicon }}">

  <!-- General CSS Files -->
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/fontawesome/css/all.min.css') }}">

  <!-- CSS Libraries -->
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/jqvmap/dist/jqvmap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/weather-icon/css/weather-icons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/weather-icon/css/weather-icons-wind.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/summernote/summernote-bs4.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/select2/dist/css/select2.min.css') }}">

  <!-- Template CSS -->
  <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/components.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/custom.css') }}">
  {!! ToastMagic::styles() !!}
  @stack('styles')

  @if(request()->routeIs('admin.dashboard'))
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style_v2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
      rel="stylesheet">
  @endif

  @include('partials.dynamic-styles')
</head>

<body>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <div class="navbar-bg"></div>
      <nav class="navbar navbar-expand-lg main-navbar justify-content-end">
        <div class="d-flex justify-content-between w-100">
          <ul class="navbar-nav navbar-left">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
          </ul>
          <ul class="navbar-nav navbar-right">
            <li><a href="{{ route('home') }}" class="nav-link" target="_blank"><i class="fas fa-globe"></i> Visit
                Website</a></li>
          </ul>
        </div>
      </nav>

      @yield('content')