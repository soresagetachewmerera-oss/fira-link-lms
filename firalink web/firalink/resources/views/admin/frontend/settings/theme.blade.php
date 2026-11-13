@extends('layouts.admin')

@section('title', 'Homepage Themes')

@php
    $remainingSeconds = null;
    if ($activeTheme === 'eduna' && filter_var(env('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN)) {
        $activatedAt = \App\Models\FrontendSetting::getValue('homepage.eduna_activated_at', null);
        if ($activatedAt) {
            $remainingSeconds = max(0, 600 - (time() - (int)$activatedAt));
        }
    }
@endphp

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Frontend Manager</div>
        <div class="breadcrumb-item">Homepage Theme</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <!-- Theme Upload Card -->
        <div class="col-12 mb-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h4>Install New Theme</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.frontend.theme.upload') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row align-items-center">
                            <div class="col-md-8 col-lg-9">
                                <div class="form-group mb-md-0">
                                    <label class="font-weight-bold">Theme ZIP File</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('theme_zip') is-invalid @enderror" id="theme_zip" name="theme_zip" required>
                                        <label class="custom-file-label" for="theme_zip">Choose zip file...</label>
                                        @error('theme_zip')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">Upload a valid theme zip package containing `theme.json`, template files, and `assets/` directory.</small>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-3 text-right">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                    <i class="fas fa-upload mr-2"></i> Upload & Install
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Discovered Themes list -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Available Themes</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Default Fallback Theme Card -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border @if($activeTheme === null) border-success shadow-sm @else border-light @endif">
                                <div class="position-relative bg-light" style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                    @if(file_exists(public_path('assets/admin/img/default.png')))
                                        <img src="{{ asset('assets/admin/img/default.png') }}" class="w-100 h-100" style="object-fit: cover;" alt="Default Theme">
                                    @else
                                        <div class="text-center text-muted">
                                            <i class="fas fa-desktop fa-3x mb-2"></i>
                                            <p class="mb-0">Default Theme Preview</p>
                                        </div>
                                    @endif
                                    
                                    @if($activeTheme === null)
                                        <span class="badge badge-success position-absolute" style="top: 10px; right: 10px; font-size: 0.85rem; padding: 0.4em 0.8em;">
                                            <i class="fas fa-check-circle mr-1"></i> Active
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1">Default Built-in Theme</h5>
                                    <p class="text-muted small mb-2">By MHQuickDev</p>
                                    <p class="card-text text-secondary small flex-grow-1">
                                        The default standard homepage layout packaged with the EduEx platform. Uses default front assets.
                                    </p>
                                    
                                    <div class="mt-3">
                                        @if($activeTheme === null)
                                            <button class="btn btn-outline-success btn-block disabled" disabled>
                                                Active Theme
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.frontend.theme.update') }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    Activate Default Theme
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Discovered Themes -->
                        @foreach($themes as $slug => $theme)
                            @php
                                $isActive = ($activeTheme === $slug);
                                $variants = $theme['variants'] ?? [];
                                $hasMultipleVariants = count($variants) > 1;
                                $thumbnail = $theme['thumbnail'] ?? null;
                                
                                if ($thumbnail) {
                                    $thumbnailUrl = theme_asset($thumbnail, $slug);
                                } else {
                                    $thumbnailUrl = null;
                                }
                            @endphp
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border @if($isActive) border-success shadow-sm @else border-light @endif">
                                    <div class="position-relative bg-light d-flex align-items-center justify-content-center" style="height: 200px; overflow: hidden;">
                                        @if($thumbnailUrl)
                                            <img src="{{ $thumbnailUrl }}" class="w-100 h-100" style="object-fit: cover;" alt="{{ $theme['name'] }}" onerror="this.src='https://placehold.co/600x400?text={{ urlencode($theme['name']) }}'">
                                        @else
                                            <div class="text-center text-muted">
                                                <i class="fas fa-paint-brush fa-3x mb-2"></i>
                                                <p class="mb-0">{{ $theme['name'] }} Preview</p>
                                            </div>
                                        @endif

                                        @if(filter_var(env('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN) && $slug === 'eduna')
                                            <a href="https://codecanyon.net/item/eduna-eduex-lms-script-laravel-theme/64222922" target="_blank" class="badge badge-warning position-absolute addon-badge" style="top: 10px; left: 10px; font-size: 0.85rem; padding: 0.4em 0.8em; z-index: 10; color: #fff;">
                                                <i class="fas fa-shopping-cart mr-1"></i> Addon Theme (Buy Here)
                                            </a>
                                        @endif

                                        @if($isActive)
                                            <span class="badge badge-success position-absolute" style="top: 10px; right: 10px; font-size: 0.85rem; padding: 0.4em 0.8em;">
                                                <i class="fas fa-check-circle mr-1"></i> Active
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h5 class="card-title mb-0">{{ $theme['name'] }}</h5>
                                            <div class="d-flex align-items-center">
                                                @if(filter_var(env('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN) && $slug === 'eduna')
                                                    <a href="https://codecanyon.net/item/eduna-eduex-lms-script-laravel-theme/64222922" target="_blank" class="badge badge-warning text-white mr-2 addon-badge" style="font-size: 0.75rem; padding: 0.3em 0.6em;">
                                                        <i class="fas fa-shopping-cart mr-1"></i> Addon (Buy)
                                                    </a>
                                                @endif
                                                <span class="badge badge-secondary">v{{ $theme['version'] ?? '1.0.0' }}</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-2">By {{ $theme['author'] ?? 'Unknown' }}</p>
                                        <p class="card-text text-secondary small flex-grow-1">
                                            {{ $theme['description'] ?? 'No description provided.' }}
                                        </p>

                                        @if($isActive && isset($remainingSeconds) && $remainingSeconds !== null && $slug === 'eduna')
                                            <div class="alert alert-warning mb-2 p-2 small text-center" id="demo-countdown-container" style="border-radius: 4px; border-left: 4px solid #ffc107;">
                                                <i class="fas fa-clock mr-1"></i> Reverts to default in <strong id="demo-countdown" class="text-danger">--:--</strong>
                                            </div>
                                        @endif

                                        <!-- Activation/Variant Form -->
                                        <form method="POST" action="{{ route('admin.frontend.theme.update') }}" class="mt-3">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="theme" value="{{ $slug }}">

                                            @if($hasMultipleVariants)
                                                <div class="form-group mb-2">
                                                    <label class="small font-weight-bold">Select Variant</label>
                                                    <select name="variant" class="form-control form-control-sm">
                                                        @foreach($variants as $variant)
                                                            <option value="{{ $variant['file'] }}" @if($isActive && $activeVariant === $variant['file']) selected @endif>
                                                                {{ $variant['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="row no-gutters">
                                                <div class="@if($isActive) col-12 @else col-9 pr-1 @endif">
                                                    @if($isActive && !$hasMultipleVariants)
                                                        <button type="button" class="btn btn-outline-success btn-block disabled" disabled>
                                                            Active Theme
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-primary btn-block">
                                                            @if($isActive) Update Variant @else Activate @endif
                                                        </button>
                                                    @endif
                                                </div>
                                                @if(!$isActive)
                                                    <div class="col-3 pl-1">
                                                        <button type="button" class="btn btn-outline-danger btn-block" onclick="confirmDeleteTheme('{{ $slug }}')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="delete-theme-form" method="POST" action="{{ route('admin.frontend.theme.delete') }}" style="display:none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="theme" id="delete-theme-slug">
    </form>
@endsection

@push('scripts')
    <script>
        // Custom File Input Display Name
        document.getElementById('theme_zip').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });

        // Theme Delete Confirmation
        function confirmDeleteTheme(slug) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the theme '" + slug + "' and all its assets. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-theme-slug').value = slug;
                    document.getElementById('delete-theme-form').submit();
                }
            });
        }

        @if(isset($remainingSeconds) && $remainingSeconds !== null)
            (function() {
                var remaining = {{ $remainingSeconds }};
                var display = document.getElementById('demo-countdown');
                
                function updateTimer() {
                    if (remaining <= 0) {
                        if (display) {
                            display.innerHTML = "Reverting...";
                        }
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                        return;
                    }
                    
                    var minutes = Math.floor(remaining / 60);
                    var seconds = remaining % 60;
                    
                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;
                    
                    if (display) {
                        display.innerHTML = minutes + ":" + seconds;
                    }
                    remaining--;
                    setTimeout(updateTimer, 1000);
                }
                
                if (display) {
                    updateTimer();
                }
            })();
        @endif
    </script>
@endpush

@push('styles')
    <style>
        .addon-badge {
            transition: all 0.3s ease;
        }
        .addon-badge:hover {
            transform: scale(1.05);
            background-color: #d39e00 !important;
            border-color: #c69500 !important;
            color: #fff !important;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }
    </style>
@endpush
