@extends('setup.layout')

@section('content')
    <h2>Server requirements</h2>
    <p class="setup-lead">
        Your server should meet the minimum PHP version and extension requirements before the application can run smoothly.
    </p>

    <table class="setup-table">
        <thead>
            <tr>
                <th>Requirement</th>
                <th>Details</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>PHP Version</td>
                <td>
                    Required: {{ $summary['php']['required'] }}<br>
                    Current: {{ $summary['php']['current'] }}
                </td>
                    <td>
                        <span class="setup-status {{ $summary['php']['satisfied'] ? 'pass' : 'fail' }}">
                        {{ $summary['php']['satisfied'] ? 'Compatible' : 'Upgrade required' }}
                    </span>
                </td>
            </tr>
            @foreach ($summary['extensions'] as $extension => $data)
                <tr>
                    <td>{{ strtoupper($extension) }}</td>
                    <td>PHP extension</td>
                    <td>
                        <span class="setup-status {{ $data['loaded'] ? 'pass' : 'fail' }}">
                            {{ $data['loaded'] ? 'Enabled' : 'Missing' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (! $ready)
        <div class="alert alert-error" style="margin-top: 24px;">
            Some requirements are missing. Please install the required PHP version or enable the missing extensions before
            continuing.
        </div>
    @endif

    <div class="setup-actions">
        <a class="btn btn-secondary" href="{{ route('setup.welcome') }}">
            ← Back
        </a>

        <a class="btn btn-primary" href="{{ route('setup.permissions') }}">
            Continue
        </a>
    </div>
@endsection

