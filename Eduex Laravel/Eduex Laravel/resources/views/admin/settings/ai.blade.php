@extends('layouts.admin')

@section('title', 'AI Configuration')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">AI Configuration</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12 col-md-6 col-lg-6">
            <div class="card">
                <form action="{{ route('admin.settings.ai.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header">
                        <h4>AI Configuration</h4>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="gemini_api_key">Gemini API Key</label>
                            <input type="password" name="gemini_api_key" id="gemini_api_key" class="form-control"
                                value="{{ $settings['gemini_api_key'] ?? '' }}" placeholder="Enter your Gemini API key">
                            <small class="form-text text-muted">
                                Leave blank to keep the current key unchanged (if set).
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="gemini_model">Gemini Model</label>
                            <select name="gemini_model" id="gemini_model" class="form-control select2">
                                @foreach($models as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ ($settings['gemini_model'] ?? $defaultModel) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                default: {{ $defaultModel }}
                            </small>
                        </div>
                    </div>

                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>About Gemini AI</h4>
                </div>
                <div class="card-body">
                    <p>
                        Google Gemini is a powerful multimodal AI model capable of understanding and generating text, code,
                        and more.
                    </p>
                    <p>
                        To get your API key, visit the <a href="https://aistudio.google.com/" target="_blank">Google AI
                            Studio</a>.
                    </p>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Ensure your billing is enabled for higher rate limits if you plan to use this
                        in production.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection