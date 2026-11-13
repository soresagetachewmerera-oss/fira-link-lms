@extends('setup.layout')

@section('content')
    <h2>Welcome to the EduEx installer</h2>
    <p class="setup-lead">
        This guided wizard will help you verify your server, configure the application and prepare the database so you
        can start using the platform in minutes.
    </p>

    @if ($alreadyInstalled)
        <div class="alert alert-info">
            A previous installation marker was detected. You can continue to reconfigure the application or delete the marker
            file located at <code>{{ config('setup.installed_marker') }}</code> to start from scratch.
        </div>
    @endif

    <ul class="setup-list">
        <li>Server requirements check &amp; directory permissions validation.</li>
        <li>Environment editor to update your <code>.env</code> configuration safely.</li>
        <li>Automated database migrations, seeders and application key generation.</li>
    </ul>

    <div class="setup-actions">
        <span class="setup-actions-note">
            {!! $requirementsMet
                ? 'All minimum requirements look good. Continue to double check details.'
                : 'We will start by checking PHP extensions and permissions.' !!}
        </span>

        <a class="btn btn-primary" href="{{ route('setup.requirements') }}">
            Begin installation
        </a>
    </div>
@endsection

