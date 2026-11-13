@extends('setup.layout')

@section('content')
    <h2>Database setup</h2>
    <p class="setup-lead">
        We will run the database migrations and seeders now. This may take a minute depending on your connection.
    </p>

    @if ($error)
        <div class="alert alert-error">
            {{ $error }}
        </div>
    @endif

    @if ($statusMessage)
        <div class="alert alert-success">
            {{ $statusMessage }}
        </div>
    @endif

    <form action="{{ route('setup.database.run') }}" method="POST" class="setup-block">
        @csrf

        <div class="setup-actions">
            <a class="btn btn-secondary" href="{{ route('setup.environment') }}">
                ← Back
            </a>

            <button type="submit" class="btn btn-primary">
                Run migrations
            </button>
        </div>
    </form>

    @if ($output)
        <div class="setup-block">
            <h3 class="setup-heading">Command output</h3>
            <pre class="setup-code">{{ trim($output) }}</pre>
        </div>
    @endif
@endsection

