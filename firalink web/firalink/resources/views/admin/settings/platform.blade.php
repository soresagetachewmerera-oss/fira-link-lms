@extends('layouts.admin')

@section('title', 'Platform Configuration')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Platform Configuration</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>General Platform Details</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"> <i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.platform.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="site_name">Site Name</label>
                            <input type="text" name="site_name" id="site_name"
                                class="form-control @error('site_name') is-invalid @enderror"
                                value="{{ old('site_name', $settings['site_name'] ?? '') }}" required>
                            @error('site_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="tagline">Tagline</label>
                            <input type="text" name="tagline" id="tagline"
                                class="form-control @error('tagline') is-invalid @enderror"
                                value="{{ old('tagline', $settings['tagline'] ?? '') }}">
                            @error('tagline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="timezone">Timezone</label>
                                <input type="text" name="timezone" id="timezone"
                                    class="form-control @error('timezone') is-invalid @enderror"
                                    value="{{ old('timezone', $settings['timezone'] ?? config('app.timezone')) }}" required>
                                @error('timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="default_language">Default Language</label>
                                <input type="text" name="default_language" id="default_language"
                                    class="form-control @error('default_language') is-invalid @enderror"
                                    value="{{ old('default_language', $settings['default_language'] ?? config('app.locale')) }}"
                                    required>
                                @error('default_language')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="default_currency">Default Currency</label>
                            <select name="default_currency" id="default_currency"
                                class="form-control @error('default_currency') is-invalid @enderror" required>
                                @foreach ($currencyOptions as $code => $label)
                                    <option value="{{ $code }}"
                                        {{ old('default_currency', $selectedCurrency) === $code ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            @error('default_currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
