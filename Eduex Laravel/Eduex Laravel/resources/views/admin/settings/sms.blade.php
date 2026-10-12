@extends('layouts.admin')

@section('title', 'SMS Ethiopia & OTP Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">SMS Ethiopia & OTP</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4>SMS Ethiopia Gateway & OTP Management</h4>
                        <small class="text-muted">Dynamic configuration for SMS delivery, OTP security thresholds, notification templates, and audit logs.</small>
                    </div>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Settings
                    </a>
                </div>

                <div class="card-body">
                    @php
                        $activeTab = session('active_tab', request('tab', 'provider'));
                    @endphp

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills mb-4" id="smsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'provider' ? 'active' : '' }}" id="provider-tab" data-toggle="tab" href="#provider" role="tab">
                                <i class="fas fa-satellite-dish mr-1"></i> Provider Settings & Test
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'otp' ? 'active' : '' }}" id="otp-tab" data-toggle="tab" href="#otp" role="tab">
                                <i class="fas fa-shield-alt mr-1"></i> OTP Security & Policies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'templates' ? 'active' : '' }}" id="templates-tab" data-toggle="tab" href="#templates" role="tab">
                                <i class="fas fa-file-alt mr-1"></i> Notification Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'logs' ? 'active' : '' }}" id="logs-tab" data-toggle="tab" href="#logs" role="tab">
                                <i class="fas fa-history mr-1"></i> SMS Delivery Logs
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="smsTabsContent">

                        <!-- TAB 1: PROVIDER SETTINGS & TEST SMS -->
                        <div class="tab-pane fade {{ $activeTab === 'provider' ? 'show active' : '' }}" id="provider" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-7 col-md-12">
                                    <div class="card border">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0 text-dark"><i class="fas fa-sliders-h mr-2"></i>Official SMS Ethiopia Credentials</h5>
                                        </div>
                                        <form action="{{ route('admin.settings.sms.provider.update') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="card-body">
                                                <div class="form-group mb-4">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="sms_enabled" name="enabled" value="1"
                                                            {{ old('enabled', $providerSettings['enabled']) ? 'checked' : '' }}>
                                                        <label class="custom-control-label font-weight-bold" for="sms_enabled">
                                                            Enable SMS Ethiopia Delivery Globally
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        When disabled, transactional SMS requests will be recorded safely without dispatching outward HTTP requests.
                                                    </small>
                                                </div>

                                                <div class="form-group">
                                                    <label for="api_url" class="font-weight-bold">API Endpoint URL</label>
                                                    <input type="url" name="api_url" id="api_url" class="form-control @error('api_url') is-invalid @enderror"
                                                        value="{{ old('api_url', $providerSettings['api_url']) }}" required>
                                                    <small class="text-muted">Official endpoint: <code>https://smsethiopia.com/api/sms/send</code></small>
                                                    @error('api_url')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="api_key" class="font-weight-bold">SMS Ethiopia API Key</label>
                                                    <div class="input-group">
                                                        <input type="password" name="api_key" id="api_key" class="form-control @error('api_key') is-invalid @enderror"
                                                            placeholder="{{ !empty($providerSettings['api_key']) ? '•••••••••••••••••••••••••••••••• (Configured)' : 'Enter your SMS Ethiopia API key' }}">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('api_key')">
                                                                <i class="fas fa-eye" id="api_key_icon"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @if(!empty($providerSettings['api_key']))
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> An API key is currently saved. Leave blank to preserve it.</small>
                                                    @else
                                                        <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> No API key configured. SMS dispatch will be skipped until set.</small>
                                                    @endif
                                                    @error('api_key')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="sender_id" class="font-weight-bold">Sender Name / ID</label>
                                                            <input type="text" name="sender_id" id="sender_id" class="form-control @error('sender_id') is-invalid @enderror"
                                                                value="{{ old('sender_id', $providerSettings['sender_id']) }}" placeholder="FiraLink" maxlength="20">
                                                            <small class="text-muted">Registered sender name with SMS Ethiopia.</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="timeout" class="font-weight-bold">Request Timeout (seconds)</label>
                                                            <input type="number" name="timeout" id="timeout" class="form-control @error('timeout') is-invalid @enderror"
                                                                value="{{ old('timeout', $providerSettings['timeout']) }}" min="5" max="60" required>
                                                            <small class="text-muted">Safe HTTP timeout before aborting.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-light text-right">
                                                <button type="submit" class="btn btn-primary px-4">
                                                    <i class="fas fa-save mr-1"></i> Save Provider Settings
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="col-lg-5 col-md-12">
                                    <!-- Test SMS Dispatch Card -->
                                    <div class="card border mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0 text-dark"><i class="fas fa-paper-plane mr-2"></i>Send Test SMS</h5>
                                        </div>
                                        <form action="{{ route('admin.settings.sms.test') }}" method="POST">
                                            @csrf
                                            <div class="card-body">
                                                <div class="alert alert-info py-2">
                                                    <i class="fas fa-info-circle mr-1"></i> Send a live test message to verify gateway connectivity and balance.
                                                </div>
                                                <div class="form-group">
                                                    <label for="test_phone" class="font-weight-bold">Recipient Ethiopian Phone</label>
                                                    <input type="text" name="phone" id="test_phone" class="form-control"
                                                        placeholder="e.g. 0911234567 or +251911234567" required>
                                                    <small class="text-muted">Automatically normalized to <code>2519...</code> format.</small>
                                                </div>
                                                <div class="form-group">
                                                    <label for="test_message" class="font-weight-bold">Message Content</label>
                                                    <textarea name="message" id="test_message" rows="3" class="form-control" maxlength="160" required>This is a test message from Fira Link via SMS Ethiopia gateway.</textarea>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-light text-right">
                                                <button type="submit" class="btn btn-success px-4" {{ empty($providerSettings['api_key']) ? 'disabled' : '' }}>
                                                    <i class="fas fa-paper-plane mr-1"></i> Dispatch Test SMS
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Provider Specs Reference Card -->
                                    <div class="card border bg-light">
                                        <div class="card-body">
                                            <h6 class="font-weight-bold text-dark"><i class="fas fa-book-reader mr-1"></i> Official API Specification</h6>
                                            <ul class="text-muted small pl-3 mb-0">
                                                <li><strong>Method:</strong> POST</li>
                                                <li><strong>URL:</strong> https://smsethiopia.com/api/sms/send</li>
                                                <li><strong>Header:</strong> <code>KEY: &lt;API_KEY&gt;</code></li>
                                                <li><strong>Payload:</strong> <code>{"msisdn": "2519...", "text": "..."}</code></li>
                                                <li><strong>Expected Response:</strong> <code>{"sent": true, "id": 0, "description": "Accepted"}</code></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: OTP SECURITY & POLICIES -->
                        <div class="tab-pane fade {{ $activeTab === 'otp' ? 'show active' : '' }}" id="otp" role="tabpanel">
                            <form action="{{ route('admin.settings.sms.otp.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-lg-6 col-md-12">
                                        <div class="card border">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0 text-dark"><i class="fas fa-stopwatch mr-2"></i>Code Generation & Expiration</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="otp_length" class="font-weight-bold">OTP Code Length (digits)</label>
                                                    <select name="length" id="otp_length" class="form-control @error('length') is-invalid @enderror">
                                                        <option value="4" {{ old('length', $otpSettings['length']) == 4 ? 'selected' : '' }}>4 digits</option>
                                                        <option value="6" {{ old('length', $otpSettings['length']) == 6 ? 'selected' : '' }}>6 digits (Recommended)</option>
                                                        <option value="8" {{ old('length', $otpSettings['length']) == 8 ? 'selected' : '' }}>8 digits</option>
                                                    </select>
                                                    <small class="text-muted">Cryptographically unpredictable numeric code length.</small>
                                                </div>

                                                <div class="form-group">
                                                    <label for="expiry_minutes" class="font-weight-bold">OTP Expiration Duration (minutes)</label>
                                                    <input type="number" name="expiry_minutes" id="expiry_minutes" class="form-control"
                                                        value="{{ old('expiry_minutes', $otpSettings['expiry_minutes']) }}" min="1" max="60" required>
                                                    <small class="text-muted">Codes will automatically expire and be purged after this duration.</small>
                                                </div>

                                                <div class="form-group">
                                                    <label for="resend_cooldown" class="font-weight-bold">Resend Cooldown (seconds)</label>
                                                    <input type="number" name="resend_cooldown_seconds" id="resend_cooldown" class="form-control"
                                                        value="{{ old('resend_cooldown_seconds', $otpSettings['resend_cooldown_seconds']) }}" min="15" max="300" required>
                                                    <small class="text-muted">Minimum seconds a user must wait before requesting a new code.</small>
                                                </div>

                                                <div class="form-group">
                                                    <label for="max_attempts" class="font-weight-bold">Max Verification Attempts</label>
                                                    <input type="number" name="max_attempts" id="max_attempts" class="form-control"
                                                        value="{{ old('max_attempts', $otpSettings['max_attempts']) }}" min="1" max="10" required>
                                                    <small class="text-muted">Brute force guard: code is invalidated immediately after this many invalid attempts.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-12">
                                        <div class="card border">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0 text-dark"><i class="fas fa-toggle-on mr-2"></i>Active Verification Workflows</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group border-bottom pb-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="enable_login" name="enable_login" value="1"
                                                            {{ old('enable_login', $otpSettings['enable_login']) ? 'checked' : '' }}>
                                                        <label class="custom-control-label font-weight-bold" for="enable_login">
                                                            Login Two-Factor Verification
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Users are prompted for a one-time code on login if enabled.</small>
                                                </div>

                                                <div class="form-group border-bottom pb-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="enable_phone_verification" name="enable_phone_verification" value="1"
                                                            {{ old('enable_phone_verification', $otpSettings['enable_phone_verification']) ? 'checked' : '' }}>
                                                        <label class="custom-control-label font-weight-bold" for="enable_phone_verification">
                                                            Phone Number Verification
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Require SMS OTP verification before marking phone numbers as verified.</small>
                                                </div>

                                                <div class="form-group">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="enable_password_reset" name="enable_password_reset" value="1"
                                                            {{ old('enable_password_reset', $otpSettings['enable_password_reset']) ? 'checked' : '' }}>
                                                        <label class="custom-control-label font-weight-bold" for="enable_password_reset">
                                                            Password Reset via SMS
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Allow students to recover passwords via secure phone OTP verification.</small>
                                                </div>

                                                <div class="alert alert-warning mt-4 small mb-0">
                                                    <i class="fas fa-lock mr-1"></i> <strong>Security Guarantee:</strong> OTPs are stored securely as irreversible hashes. Plain verification codes are never exposed in administrative logs, database tables, or client API responses.
                                                </div>
                                            </div>
                                            <div class="card-footer bg-light text-right">
                                                <button type="submit" class="btn btn-primary px-4">
                                                    <i class="fas fa-save mr-1"></i> Save OTP Policies
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 3: NOTIFICATION TEMPLATES -->
                        <div class="tab-pane fade {{ $activeTab === 'templates' ? 'show active' : '' }}" id="templates" role="tabpanel">
                            <div class="alert alert-light border mb-4">
                                <i class="fas fa-info-circle text-primary mr-1"></i>
                                Configure dynamic SMS message templates. Placeholders enclosed in curly braces (e.g. <code>{name}</code>, <code>{otp}</code>, <code>{app_name}</code>) are dynamically populated at dispatch.
                            </div>

                            <div class="row">
                                @forelse ($templates as $template)
                                    <div class="col-lg-6 col-md-12 mb-4">
                                        <div class="card border h-100 shadow-sm">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0 font-weight-bold text-dark">{{ $template->name }}</h6>
                                                    <span class="badge badge-secondary font-weight-normal">Key: <code>{{ $template->key }}</code></span>
                                                </div>
                                                <span class="badge {{ $template->is_enabled ? 'badge-success' : 'badge-danger' }}">
                                                    {{ $template->is_enabled ? 'Active' : 'Disabled' }}
                                                </span>
                                            </div>

                                            <form action="{{ route('admin.settings.sms.template.update', $template->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="card-body">
                                                    <div class="form-group mb-2">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="tpl_enable_{{ $template->id }}"
                                                                name="is_enabled" value="1" {{ $template->is_enabled ? 'checked' : '' }}>
                                                            <label class="custom-control-label" for="tpl_enable_{{ $template->id }}">
                                                                Enable this notification
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="font-weight-bold small text-muted">Message Template Content</label>
                                                        <textarea name="content" rows="3" class="form-control" maxlength="500" required
                                                            id="tpl_content_{{ $template->id }}"
                                                            oninput="updateCharCount('{{ $template->id }}')">{{ $template->content }}</textarea>
                                                        <div class="d-flex justify-content-between small text-muted mt-1">
                                                            <span id="char_count_{{ $template->id }}">{{ strlen($template->content) }} characters</span>
                                                            <span>Standard SMS: 160 chars</span>
                                                        </div>
                                                    </div>

                                                    <div class="form-group mb-0">
                                                        <label class="font-weight-bold small text-muted d-block">Supported Variables</label>
                                                        <div class="d-flex flex-wrap" style="gap: 4px;">
                                                            @if(is_array($template->allowed_variables))
                                                                @foreach($template->allowed_variables as $var)
                                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2"
                                                                        onclick="insertVariable('tpl_content_{{ $template->id }}', '{{ '{' . $var . '}' }}')">
                                                                        + &#123;{{ $var }}&#125;
                                                                    </button>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card-footer bg-light text-right py-2">
                                                    <button type="submit" class="btn btn-primary btn-sm px-3">
                                                        <i class="fas fa-save mr-1"></i> Update Template
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="alert alert-warning">No SMS templates found. Please run the seeder to populate default templates.</div>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- TAB 4: SMS AUDIT LOGS & MONITORING -->
                        <div class="tab-pane fade {{ $activeTab === 'logs' ? 'show active' : '' }}" id="logs" role="tabpanel">
                            <!-- Filter & Action Bar -->
                            <div class="card border mb-3 bg-light">
                                <div class="card-body py-2">
                                    <form action="{{ route('admin.settings.sms') }}" method="GET" class="form-row align-items-center">
                                        <input type="hidden" name="tab" value="logs">
                                        <div class="col-auto">
                                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search phone or template..."
                                                value="{{ request('search') }}">
                                        </div>
                                        <div class="col-auto">
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="">All Statuses</option>
                                                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                                <option value="invalid_number" {{ request('status') === 'invalid_number' ? 'selected' : '' }}>Invalid Number</option>
                                                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
                                            <a href="{{ route('admin.settings.sms', ['tab' => 'logs']) }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
                                        </div>
                                        <div class="col text-right">
                                            <form action="{{ route('admin.settings.sms.logs.clear') }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to clear all SMS audit logs?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash-alt mr-1"></i> Clear Logs
                                                </button>
                                            </form>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-md">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Recipient</th>
                                            <th>User</th>
                                            <th>Template / Event</th>
                                            <th>Message Preview</th>
                                            <th>Status</th>
                                            <th>Provider Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($logs as $log)
                                            <tr>
                                                <td class="small">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                                <td>
                                                    <code>{{ $log->recipient_masked }}</code>
                                                </td>
                                                <td class="small">
                                                    @if($log->user)
                                                        <a href="{{ route('admin.users.edit', $log->user->id) }}">{{ $log->user->name }}</a>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light border">{{ $log->template_key ?? 'direct' }}</span>
                                                </td>
                                                <td class="small text-muted" style="max-width: 250px;">
                                                    {{ $log->message_preview }}
                                                </td>
                                                <td>
                                                    @if ($log->status === 'delivered')
                                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Delivered</span>
                                                    @elseif ($log->status === 'failed')
                                                        <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Failed</span>
                                                    @elseif ($log->status === 'invalid_number')
                                                        <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Invalid</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ ucfirst($log->status) }}</span>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @if(!empty($log->error_message))
                                                        <span class="text-danger" title="{{ $log->error_message }}">
                                                            {{ \Illuminate\Support\Str::limit($log->error_message, 30) }}
                                                        </span>
                                                    @elseif(!empty($log->provider_response))
                                                        <button class="btn btn-xs btn-outline-info py-0 px-1" type="button" data-toggle="collapse" data-target="#resp_{{ $log->id }}">
                                                            View JSON
                                                        </button>
                                                        <div class="collapse mt-1" id="resp_{{ $log->id }}">
                                                            <pre class="bg-dark text-white p-2 rounded small mb-0" style="font-size: 10px;">{{ json_encode($log->provider_response, JSON_PRETTY_PRINT) }}</pre>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i> No SMS delivery logs recorded yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                {{ $logs->links() }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function insertVariable(targetId, variableText) {
            const textarea = document.getElementById(targetId);
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + variableText + text.substring(end);
            textarea.focus();
            textarea.selectionEnd = start + variableText.length;
            const idParts = targetId.split('_');
            const id = idParts[idParts.length - 1];
            updateCharCount(id);
        }

        function updateCharCount(id) {
            const textarea = document.getElementById('tpl_content_' + id);
            const counter = document.getElementById('char_count_' + id);
            if (textarea && counter) {
                counter.textContent = textarea.value.length + ' characters';
            }
        }
    </script>
@endsection
