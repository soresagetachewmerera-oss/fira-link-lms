@extends('setup.layout')

@section('content')
    <h2>Setup complete 🎉</h2>
    <p class="setup-lead">
        Your application is ready to go. We generated the app key, executed the migrations and stored your environment
        configuration.
    </p>

    @if ($statusMessage)
        <div class="alert alert-success">
            {{ $statusMessage }}
        </div>
    @endif

    @if ($output)
        <div class="setup-block">
            <h3 class="setup-heading">Artisan output</h3>
            <pre class="setup-code">{{ trim($output) }}</pre>
        </div>
    @endif

    <section class="setup-block">
        <h3 class="setup-heading">Environment summary</h3>
        <table class="setup-table">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($envValues as $key => $value)
                    <tr>
                        <td><code>{{ $key }}</code></td>
                        <td>{{ $value === '' ? '—' : $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if ($demoUsers->isNotEmpty())
        <section class="setup-block demo-table">
            <h3 class="setup-heading">Demo accounts (seeded)</h3>
            <p class="setup-actions-note">All demo accounts are created with the password <code>password</code>.</p>
            <table class="setup-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($demoUsers as $user)
                        <tr>
                            <td>{{ $user['name'] }}</td>
                            <td>{{ $user['role'] }}</td>
                            <td><code>{{ $user['email'] }}</code></td>
                            <td><code>{{ $user['password'] }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <div class="setup-actions setup-block">
        <span class="setup-actions-note">Marker file stored at <code>{{ $markerPath }}</code>.</span>
        <a class="btn btn-primary" href="{{ route('home') }}">
            Go to application →
        </a>
    </div>
@endsection

