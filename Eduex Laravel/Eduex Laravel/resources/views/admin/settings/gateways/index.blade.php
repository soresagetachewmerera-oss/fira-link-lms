@extends('layouts.admin')

@section('title', 'Payment Gateways')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Payment Gateways</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap py-3" style="border-bottom: 1px solid #f1f5f9;">
                    <div class="d-flex align-items-center">
                        <div class="mr-3" style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #7c3aed, #4c1d95); display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 font-weight-bold" style="color: #0f172a;">Payment Gateway Management</h4>
                            <small class="text-muted">Configure active payment methods, API credentials, and webhooks</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-2 mt-md-0">
                        <span class="badge mr-3 px-3 py-2" style="background: #ede9fe; color: #6d28d9; font-weight: 600; border-radius: 8px;">
                            Exclusive Gateway: Chapa (ETB)
                        </span>
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary" style="border-radius: 8px;">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Settings
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border mb-4" style="border-radius: 10px; background-color: #f8fafc; border-left: 4px solid #7c3aed !important;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-lg mr-3" style="color: #7c3aed;"></i>
                            <div>
                                <strong style="color: #0f172a;">Standardized Payment Gateway:</strong>
                                <span class="text-muted">Chapa is the sole enabled payment gateway for Fira Link Academy, processing transactions strictly in Ethiopian Birr (ETB) via Telebirr, CBE Birr, and Bank/Card transfers. All alternative payment methods are disabled.</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @foreach ($gateways as $gateway)
                            @php($credentials = $gateway->credentials ?? [])
                            @php($fields = $credentialFieldMap[$gateway->identifier] ?? array_keys($credentials))
                            @php($isChapa = $gateway->identifier === 'chapa')

                            <div class="col-lg-8 mx-auto mb-4">
                                <div class="card h-100 shadow-sm border" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; border-color: {{ $gateway->is_enabled ? '#cbd5e1' : '#e2e8f0' }} !important; {{ $isChapa ? 'border-top: 4px solid #7c3aed !important;' : '' }}">
                                    <div class="card-header d-flex justify-content-between align-items-center py-3" style="background-color: {{ $isChapa ? '#faf5ff' : '#f8fafc' }}; border-top-left-radius: 12px; border-top-right-radius: 12px; border-bottom: 1px solid #f1f5f9;">
                                        <div class="d-flex align-items-center">
                                            @if($isChapa)
                                                <div class="mr-2" style="width: 34px; height: 34px; border-radius: 8px; background: #7c3aed; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-bolt"></i>
                                                </div>
                                            @else
                                                <div class="mr-2" style="width: 34px; height: 34px; border-radius: 8px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569;">
                                                    <i class="fas {{ $gateway->type === 'offline' ? 'fa-money-bill-transfer' : 'fa-credit-card' }}"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h5 class="mb-0 font-weight-bold" style="color: #1e293b;">{{ $gateway->name }}</h5>
                                                <small class="text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">{{ $gateway->type }} gateway</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            @if(isset($credentials['mode']))
                                                <span class="badge mr-2 {{ $credentials['mode'] === 'live' ? 'badge-success' : 'badge-warning' }}" style="border-radius: 6px; font-size: 11px;">
                                                    {{ strtoupper($credentials['mode']) }}
                                                </span>
                                            @endif
                                            <span class="badge badge-{{ $gateway->is_enabled ? 'success' : 'secondary' }}" style="border-radius: 6px; font-size: 12px; padding: 6px 12px;">
                                                {{ $gateway->is_enabled ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('admin.settings.gateways.update', $gateway->identifier) }}" method="POST" id="form-{{ $gateway->identifier }}">
                                            @csrf
                                            @method('PUT')

                                            <!-- Toggle Switch -->
                                            <div class="form-group mb-3 p-3 rounded" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                                                <div class="custom-control custom-switch d-flex align-items-center">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="enable-{{ $gateway->identifier }}" name="is_enabled"
                                                        value="1" {{ $gateway->is_enabled ? 'checked' : '' }}>
                                                    <label class="custom-control-label font-weight-bold ml-2" for="enable-{{ $gateway->identifier }}" style="color: #1e293b; cursor: pointer;">
                                                        Enable {{ $gateway->name }} on Platform
                                                    </label>
                                                </div>
                                                <small class="text-muted d-block mt-1 ml-4" style="font-size: 12px;">
                                                    When enabled, students will see this payment option during checkout and course enrollment.
                                                </small>
                                            </div>

                                            @if ($gateway->type === 'online')
                                                <div class="border rounded p-3 mb-3" style="background-color: #ffffff; border-color: #e2e8f0 !important;">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h6 class="mb-0 font-weight-bold" style="color: #334155;">
                                                            <i class="fas fa-key mr-1 text-primary"></i> API Credentials & Configuration
                                                        </h6>
                                                        @if($isChapa)
                                                            <span class="badge badge-light border text-muted" style="font-size: 11px;">
                                                                Telebirr &bull; CBE Birr &bull; Cards
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @forelse($fields as $key)
                                                        @php($isSecret = in_array($key, ['secret_key', 'secret', 'password', 'app_secret', 'client_secret', 'webhook_secret', 'api_key']))
                                                        @php($isMode = $key === 'mode')

                                                        <div class="form-group mb-3">
                                                            <label class="small font-weight-bold text-uppercase d-flex justify-content-between align-items-center" style="color: #475569; letter-spacing: 0.5px;">
                                                                <span>{{ str_replace('_', ' ', $key) }}</span>
                                                                @if($isSecret)
                                                                    <span class="badge badge-light border text-muted" style="font-size: 10px; font-weight: normal;">
                                                                        <i class="fas fa-lock mr-1"></i> Masked
                                                                    </span>
                                                                @endif
                                                            </label>

                                                            @if($isMode)
                                                                <select name="credentials[{{ $key }}]" class="form-control" style="border-radius: 8px;">
                                                                    <option value="sandbox" {{ ($credentials[$key] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                                                                        Sandbox / Test Mode (Recommended for testing)
                                                                    </option>
                                                                    <option value="live" {{ ($credentials[$key] ?? '') === 'live' ? 'selected' : '' }}>
                                                                        Live / Production Mode
                                                                    </option>
                                                                </select>
                                                                <small class="text-muted" style="font-size: 11px;">Switch between sandbox test environment and real live payments.</small>
                                                            @elseif($isSecret)
                                                                <div class="input-group">
                                                                    <input type="password" name="credentials[{{ $key }}]"
                                                                        id="cred-{{ $gateway->identifier }}-{{ $key }}"
                                                                        value="{{ $credentials[$key] ?? '' }}" class="form-control"
                                                                        style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;"
                                                                        placeholder="Enter {{ str_replace('_', ' ', $key) }}"
                                                                        autocomplete="new-password">
                                                                    <div class="input-group-append">
                                                                        <button type="button" class="btn btn-outline-secondary toggle-secret-btn"
                                                                            data-target="cred-{{ $gateway->identifier }}-{{ $key }}"
                                                                            style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;"
                                                                            title="Show/Hide">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted" style="font-size: 11px;">Secret is masked for security. Leave empty on update to keep current value.</small>
                                                            @else
                                                                <input type="text" name="credentials[{{ $key }}]"
                                                                    value="{{ $credentials[$key] ?? '' }}" class="form-control"
                                                                    style="border-radius: 8px;"
                                                                    placeholder="Enter {{ str_replace('_', ' ', $key) }}">
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="form-group mb-2">
                                                            <input type="text" name="credentials[value]" value="" class="form-control" placeholder="Enter credential value">
                                                        </div>
                                                    @endforelse
                                                </div>

                                                @if($isChapa)
                                                    <!-- Chapa Webhook & Callback Panel -->
                                                    <div class="border rounded p-3 mb-3" style="background: #faf5ff; border-color: #ddd6fe !important;">
                                                        <h6 class="mb-2 font-weight-bold" style="color: #6b21a8; font-size: 13px;">
                                                            <i class="fas fa-network-wired mr-1"></i> Chapa Endpoints for Merchant Dashboard
                                                        </h6>
                                                        <p class="text-muted mb-3" style="font-size: 12px;">
                                                            Copy these URLs and paste them into your <a href="https://dashboard.chapa.co" target="_blank" class="font-weight-bold text-primary">Chapa Dashboard</a> under Settings &gt; Webhooks:
                                                        </p>

                                                        <div class="form-group mb-2">
                                                            <label class="small font-weight-bold" style="color: #4c1d95;">Callback URL (Browser Redirect):</label>
                                                            <div class="input-group">
                                                                <input type="text" readonly class="form-control bg-white" style="font-size: 12px; border-radius: 8px 0 0 8px;"
                                                                    id="chapaCallbackUrl" value="{{ route('payment.chapa.callback') }}">
                                                                <div class="input-group-append">
                                                                    <button type="button" class="btn btn-outline-primary copy-btn" data-target="chapaCallbackUrl" style="border-radius: 0 8px 8px 0;" title="Copy URL">
                                                                        <i class="fas fa-copy mr-1"></i> Copy
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group mb-1">
                                                            <label class="small font-weight-bold" style="color: #4c1d95;">Webhook URL (Server-to-Server Instant IPN):</label>
                                                            <div class="input-group">
                                                                <input type="text" readonly class="form-control bg-white" style="font-size: 12px; border-radius: 8px 0 0 8px;"
                                                                    id="chapaWebhookUrl" value="{{ route('payment.chapa.webhook') }}">
                                                                <div class="input-group-append">
                                                                    <button type="button" class="btn btn-outline-primary copy-btn" data-target="chapaWebhookUrl" style="border-radius: 0 8px 8px 0;" title="Copy URL">
                                                                        <i class="fas fa-copy mr-1"></i> Copy
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="form-group mb-3">
                                                    <label for="instructions-{{ $gateway->identifier }}" class="font-weight-bold small text-uppercase" style="color: #475569;">
                                                        Offline / Bank Transfer Instructions
                                                    </label>
                                                    <textarea name="offline_instructions" id="instructions-{{ $gateway->identifier }}" rows="6" class="form-control"
                                                        style="border-radius: 8px;"
                                                        placeholder="Provide bank account details, Telebirr numbers, and instructions for manual payments.">{{ old('offline_instructions', $gateway->offline_instructions) }}</textarea>
                                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                                        These instructions are displayed to students on the checkout page when offline payment is chosen.
                                                    </small>
                                                </div>
                                            @endif

                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2" style="border-top: 1px solid #f1f5f9;">
                                                <div>
                                                    @if($isChapa)
                                                        <button type="button" class="btn btn-outline-info btn-sm test-conn-btn"
                                                            data-url="{{ route('admin.settings.gateways.test', 'chapa') }}"
                                                            style="border-radius: 8px;">
                                                            <i class="fas fa-vial mr-1"></i> Test Connection
                                                        </button>
                                                    @endif
                                                </div>
                                                <div>
                                                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #7c3aed, #6d28d9); border: none; border-radius: 8px; padding: 8px 18px; font-weight: 600;">
                                                        <i class="fas fa-save mr-1"></i> Save Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle password masking
    $('.toggle-secret-btn').on('click', function() {
        var targetId = $(this).data('target');
        var input = $('#' + targetId);
        var icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Copy to clipboard
    $('.copy-btn').on('click', function() {
        var targetId = $(this).data('target');
        var input = document.getElementById(targetId);
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Copied to clipboard!',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                alert('Copied to clipboard: ' + input.value);
            }
        });
    });

    // Test Connection AJAX
    $('.test-conn-btn').on('click', function() {
        var btn = $(this);
        var url = btn.data('url');
        var originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Verifying...');

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Connection Successful',
                        text: response.message,
                        confirmButtonColor: '#7c3aed'
                    });
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Connection test failed.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Test Failed',
                        text: message,
                        confirmButtonColor: '#7c3aed'
                    });
                } else {
                    alert(message);
                }
            }
        });
    });
});
</script>
@endpush
