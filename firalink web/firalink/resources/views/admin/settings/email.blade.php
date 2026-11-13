@extends('layouts.admin')

@section('title', 'Email & Notifications')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Email & Notifications</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Email Preferences</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.email.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Send Welcome Email</h6>
                                <small class="text-muted">Automatically send a welcome email to new users after
                                    registration.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="send_welcome_email"
                                    name="send_welcome_email" value="1"
                                    {{ old('send_welcome_email', $settings['send_welcome_email'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="send_welcome_email"></label>
                            </div>
                        </div>
                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Notify Admin on New Registration</h6>
                                <small class="text-muted">Email administrators whenever a new user joins the
                                    platform.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="notify_admin_new_registration"
                                    name="notify_admin_new_registration" value="1"
                                    {{ old('notify_admin_new_registration', $settings['notify_admin_new_registration'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="notify_admin_new_registration"></label>
                            </div>
                        </div>
                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Course Update Digest</h6>
                                <small class="text-muted">Send periodic summaries of course updates to enrolled
                                    students.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="course_update_digest"
                                    name="course_update_digest" value="1"
                                    {{ old('course_update_digest', $settings['course_update_digest'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="course_update_digest"></label>
                            </div>
                        </div>
                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Marketing Opt-in Default</h6>
                                <small class="text-muted">Enable marketing emails subscription by default during
                                    signup.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="marketing_opt_in_default"
                                    name="marketing_opt_in_default" value="1"
                                    {{ old('marketing_opt_in_default', $settings['marketing_opt_in_default'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="marketing_opt_in_default"></label>
                            </div>
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
