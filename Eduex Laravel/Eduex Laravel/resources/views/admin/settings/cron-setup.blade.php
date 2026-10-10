@extends('layouts.admin')

@section('title', 'Cron Jobs & Scheduler')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Cron Jobs & Scheduler</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            {{-- Cron Job Setup Instructions --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-clock mr-2"></i> Cron Job Setup</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Why is this needed?</strong>
                        Laravel uses a task scheduler to automate background jobs like sending live class reminders.
                        You need to set up a cron job on your server to trigger all scheduled tasks.
                    </div>

                    {{-- Cron Status --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold text-dark mb-3">
                            <i class="fas fa-heartbeat mr-1"></i> Cron Status
                        </h6>
                        @if ($cronStatus)
                            <div class="d-flex align-items-center">
                                <span class="badge badge-{{ $cronStatus['exit_code'] == 0 ? 'success' : 'danger' }} mr-2"
                                      style="font-size: 13px; padding: 6px 12px;">
                                    {{ $cronStatus['exit_code'] == 0 ? '✓ Running' : '✗ Error (code: ' . $cronStatus['exit_code'] . ')' }}
                                </span>
                                <span class="text-muted">
                                    Last run: <strong>{{ $cronStatus['last_run'] }}</strong>
                                    @if(isset($cronStatus['trigger']) && $cronStatus['trigger'] === 'web_route')
                                        <span class="badge badge-info ml-1" style="font-size: 10px;">via Web Route</span>
                                    @endif
                                </span>
                            </div>
                        @else
                            <div class="d-flex align-items-center">
                                <span class="badge badge-warning mr-2" style="font-size: 13px; padding: 6px 12px;">
                                    ⚠ Not yet configured
                                </span>
                                <span class="text-muted">No cron execution has been recorded yet.</span>
                            </div>
                        @endif
                    </div>

                    <hr>

                    {{-- Setup Instructions --}}
                    <h6 class="font-weight-bold text-dark mb-3">
                        <i class="fas fa-terminal mr-1"></i> Setup Instructions
                    </h6>

                    {{-- Method 1: Standard (SSH Access) --}}
                    <div class="card border mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-server mr-1"></i> Method 1: Standard (VPS / Dedicated Server)</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">If you have SSH access, add this line to your server's crontab (<code>crontab -e</code>):</p>
                            <div class="position-relative">
                                <pre class="bg-dark text-white p-3 rounded" id="cron-standard" style="font-size: 13px; overflow-x: auto;">* * * * * cd {{ $projectRoot }} && {{ $phpBinary }} artisan schedule:run >> /dev/null 2>&1</pre>
                                <button class="btn btn-sm btn-light position-absolute" style="top: 8px; right: 8px;"
                                        onclick="copyToClipboard('cron-standard')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Method 2: cPanel / Plesk URL Trigger (Shared Hosting) --}}
                    <div class="card border mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-globe mr-1"></i> Method 2: Shared Hosting (cPanel / Plesk)</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">If you do not have SSH access, you can trigger the scheduler via URL using a curl command in cPanel's Cron section:</p>
                            <div class="position-relative">
                                <pre class="bg-dark text-white p-3 rounded" id="cron-shared" style="font-size: 13px; overflow-x: auto;">* * * * * curl -s "{{ route('cron.run') }}?key={{ $settings['cron_key'] }}" >> /dev/null 2>&1</pre>
                                <button class="btn btn-sm btn-light position-absolute" style="top: 8px; right: 8px;"
                                        onclick="copyToClipboard('cron-shared')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <p class="mb-1"><strong>How to set up in cPanel:</strong></p>
                                <ol class="mb-0 pl-3 text-muted" style="font-size: 13px;">
                                    <li>Log into cPanel → Find <strong>"Cron Jobs"</strong></li>
                                    <li>Set schedule to <strong>"Once Per Minute (* * * * *)"</strong></li>
                                    <li>Paste the command above in the <strong>"Command"</strong> field</li>
                                    <li>Click <strong>"Add New Cron Job"</strong></li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Method 3: Webcron / External Trigger --}}
                    <div class="card border mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-link mr-1"></i> Method 3: Webcron / External Trigger (e.g. cron-job.org)</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">Configure an external cron trigger service to hit this URL every minute:</p>
                            <div class="position-relative">
                                <pre class="bg-dark text-white p-3 rounded" id="cron-url" style="font-size: 13px; overflow-x: auto;">{{ route('cron.run') }}?key={{ $settings['cron_key'] }}</pre>
                                <button class="btn btn-sm btn-light position-absolute" style="top: 8px; right: 8px;"
                                        onclick="copyToClipboard('cron-url')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Server Info --}}
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle mr-1"></i> Server Information</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-bold" style="width: 200px;">PHP Binary Path</td>
                                        <td><code>{{ $phpBinary }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Project Root</td>
                                        <td><code>{{ $projectRoot }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">PHP Version</td>
                                        <td><code>{{ PHP_VERSION }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Server OS</td>
                                        <td><code>{{ PHP_OS }}</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduler Configuration --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fas fa-tasks mr-2"></i> Scheduler Configuration</h4>
                </div>
                <form action="{{ route('admin.settings.cron_setup.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        {{-- Secret Key --}}
                        <h6 class="font-weight-bold text-dark mb-3">Endpoint Security</h6>
                        <div class="form-group mb-4">
                            <label for="cron_key" class="font-weight-bold">Cron Secret Key</label>
                            <div class="input-group">
                                <input type="text" name="cron_key" id="cron_key"
                                    class="form-control @error('cron_key') is-invalid @enderror"
                                    value="{{ old('cron_key', $settings['cron_key']) }}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-secondary" type="button" id="btn-generate-key">
                                        <i class="fas fa-magic"></i> Generate Key
                                    </button>
                                </div>
                                @error('cron_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Protects your webcron route from unauthorized execution or denial-of-service. Keep this secret.
                            </small>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold text-dark mb-3">Registered Scheduled Tasks</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Task</th>
                                        <th>Command</th>
                                        <th>Frequency</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Live Class Reminders</td>
                                        <td><code>live-classes:send-reminders</code></td>
                                        <td>Every 5 minutes</td>
                                        <td><span class="badge badge-success">Active</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            New scheduled tasks can be registered in <code>routes/console.php</code>. They will automatically execute when the cron is triggered.
                        </small>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function copyToClipboard(elementId) {
                const el = document.getElementById(elementId);
                const text = el.innerText.trim();
                navigator.clipboard.writeText(text).then(() => {
                    const btn = el.parentElement.querySelector('button');
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    btn.classList.replace('btn-light', 'btn-success');
                    setTimeout(() => {
                        btn.innerHTML = original;
                        btn.classList.replace('btn-success', 'btn-light');
                    }, 2000);
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const generateBtn = document.getElementById('btn-generate-key');
                if (generateBtn) {
                    generateBtn.addEventListener('click', function() {
                        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                        let key = '';
                        for (let i = 0; i < 32; i++) {
                            key += chars.charAt(Math.floor(Math.random() * chars.length));
                        }
                        const keyInput = document.getElementById('cron_key');
                        if (keyInput) {
                            keyInput.value = key;
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
