@extends('layouts.admin')

@section('title', 'Platform Audit Logs')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.saas-billing.plans.index') }}">SaaS Billing</a></div>
    <div class="breadcrumb-item active">Audit Logs</div>
@endsection

@section('main-content')
<div class="section-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">Platform Audit Logs</h2>
            <p class="section-lead">Immutable forensic log of entitlement changes, plan updates, organization overrides, and sensitive administrative actions.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-shield-alt mr-2 text-primary"></i> Audit Trail ({{ $logs->total() }})</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="font-size: 13px;">
                    <thead class="thead-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Actor</th>
                            <th>IP Address</th>
                            <th>Audit Payload / Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td style="white-space: nowrap; font-size: 11px;">
                                    <strong>{{ $log->created_at->format('M d, Y H:i:s') }}</strong>
                                    <div class="text-muted">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border font-mono">{{ $log->action }}</span>
                                </td>
                                <td>
                                    <strong>{{ class_basename($log->auditable_type ?? 'N/A') }}</strong>
                                    @if($log->auditable_id)
                                        <span class="text-muted">#{{ $log->auditable_id }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $log->actor_type ?? 'system' }}</div>
                                    @if($log->actor_id)
                                        <small class="text-muted">ID: {{ $log->actor_id }}</small>
                                    @endif
                                </td>
                                <td class="font-mono" style="font-size: 11px;">{{ $log->ip_address ?? '—' }}</td>
                                <td style="max-width: 380px;">
                                    @if(!empty($log->metadata))
                                        <pre class="bg-light p-2 rounded mb-0 text-dark" style="font-size: 11px; max-height: 120px; overflow-y: auto;">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No audit logs recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer py-2">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

