@extends('layouts.admin')

@section('title', 'Chat Configuration')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Chat Configuration</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Chat Configuration</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.chat.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <!-- Enable Chat -->
                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Enable Chat</h6>
                                <small class="text-muted">Allow students and instructors to message each other.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enabled"
                                    name="enabled" value="1"
                                    {{ old('enabled', $settings['enabled'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="enabled"></label>
                            </div>
                        </div>
                        <hr>

                        <!-- Polling Timeout -->
                        <div class="form-group">
                            <label for="poll_timeout"><h6 class="mb-1">Long Poll Timeout (seconds)</h6></label>
                            <small class="text-muted d-block mb-2">How long the server holds open a polling request before returning. Recommended: 20–30 seconds.</small>
                            <input type="number" class="form-control" id="poll_timeout" name="poll_timeout"
                                   value="{{ old('poll_timeout', $settings['poll_timeout'] ?? 25) }}"
                                   min="5" max="60" style="max-width: 200px;">
                        </div>

                        <!-- Poll Interval -->
                        <div class="form-group">
                            <label for="poll_interval"><h6 class="mb-1">Poll Interval (seconds)</h6></label>
                            <small class="text-muted d-block mb-2">How frequently the server checks for new messages during a long poll. Recommended: 1–3 seconds.</small>
                            <input type="number" class="form-control" id="poll_interval" name="poll_interval"
                                   value="{{ old('poll_interval', $settings['poll_interval'] ?? 2) }}"
                                   min="1" max="10" style="max-width: 200px;">
                        </div>

                        <hr>

                        <!-- Max File Size -->
                        <div class="form-group">
                            <label for="max_file_size"><h6 class="mb-1">Max File Size (KB)</h6></label>
                            <small class="text-muted d-block mb-2">Maximum allowed file upload size per attachment. Default: 10240 KB (10 MB).</small>
                            <input type="number" class="form-control" id="max_file_size" name="max_file_size"
                                   value="{{ old('max_file_size', $settings['max_file_size'] ?? 10240) }}"
                                   min="512" max="51200" style="max-width: 200px;">
                        </div>

                        <!-- Allowed File Types -->
                        <div class="form-group">
                            <label for="allowed_file_types"><h6 class="mb-1">Allowed File Types</h6></label>
                            <small class="text-muted d-block mb-2">Comma-separated list of allowed file extensions.</small>
                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types"
                                   value="{{ old('allowed_file_types', $settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip') }}">
                        </div>

                        <hr>

                        <!-- Messages Per Page -->
                        <div class="form-group">
                            <label for="messages_per_page"><h6 class="mb-1">Messages Per Page</h6></label>
                            <small class="text-muted d-block mb-2">Number of messages loaded when opening a conversation.</small>
                            <input type="number" class="form-control" id="messages_per_page" name="messages_per_page"
                                   value="{{ old('messages_per_page', $settings['messages_per_page'] ?? 50) }}"
                                   min="10" max="200" style="max-width: 200px;">
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
