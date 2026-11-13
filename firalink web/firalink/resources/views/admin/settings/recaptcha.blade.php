@extends('layouts.admin')

@section('title', 'Recaptcha Configuration')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Recaptcha</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Google Recaptcha</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.recaptcha.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enabled" name="enabled"
                                    value="1" {{ old('enabled', $settings['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="enabled">Enable Recaptcha across public
                                    forms</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="version">Recaptcha Version</label>
                            <select name="version" id="version"
                                class="form-control @error('version') is-invalid @enderror">
                                <option value="v2_checkbox"
                                    {{ old('version', $settings['version'] ?? 'v2_checkbox') === 'v2_checkbox' ? 'selected' : '' }}>
                                    v2 Checkbox ("I'm not a robot")</option>
                                <option value="v3_invisible"
                                    {{ old('version', $settings['version'] ?? 'v2_checkbox') === 'v3_invisible' ? 'selected' : '' }}>
                                    v3 Invisible</option>
                            </select>
                            @error('version')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Be sure your site & secret keys were generated for the selected
                                version.</small>
                        </div>
                        <div class="form-group">
                            <label for="site_key">Site Key</label>
                            <input type="text" name="site_key" id="site_key"
                                class="form-control @error('site_key') is-invalid @enderror"
                                value="{{ old('site_key', $settings['site_key'] ?? '') }}">
                            @error('site_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="secret_key">Secret Key</label>
                            <input type="text" name="secret_key" id="secret_key"
                                class="form-control @error('secret_key') is-invalid @enderror"
                                value="{{ old('secret_key', $settings['secret_key'] ?? '') }}">
                            @error('secret_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="score_threshold">Score Threshold</label>
                            <input type="number" step="0.1" name="score_threshold" id="score_threshold"
                                class="form-control @error('score_threshold') is-invalid @enderror"
                                value="{{ old('score_threshold', $settings['score_threshold'] ?? 0.5) }}" min="0"
                                max="1">
                            @error('score_threshold')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Higher values make Recaptcha more strict.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Recaptcha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
