<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Updater - Admin Dashboard</title>
    @php($favicon = branding_asset(['favicon_path', 'logos.favicon', 'favicon'], 'assets/front/img/favicon.svg'))
    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="shortcut icon" href="{{ $favicon }}">

    <!-- General CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/admin/modules/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/modules/fontawesome/css/all.min.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/custom.css') }}">
    {!! ToastMagic::styles() !!}
</head>
<body style="background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 0;">

<div class="container">
    <div class="row justify-content-center mb-2">
        <div class="col-md-8 d-flex justify-content-start">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light shadow-sm border-0 d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="fas fa-arrow-left"></i>
                <span class="font-weight-bold">Back to Dashboard</span>
            </a>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Versions Comparison Card -->
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #004F44, #002D27); color: white;">
                <div class="card-body p-4 p-md-5 relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h6 class="text-white-50 text-uppercase tracking-widest mb-1">System Status</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success text-white">Active & Running</span>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-rocket fa-2x text-white-50"></i>
                        </div>
                    </div>

                    <div class="row text-center text-md-start">
                        <!-- Current Version -->
                        <div class="{{ $newVersion != $currentVersion ? 'col-md-6 border-end' : 'col-12 text-center' }}">
                            <p class="text-white-50 text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">Current Version</p>
                            <h1 class="display-4 fw-bold mb-0 text-white">{{ $currentVersion }}</h1>
                            <p class="text-white-50 mt-2" style="font-size: 0.8rem;">Installed</p>
                        </div>

                        <!-- Available Version -->
                        @if($newVersion != $currentVersion)
                            <div class="col-md-6 ps-md-5 mt-4 mt-md-0">
                                <p class="text-warning text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">
                                    Available Update
                                </p>
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                                    <h1 class="display-4 fw-bold text-success mb-0">
                                        {{ $newVersion }}
                                    </h1>
                                    @if(version_compare($newVersion, $currentVersion, '>'))
                                        <span class="badge bg-success text-uppercase text-white">New</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Update Action Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-start gap-4 mb-4">
                        <div class="p-3 bg-light text-primary rounded-circle mr-3">
                            <i class="fas fa-sync-alt fa-fw"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-2">System Synchronization</h4>
                            <p class="text-muted small mb-0">
                                This process will safely execute pending database migrations and apply the new version patch.
                                <br>
                                <span class="text-warning fw-bold d-inline-block mt-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Recommended to backup database before proceeding.
                                </span>
                            </p>
                        </div>
                    </div>

                    @if(version_compare($newVersion, $currentVersion, '>'))
                        <div class="alert alert-success d-flex align-items-center mb-4">
                            <i class="fas fa-arrow-alt-circle-up fa-2x me-3 mr-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1">Update Available</h5>
                                <p class="mb-0 small">A newer version ({{ $newVersion }}) is detected in the codebase.</p>
                            </div>
                        </div>
                    @elseif(version_compare($newVersion, $currentVersion, '<'))
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle fa-2x me-3 mr-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1">Version Mismatch</h5>
                                <p class="mb-0 small">Database version ({{ $currentVersion }}) is higher than codebase ({{ $newVersion }}).</p>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-secondary d-flex align-items-center mb-4">
                            <i class="fas fa-check-double fa-2x me-3 text-muted mr-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">Up to Date</h5>
                                <p class="mb-0 small text-muted">Your system is running the latest codebase version.</p>
                            </div>
                        </div>
                    @endif

                    @if(version_compare($newVersion, $currentVersion, '>'))
                        <form action="{{ route('admin.updater.update') }}" method="POST" id="updateForm">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2" id="updateBtn">
                                <span class="spinner-border spinner-border-sm d-none mr-2" role="status" aria-hidden="true" id="spinner"></span>
                                <i class="fas fa-rocket mr-2" id="btn-icon"></i>
                                <span id="btn-text">Update System Now</span>
                            </button>
                        </form>
                    @else
                        <button type="button" disabled class="btn btn-light btn-lg w-100 py-3 fw-bold text-muted d-flex align-items-center justify-content-center gap-2">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>System is Up to Date</span>
                        </button>
                    @endif

                    <!-- Console Logs -->
                    @if(session('output'))
                        <div class="mt-5">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="text-muted text-uppercase mb-0" style="font-size:0.8rem;">
                                    <i class="fas fa-terminal me-2 mr-2"></i> Execution Log
                                </h6>
                                <span class="badge bg-success text-uppercase text-white">Success</span>
                            </div>
                            <div class="bg-dark text-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                                <pre class="mb-0" style="color: #a8cc8c;">{{ session('output') }}</pre>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mt-4 alert alert-danger d-flex align-items-center">
                            <i class="fas fa-bug fa-fw me-3 mr-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Update Failed</h6>
                                <p class="mb-0 small">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- General JS Scripts -->
<script src="{{ asset('assets/admin/modules/jquery.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/popper.js') }}"></script>
<script src="{{ asset('assets/admin/modules/bootstrap/js/bootstrap.min.js') }}"></script>

{!! ToastMagic::scripts() !!}

<script>
    document.getElementById('updateForm')?.addEventListener('submit', function (e) {
        const btn = document.getElementById('updateBtn');
        const spinner = document.getElementById('spinner');
        const icon = document.getElementById('btn-icon');
        const text = document.getElementById('btn-text');

        btn.disabled = true;
        btn.classList.add('disabled');
        spinner.classList.remove('d-none');
        if(icon) icon.classList.add('d-none');
        text.innerText = 'Updating System...';
    });
</script>
</body>
</html>
