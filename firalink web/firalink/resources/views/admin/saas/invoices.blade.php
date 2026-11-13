@extends('layouts.admin')

@section('title', 'SaaS Invoices & Payment Receipts')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item"><a href="{{ route('admin.saas-billing.plans.index') }}">SaaS Billing</a></div>
    <div class="breadcrumb-item active">Invoices</div>
@endsection

@section('main-content')
<div class="section-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">Subscription Billing Invoices</h2>
            <p class="section-lead">Immutable accounting records of platform subscription charges across Ethiopian Birr (ETB) and USD gateways.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert"><span>&times;</span></button>
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-receipt mr-2 text-primary"></i> Invoices Log ({{ $invoices->total() }})</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Organization</th>
                            <th>Amount</th>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Issued At</th>
                            <th>Paid At</th>
                            <th>Provider Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="font-mono font-weight-bold text-dark">
                                    {{ $inv->invoice_number }}
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $inv->organization->name ?? "Org #{$inv->organization_id}" }}</div>
                                </td>
                                <td class="font-weight-bold text-dark">
                                    {{ $inv->currency }} {{ number_format($inv->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge badge-light border text-uppercase">{{ $inv->provider ?? 'chapa' }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'failed' ? 'danger' : 'warning') }} uppercase">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td style="font-size: 12px;">{{ $inv->issued_at ? $inv->issued_at->format('M d, Y H:i') : $inv->created_at->format('M d, Y') }}</td>
                                <td style="font-size: 12px;">{{ $inv->paid_at ? $inv->paid_at->format('M d, Y H:i') : '—' }}</td>
                                <td class="font-mono text-muted" style="font-size: 11px;">{{ $inv->provider_ref ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No subscription invoices generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer py-2">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

