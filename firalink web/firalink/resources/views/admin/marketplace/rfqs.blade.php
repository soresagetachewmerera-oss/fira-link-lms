@extends('layouts.admin')

@section('title', 'Buyer Procurement Tenders (RFQs)')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Coffee Marketplace</div>
        <div class="breadcrumb-item active">Procurement Tenders</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>International Buyer RFQ Tenders</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-md">
                            <thead>
                                <tr>
                                    <th>RFQ #</th>
                                    <th>Tender Title & Buyer</th>
                                    <th>Target Origin</th>
                                    <th>Volume (Bags)</th>
                                    <th>Destination</th>
                                    <th>Quotes Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rfqs as $rfq)
                                    <tr>
                                        <td><code>{{ $rfq->rfq_number }}</code></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $rfq->title }}</div>
                                            <div class="text-small text-muted">Buyer: {{ $rfq->buyer->name }} ({{ $rfq->buyer->email }})</div>
                                        </td>
                                        <td>{{ $rfq->target_origin_region ?? 'Any Region' }}</td>
                                        <td><strong>{{ number_format($rfq->volume_bags) }}</strong> ({{ $rfq->volume_bags * 60 / 1000 }} MT)</td>
                                        <td>{{ $rfq->destination_port }}, {{ $rfq->destination_country }} ({{ $rfq->preferred_incoterm }})</td>
                                        <td>
                                            <span class="badge badge-info">{{ $rfq->quotations->count() }} Quotes</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $rfq->status === 'open' ? 'badge-success' : 'badge-secondary' }} text-uppercase">
                                                {{ $rfq->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No buyer tenders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-right">
                    {{ $rfqs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

