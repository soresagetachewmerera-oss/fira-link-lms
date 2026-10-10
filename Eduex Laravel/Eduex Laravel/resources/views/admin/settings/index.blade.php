@extends('layouts.admin')

@section('title', 'Settings')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Settings</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Administration Settings</h4>
                    <div class="card-header-action">
                        <span class="badge badge-primary">{{ count($settingsSections) }} areas</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Quickly navigate to configuration areas for payments, courses, users, and other platform tools.
                    </p>
                    <div class="row">
                        @foreach ($settingsSections as $section)
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card border shadow-sm h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 48px; height: 48px;">
                                                <i class="{{ $section['icon'] }}"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h5 class="mb-1">{{ $section['title'] }}</h5>
                                                <small class="text-muted">{{ $section['description'] }}</small>
                                            </div>
                                        </div>

                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            @if ($section['route'])
                                                <a href="{{ $section['route'] }}" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-arrow-right"></i> Manage
                                                </a>
                                            @else
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-lock"></i> Not Available Yet
                                                </button>
                                            @endif

                                            @if (!empty($section['badge']))
                                                <span class="badge badge-warning">{{ $section['badge'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
