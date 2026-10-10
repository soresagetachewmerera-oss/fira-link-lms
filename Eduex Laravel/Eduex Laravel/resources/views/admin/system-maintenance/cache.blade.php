@extends('layouts.admin')

@section('title', 'Cache Management')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.system.status') }}">System Maintenance</a></div>
        <div class="breadcrumb-item active">Cache Management</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h4>Clear Application Cache</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Clearing cache will remove compiled views, configuration cache, and application cache.
                        This is safe to run after deploying updates or changing configurations.
                    </p>
                    <form action="{{ route('admin.settings.system.cache.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-broom"></i> Clear Cache
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h4>Cache Metrics</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach ($metrics as $metric => $value)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-capitalize">{{ str_replace('_', ' ', $metric) }}</span>
                                <span class="font-weight-semibold">
                                    @if (is_bool($value))
                                        <span class="badge badge-{{ $value ? 'success' : 'secondary' }}">
                                            {{ $value ? 'Yes' : 'No' }}
                                        </span>
                                    @elseif($metric === 'last_cache_clear' && $value)
                                        {{ \Carbon\Carbon::parse($value)->toDayDateTimeString() }}
                                    @else
                                        {{ $value ?? 'N/A' }}
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
