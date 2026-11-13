@extends('layouts.admin')

@section('title', 'Error Logs')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.system.status') }}">System Maintenance</a></div>
        <div class="breadcrumb-item active">Error Logs</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Laravel Log File</h4>
                    <div class="card-header-action">
                        <span class="badge badge-light">Path: {{ $logPath }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($logs)
                        <pre class="bg-dark text-white p-3 rounded" style="max-height: 600px; overflow-y: auto; font-size: 13px;">
{{ $logs }}
                        </pre>
                        <p class="text-muted small mb-0">Showing the most recent 200 lines.</p>
                    @else
                        <div class="alert alert-info mb-0">
                            No log entries found. Your application log file may be empty.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
