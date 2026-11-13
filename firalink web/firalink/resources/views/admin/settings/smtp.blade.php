@extends('layouts.admin')

@section('title', 'SMTP Credentials')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">SMTP Credentials</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-xl-8 col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Mail Server Configuration</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.smtp.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="host">SMTP Host</label>
                                <input type="text" name="host" id="host"
                                    class="form-control @error('host') is-invalid @enderror"
                                    value="{{ old('host', $settings['host'] ?? '') }}" required>
                                @error('host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="port">Port</label>
                                <input type="number" name="port" id="port"
                                    class="form-control @error('port') is-invalid @enderror"
                                    value="{{ old('port', $settings['port'] ?? 587) }}" required>
                                @error('port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="encryption">Encryption</label>
                                <select name="encryption" id="encryption"
                                    class="form-control @error('encryption') is-invalid @enderror">
                                    @php
                                        $encryption = old('encryption', $settings['encryption'] ?? 'tls');
                                    @endphp
                                    <option value="" {{ $encryption === '' ? 'selected' : '' }}>None</option>
                                    <option value="tls" {{ $encryption === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                                @error('encryption')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username"
                                    class="form-control @error('username') is-invalid @enderror"
                                    value="{{ old('username', $settings['username'] ?? '') }}">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Leave blank to keep existing value">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <hr>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="from_name">From Name</label>
                                <input type="text" name="from_name" id="from_name"
                                    class="form-control @error('from_name') is-invalid @enderror"
                                    value="{{ old('from_name', $settings['from_name'] ?? config('mail.from.name')) }}"
                                    required>
                                @error('from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="from_address">From Email</label>
                                <input type="email" name="from_address" id="from_address"
                                    class="form-control @error('from_address') is-invalid @enderror"
                                    value="{{ old('from_address', $settings['from_address'] ?? config('mail.from.address')) }}"
                                    required>
                                @error('from_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xl-4 col-lg-12 mt-4 mt-xl-0">
            <div class="card card-primary">
                <div class="card-header">
                    <h4>Tips</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Use environment credentials for production environments. Update the password
                        field only when it needs to change.</p>
                    <p class="text-muted mb-0">After updating SMTP settings, send a test email to confirm delivery.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
