@extends('layouts.admin')

@section('title', 'Support Center Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Support Center</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Support Channels</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.support.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="help_center_url">Help Center URL</label>
                            <input type="url" name="help_center_url" id="help_center_url"
                                class="form-control @error('help_center_url') is-invalid @enderror"
                                value="{{ old('help_center_url', $settings['help_center_url'] ?? '') }}"
                                placeholder="https://support.example.com">
                            @error('help_center_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="knowledge_base_enabled"
                                    name="knowledge_base_enabled" value="1"
                                    {{ old('knowledge_base_enabled', $settings['knowledge_base_enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="knowledge_base_enabled">Enable knowledge
                                    base</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ticket_portal_enabled"
                                    name="ticket_portal_enabled" value="1"
                                    {{ old('ticket_portal_enabled', $settings['ticket_portal_enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ticket_portal_enabled">Enable ticket portal</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Support Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
