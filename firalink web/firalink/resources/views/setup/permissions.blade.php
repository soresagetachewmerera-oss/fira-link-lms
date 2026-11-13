@extends('setup.layout')

@section('content')
    <h2>Directory permissions</h2>
    <p class="setup-lead">
        Ensure Laravel can write to the required directories. Update permissions on your server if any location fails the check.
    </p>

    <table class="setup-table">
        <thead>
            <tr>
                <th>Path</th>
                <th>Required</th>
                <th>Current</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary as $path => $data)
                <tr>
                    <td><code>{{ $data['path'] }}</code></td>
                    <td>{{ $data['required'] }}</td>
                    <td>{{ $data['current'] ?? 'n/a' }}</td>
                    <td>
                        <span class="setup-status {{ $data['satisfied'] ? 'pass' : 'fail' }}">
                            {{ $data['satisfied'] ? 'Writable' : 'Needs attention' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (! $ready)
        <div class="alert alert-error" style="margin-top: 24px;">
            One or more directories are not writable. Update the ownership or run
            <code>chmod -R 775 storage bootstrap/cache</code> and try again.
        </div>
    @endif

    <div class="setup-actions">
        <a class="btn btn-secondary" href="{{ route('setup.requirements') }}">
            ← Back
        </a>

        <a class="btn btn-primary" href="{{ route('setup.environment') }}">
            Continue
        </a>
    </div>
@endsection

