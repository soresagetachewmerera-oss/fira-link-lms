@extends('layouts.admin')

@section('title', 'Account Deletion Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Account Deletion Settings</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Deletion Workflow</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.account_deletion.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_admin_approval"
                                    name="require_admin_approval" value="1"
                                    {{ old('require_admin_approval', $settings['require_admin_approval'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="require_admin_approval">Require admin approval
                                    before account removal</label>
                            </div>
                            <small class="text-muted d-block mt-1">Users will remain active until an administrator approves
                                the deletion request.</small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto_archive_instructor_courses"
                                    name="auto_archive_instructor_courses" value="1"
                                    {{ old('auto_archive_instructor_courses', $settings['auto_archive_instructor_courses'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="auto_archive_instructor_courses">Automatically
                                    archive instructor courses</label>
                            </div>
                            <small class="text-muted d-block mt-1">Keep course content accessible to enrolled students after
                                an instructor leaves.</small>
                        </div>
                        <div class="form-group">
                            <label for="notify_admin_email">Notification Email</label>
                            <input type="email" name="notify_admin_email" id="notify_admin_email"
                                class="form-control @error('notify_admin_email') is-invalid @enderror"
                                value="{{ old('notify_admin_email', $settings['notify_admin_email'] ?? '') }}" required>
                            @error('notify_admin_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Pending requests alerts will be delivered to this address.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Policy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
