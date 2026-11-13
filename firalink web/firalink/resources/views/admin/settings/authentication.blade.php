@extends('layouts.admin')

@section('title', 'Authentication & OTP')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Authentication & OTP</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Login Experience</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.authentication.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_verification_required"
                                    name="email_verification_required" value="1"
                                    {{ old('email_verification_required', $settings['email_verification_required'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="email_verification_required">Require email
                                    verification for new accounts</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="login_otp_enabled"
                                    name="login_otp_enabled" value="1"
                                    {{ old('login_otp_enabled', $settings['login_otp_enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="login_otp_enabled">Enable OTP verification on
                                    login</label>
                            </div>
                            <small class="text-muted d-block mt-1">Users will receive a one-time code to confirm their
                                identity.</small>
                        </div>
                        <div class="form-group">
                            <label for="otp_expiry_minutes">OTP Expiry (minutes)</label>
                            <input type="number" name="otp_expiry_minutes" id="otp_expiry_minutes"
                                class="form-control @error('otp_expiry_minutes') is-invalid @enderror"
                                value="{{ old('otp_expiry_minutes', $settings['otp_expiry_minutes'] ?? 10) }}"
                                min="1" max="120" required>
                            @error('otp_expiry_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group d-none">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="recaptcha_on_login"
                                    name="recaptcha_on_login" value="1"
                                    {{ old('recaptcha_on_login', $settings['recaptcha_on_login'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="recaptcha_on_login">Require Recaptcha on login
                                    forms</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Authentication Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
