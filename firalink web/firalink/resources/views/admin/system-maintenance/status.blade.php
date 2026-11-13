@extends('layouts.admin')

@section('title', 'System Maintenance')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">System Maintenance</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header justify-content-between align-items-center">
                    <h4 class="mb-0">System Overview</h4>
                    <div class="card-header-action d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-info mr-1" data-toggle="modal" data-target="#storageLinkModal">
                            <i class="fas fa-link"></i> Storage Link
                        </button>
                        <a href="{{ route('admin.settings.system.cache') }}" class="btn btn-sm btn-primary mr-1">
                            <i class="fas fa-broom"></i> Cache Management
                        </a>
                        <a href="{{ route('admin.settings.system.logs') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Error Logs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($status as $label => $value)
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="p-3 border rounded text-center h-100">
                                    <div class="text-muted text-uppercase small">{{ str_replace('_', ' ', $label) }}</div>
                                    <div class="h5 mb-0 mt-2 font-weight-bold">{{ $value }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Health Checks</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach ($health as $check => $result)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-capitalize">{{ str_replace('_', ' ', $check) }}</span>
                                @if ($result)
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Healthy</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Needs
                                        Attention</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Performance Insights</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach ($performance as $metric => $value)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-capitalize">{{ str_replace('_', ' ', $metric) }}</span>
                                <span class="font-weight-semibold">{{ $value }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage Link Confirmation Modal -->
    <div class="modal fade" id="storageLinkModal" tabindex="-1" role="dialog" aria-labelledby="storageLinkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="storageLinkModalLabel">
                        <i class="fas fa-link text-info mr-2"></i> Recreate Storage Link
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to recreate the public storage symlink?</p>
                    <small class="text-muted">
                        This executes <code>php artisan storage:link</code> to ensure user uploaded files (certificates, profile photos, course media) are publicly accessible.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form action="{{ route('admin.settings.system.storage.link') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-link"></i> Recreate Link
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
